<?php
session_start();

// Reset game if coming fresh
if (isset($_GET['new'])) {
    session_unset();
    session_destroy();
    setcookie("progress", "", time() - 3600);
    session_start();
}

// Time limits per difficulty
$times = [
    "easy"   => 600,
    "normal" => 420,
    "expert" => 240
];

// Set difficulty (only on first load)
if (!isset($_SESSION['difficulty'])) {
    $_SESSION['difficulty'] = $_GET['difficulty'] ?? "easy";
}

// Restore progress from cookie (skip if new game)
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

// Caesar cipher — only shifts a-z, leaves other chars alone
function caesarCipher($text, $shift) {
    $result = '';
    foreach (str_split($text) as $char) {
        if ($char >= 'a' && $char <= 'z') {
            $result .= chr(((ord($char) - 97 + $shift) % 26) + 97);
        } else {
            $result .= $char; // leave non-alpha chars unchanged
        }
    }
    return $result;
}

// Initialize game state (only when no active session)
if (!isset($_SESSION['start_time'])) {
    $_SESSION['start_time']   = time();
    $_SESSION['hints_used']   = 0;
    $_SESSION['level']        = $_SESSION['level'] ?? 1;

    // BUG FIX: Always initialize solved and attempts here,
    // and generate puzzles here — NOT in a separate block below.
    // Previously these were split across the file, so a partial
    // session could skip puzzle generation but still pass the
    // start_time check, leaving undefined puzzle variables.
    $_SESSION['solved'] = [
        "math"    => false,
        "cipher"  => false,
        "pattern" => false
    ];
    $_SESSION['attempts'] = [
        "math"    => 0,
        "cipher"  => 0,
        "pattern" => 0
    ];

    // Math puzzle
    $_SESSION['math_a']      = rand(5, 15);
    $_SESSION['math_b']      = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    // Cipher puzzle
    $words = ["code", "escape", "matrix", "unlock"];
    $_SESSION['cipher_plain']     = $words[array_rand($words)];
    $_SESSION['cipher_shift']     = rand(1, 4);
    $_SESSION['cipher_scrambled'] = caesarCipher(
        $_SESSION['cipher_plain'],
        $_SESSION['cipher_shift']
    );

    // Pattern puzzle
    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern']          = $colors[3];
}

// BUG FIX: New puzzles for a new level must NOT touch start_time.
// Previously, after unsetting 'solved' and redirecting, the init block
// above would fire again on reload (start_time was still set, but
// 'solved' was gone) — actually the real issue was that start_time
// itself got unset in some paths, resetting the timer.
// Now we handle level advancement by generating new puzzles in-place,
// keeping start_time untouched.
function generateNewPuzzles() {
    $_SESSION['solved'] = [
        "math"    => false,
        "cipher"  => false,
        "pattern" => false
    ];
    $_SESSION['attempts'] = [
        "math"    => 0,
        "cipher"  => 0,
        "pattern" => 0
    ];
    unset($_SESSION['hint_message']);

    $_SESSION['math_a']      = rand(5, 15);
    $_SESSION['math_b']      = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    $words = ["code", "escape", "matrix", "unlock"];
    $_SESSION['cipher_plain']     = $words[array_rand($words)];
    $_SESSION['cipher_shift']     = rand(1, 4);
    $_SESSION['cipher_scrambled'] = caesarCipher(
        $_SESSION['cipher_plain'],
        $_SESSION['cipher_shift']
    );

    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern']          = $colors[3];
}

// Hint system
if (isset($_POST['hint'])) {
    $_SESSION['hints_used']++;

    if (!$_SESSION['solved']['math']) {
        $_SESSION['hint_message'] = "Math hint: the answer is close to " . $_SESSION['math_answer'];
    } elseif (!$_SESSION['solved']['cipher']) {
        $_SESSION['hint_message'] = "Cipher hint: shift is " . $_SESSION['cipher_shift'];
    } else {
        $_SESSION['hint_message'] = "Pattern hint: it is one of the colors shown";
    }
}

// Timer calculations
$elapsed   = time() - $_SESSION['start_time'];
$limit     = $times[$_SESSION['difficulty']] - ($_SESSION['hints_used'] * 60);
$time_left = max(0, $limit - $elapsed);
$end_time  = $_SESSION['start_time'] + $limit;

// Lose condition
if ($elapsed >= $limit) {
    $_SESSION['status'] = "fail";
    header("Location: results.php");
    exit();
}

// Track feedback messages for this request
$feedback = [];

// Check answers on POST
// BUG FIX: Previously all three puzzles used separate <form> tags,
// meaning only ONE answer could be submitted per request. That's fine,
// but the page was still fully re-initializing because the init block
// was re-running. Now that init is guarded by start_time correctly,
// each submit only updates the one puzzle that was answered.
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['hint'])) {

    if (isset($_POST['math']) && $_POST['math'] !== '' && !$_SESSION['solved']['math']) {
        $_SESSION['attempts']['math']++;
        if ((int)$_POST['math'] === (int)$_SESSION['math_answer']) {
            $_SESSION['solved']['math'] = true;
            $feedback['math'] = "Correct!";
        } else {
            $feedback['math'] = "Wrong answer, try again.";
        }
    }

    if (isset($_POST['cipher']) && $_POST['cipher'] !== '' && !$_SESSION['solved']['cipher']) {
        $_SESSION['attempts']['cipher']++;
        if (strtolower(trim($_POST['cipher'])) === strtolower(trim((string)$_SESSION['cipher_plain']))) {
            $_SESSION['solved']['cipher'] = true;
            $feedback['cipher'] = "Correct!";
        } else {
            $feedback['cipher'] = "Wrong answer, try again.";
        }
    }

    if (isset($_POST['pattern']) && $_POST['pattern'] !== '' && !$_SESSION['solved']['pattern']) {
        $_SESSION['attempts']['pattern']++;
        if ((string)$_POST['pattern'] === (string)$_SESSION['pattern']) {
            $_SESSION['solved']['pattern'] = true;
            $feedback['pattern'] = "Correct!";
        } else {
            $feedback['pattern'] = "Wrong answer, try again.";
        }
    }

    // Check for level/win completion AFTER updating solved state
    if (
        $_SESSION['solved']['math'] &&
        $_SESSION['solved']['cipher'] &&
        $_SESSION['solved']['pattern']
    ) {
        if ($_SESSION['level'] >= 3) {
            // Player wins
            $_SESSION['status']         = "win";
            $_SESSION['time_remaining'] = $time_left;
            header("Location: results.php");
            exit();
        }

        // Advance level — keep start_time intact, just generate new puzzles
        $_SESSION['level']++;
        generateNewPuzzles();

        // Redirect to avoid re-POST on refresh
        header("Location: game.php");
        exit();
    }
}

// Save progress to cookie
setcookie("progress", serialize($_SESSION), time() + 3600);

// DEBUG: remove once working
// Uncomment the next line to see raw values if answers still fail:
// echo "<pre>math_answer=" . var_export($_SESSION['math_answer'],true) . " cipher_plain=" . var_export($_SESSION['cipher_plain'],true) . " pattern=" . var_export($_SESSION['pattern'],true) . "</pre>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Escape Room - Level <?php echo $_SESSION['level']; ?></title>
</head>
<body>

<!-- LEVEL DISPLAY -->
<h2>Level <?php echo $_SESSION['level']; ?> of 3</h2>

<!-- TIMER -->
<h2>Time Left: <span id="timer"></span> sec</h2>

<script>
const endTime = <?php echo $end_time; ?> * 1000;

function updateTimer() {
    const now = Date.now();
    let timeLeft = Math.floor((endTime - now) / 1000);
    if (timeLeft <= 0) {
        timeLeft = 0;
        location.reload();
    }
    document.getElementById("timer").textContent = timeLeft;
}

setInterval(updateTimer, 1000);
updateTimer();
</script>

<hr>

<!-- HINT MESSAGE -->
<?php if (isset($_SESSION['hint_message'])): ?>
    <p style="color: orange;"><strong>Hint:</strong> <?php echo htmlspecialchars($_SESSION['hint_message']); ?></p>
<?php endif; ?>

<!-- MATH PUZZLE -->
<h3>Math <?php echo $_SESSION['solved']['math'] ? '✅' : ''; ?></h3>
<?php if (isset($feedback['math'])): ?>
    <p style="color:<?php echo $_SESSION['solved']['math'] ? 'green' : 'red'; ?>;">
        <?php echo $feedback['math']; ?>
    </p>
<?php endif; ?>
<?php if (!$_SESSION['solved']['math']): ?>
    <p><?php echo $_SESSION['math_a'] . " + " . $_SESSION['math_b']; ?> = ?</p>
    <form method="POST">
        <input name="math" type="number" required>
        <button type="submit">Submit</button>
    </form>
<?php else: ?>
    <p>Solved in <?php echo $_SESSION['attempts']['math']; ?> attempt(s)!</p>
<?php endif; ?>

<!-- CIPHER PUZZLE -->
<h3>Cipher <?php echo $_SESSION['solved']['cipher'] ? '✅' : ''; ?></h3>
<?php if (isset($feedback['cipher'])): ?>
    <p style="color:<?php echo $_SESSION['solved']['cipher'] ? 'green' : 'red'; ?>;">
        <?php echo $feedback['cipher']; ?>
    </p>
<?php endif; ?>
<?php if (!$_SESSION['solved']['cipher']): ?>
    <p>Decode: <strong><?php echo htmlspecialchars($_SESSION['cipher_scrambled']); ?></strong></p>
    <form method="POST">
        <input name="cipher" type="text" required>
        <button type="submit">Submit</button>
    </form>
<?php else: ?>
    <p>Solved in <?php echo $_SESSION['attempts']['cipher']; ?> attempt(s)!</p>
<?php endif; ?>

<!-- PATTERN PUZZLE -->
<h3>Pattern <?php echo $_SESSION['solved']['pattern'] ? '✅' : ''; ?></h3>
<?php if (isset($feedback['pattern'])): ?>
    <p style="color:<?php echo $_SESSION['solved']['pattern'] ? 'green' : 'red'; ?>;">
        <?php echo $feedback['pattern']; ?>
    </p>
<?php endif; ?>
<?php if (!$_SESSION['solved']['pattern']): ?>
    <p><?php echo implode(" → ", $_SESSION['pattern_sequence']); ?> → ?</p>
    <form method="POST">
        <select name="pattern">
            <option value="red">red</option>
            <option value="blue">blue</option>
            <option value="green">green</option>
            <option value="yellow">yellow</option>
        </select>
        <button type="submit">Submit</button>
    </form>
<?php else: ?>
    <p>Solved in <?php echo $_SESSION['attempts']['pattern']; ?> attempt(s)!</p>
<?php endif; ?>

<hr>

<!-- HINT BUTTON -->
<form method="POST">
    <button name="hint" value="1">Use Hint (-60s)</button>
</form>

<!-- NEW GAME LINK -->
<p><a href="game.php?new=1">Start New Game</a></p>

</body>
</html>
