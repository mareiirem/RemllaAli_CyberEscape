<?php
session_start();

// reset game if coming fresh
if (isset($_GET['new'])) {
    session_unset();
    session_destroy();

    // delete old cookie
    setcookie("progress", "", time() - 3600);

    session_start();
}

// time limits
$times = [
    "easy" => 600,
    "normal" => 420,
    "expert" => 240
];

// set difficulty
if (!isset($_SESSION['difficulty'])) {
    $_SESSION['difficulty'] = $_GET['difficulty'] ?? "easy";
}

// restore progress from cookie (SKIP if new game)
if (!isset($_SESSION['start_time']) && isset($_COOKIE['progress']) && !isset($_GET['new'])) {
    $saved = unserialize($_COOKIE['progress']);
    if (is_array($saved)) {
        foreach ($saved as $key => $value) {
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = $value;
            }
        }
    }
}

// cipher function
function caesarCipher($text, $shift) {
    $result = '';
    foreach (str_split($text) as $char) {
        $result .= chr(((ord($char) - 97 + $shift) % 26) + 97);
    }
    return $result;
}

// start game
if (!isset($_SESSION['start_time'])) {
    $_SESSION['start_time'] = time();
    $_SESSION['hints_used'] = 0;
    $_SESSION['solved'] = [
        "math" => false,
        "cipher" => false,
        "pattern" => false
    ];

    // math puzzle
    $_SESSION['math_a'] = rand(5, 15);
    $_SESSION['math_b'] = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    // cipher puzzle
    $words = ["code", "escape", "matrix", "unlock"];
    $_SESSION['cipher_plain'] = $words[array_rand($words)];
    $_SESSION['cipher_shift'] = rand(1, 4);
    $_SESSION['cipher_scrambled'] = caesarCipher(
        $_SESSION['cipher_plain'],
        $_SESSION['cipher_shift']
    );

    // pattern puzzle
    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern'] = $colors[3];
}

// hint system
if (isset($_POST['hint'])) {
    $_SESSION['hints_used']++;

    if (!$_SESSION['solved']['math']) {
        $_SESSION['hint_message'] = "Math hint: close to " . $_SESSION['math_answer'];
    } elseif (!$_SESSION['solved']['cipher']) {
        $_SESSION['hint_message'] = "Cipher shift is " . $_SESSION['cipher_shift'];
    } else {
        $_SESSION['hint_message'] = "Pattern is one of the colors shown";
    }
}

// timer
$elapsed = time() - $_SESSION['start_time'];
$limit = $times[$_SESSION['difficulty']] - ($_SESSION['hints_used'] * 60);
$time_left = max(0, $limit - $elapsed);

// lose condition
if ($elapsed >= $limit) {
    $_SESSION['status'] = "fail";
    header("Location: results.php");
    exit();
}

// ensure solved array exists
if (!isset($_SESSION['solved'])) {
    $_SESSION['solved'] = [
        "math" => false,
        "cipher" => false,
        "pattern" => false
    ];
}

// check answers
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['math']) && $_POST['math'] == $_SESSION['math_answer']) {
        $_SESSION['solved']['math'] = true;
    }

    if (isset($_POST['cipher']) && strtolower($_POST['cipher']) == $_SESSION['cipher_plain']) {
        $_SESSION['solved']['cipher'] = true;
    }

    if (isset($_POST['pattern']) && $_POST['pattern'] == $_SESSION['pattern']) {
        $_SESSION['solved']['pattern'] = true;
    }

    // win condition
    if (
        $_SESSION['solved']['math'] &&
        $_SESSION['solved']['cipher'] &&
        $_SESSION['solved']['pattern']
    ) {
        $_SESSION['status'] = "win";
        $_SESSION['time_remaining'] = $time_left;
        header("Location: results.php");
        exit();
    }
}

// save progress to cookie
setcookie("progress", serialize($_SESSION), time() + 3600);
?>

<!-- TIMER -->
<h2>Time Left: <span id="timer"><?php echo $time_left; ?></span> sec</h2>

<script>
let timeLeft = <?php echo $time_left; ?>;
const timerEl = document.getElementById("timer");

setInterval(() => {
    if (timeLeft > 0) {
        timeLeft--;
        timerEl.textContent = timeLeft;
    } else {
        location.reload();
    }
}, 1000);
</script>

<hr>

<!-- HINT MESSAGE -->
<?php if (isset($_SESSION['hint_message'])): ?>
<p><?php echo $_SESSION['hint_message']; ?></p>
<?php endif; ?>

<!-- MATH -->
<h3>Math</h3>
<p><?php echo $_SESSION['math_a'] . " + " . $_SESSION['math_b']; ?> = ?</p>
<form method="POST">
    <input name="math">
    <button type="submit">Submit</button>
</form>

<!-- CIPHER -->
<h3>Cipher</h3>
<p><?php echo $_SESSION['cipher_scrambled']; ?></p>
<form method="POST">
    <input name="cipher">
    <button type="submit">Submit</button>
</form>

<!-- PATTERN -->
<h3>Pattern</h3>
<p>
<?php echo implode(" → ", $_SESSION['pattern_sequence']); ?> → ?
</p>
<form method="POST">
    <select name="pattern">
        <option>red</option>
        <option>blue</option>
        <option>green</option>
        <option>yellow</option>
    </select>
    <button type="submit">Submit</button>
</form>

<!-- HINT BUTTON -->
<form method="POST">
    <button name="hint">Use Hint (-60s)</button>
</form>