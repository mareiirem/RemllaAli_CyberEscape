<?php
session_start();

$times = [
    "easy" => 600,
    "normal" => 420,
    "expert" => 240
];

// difficulty
if (!isset($_SESSION['difficulty'])) {
    $_SESSION['difficulty'] = $_GET['difficulty'] ?? "easy";
}

// restore cookie progress (basic)
if (!isset($_SESSION['start_time']) && isset($_COOKIE['progress'])) {
    $saved = unserialize($_COOKIE['progress']);
    if ($saved) {
        $_SESSION = array_merge($_SESSION, $saved);
    }
}

// START GAME
if (!isset($_SESSION['start_time'])) {
    $_SESSION['start_time'] = time();
    $_SESSION['hints_used'] = 0;
    $_SESSION['solved'] = [
        "math" => false,
        "cipher" => false,
        "pattern" => false
    ];

    // math riddle
    $_SESSION['math_a'] = rand(5, 15);
    $_SESSION['math_b'] = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    // cipher shifting riddle
    $words = ["code", "escape", "matrix", "unlock"];
    $_SESSION['cipher_plain'] = $words[array_rand($words)];

    $shift = rand(1, 4);
    $_SESSION['cipher_shift'] = $shift;
    $_SESSION['cipher_scrambled'] = str_rot13($_SESSION['cipher_plain']); // simple placeholder

    //pattern riddle
    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern'] = $colors[0];
}

// hint system
if (isset($_POST['hint'])) {
    $_SESSION['hints_used']++;
}

// timer
$elapsed = time() - $_SESSION['start_time'];
$limit = $times[$_SESSION['difficulty']] - ($_SESSION['hints_used'] * 60);
$time_left = max(0, $limit - $elapsed);

if ($elapsed >= $limit) {
    $_SESSION['status'] = "fail";
    header("Location: results.php");
    exit();
}

// FIX: ensure solved is always valid array
if (!isset($_SESSION['solved']) || !is_array($_SESSION['solved'])) {
    $_SESSION['solved'] = [
        "math" => false,
        "cipher" => false,
        "pattern" => false
    ];
}

// check answers
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // math
    if (isset($_POST['math']) && $_POST['math'] == $_SESSION['math_answer']) {
        $_SESSION['solved']['math'] = true;
    }

    // cipher (basic check)
    if (isset($_POST['cipher']) && strtolower($_POST['cipher']) == $_SESSION['cipher_plain']) {
        $_SESSION['solved']['cipher'] = true;
    }

    // pattern
    if (isset($_POST['pattern']) && $_POST['pattern'] == $_SESSION['pattern']) {
        $_SESSION['solved']['pattern'] = true;
    }

    // FIX: safe win check (no in_array on null/unsafe state)
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

// save cookie progress
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

<!-- PUZZLE MATH -->
<h3>Math Riddle</h3>
<p><?php echo $_SESSION['math_a'] . " + " . $_SESSION['math_b']; ?> = ?</p>
<form method="POST">
    <input name="math" placeholder="Answer">
    <button type="submit">Submit</button>
</form>

<!-- PUZZLE CIPHER -->
<h3>Cipher Puzzle</h3>
<p>Decode this word:</p>
<p><b><?php echo $_SESSION['cipher_scrambled']; ?></b></p>
<form method="POST">
    <input name="cipher" placeholder="Decoded word">
    <button type="submit">Submit</button>
</form>

<!-- PUZZLE 3: PATTERN -->
<h3>Pattern Lock</h3>
<p>Choose the correct hidden color:</p>
<form method="POST">
    <select name="pattern">
        <option value="red">red</option>
        <option value="blue">blue</option>
        <option value="green">green</option>
        <option value="yellow">yellow</option>
    </select>
    <button type="submit">Submit</button>
</form>

<!-- HINT -->
<form method="POST">
    <button name="hint" type="submit">Use Hint (-60s)</button>
</form>

<?php
echo "<p>Solved: ";
print_r($_SESSION['solved']);
echo "</p>";
?>