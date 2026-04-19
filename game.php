<?php
session_start();

/* ========= RESET GAME ========= */
if (isset($_GET['new'])) {
    session_unset();
    session_destroy();
    session_start();
}

/* ========= TIME LIMITS ========= */
$times = [
    "easy" => 600,
    "normal" => 420,
    "expert" => 240
];

/* ========= DIFFICULTY ========= */
if (!isset($_SESSION['difficulty'])) {
    $_SESSION['difficulty'] = $_GET['difficulty'] ?? "easy";
}

/* ========= SCRAMBLE FUNCTION ========= */
function scrambleWord($word) {
    do {
        $scrambled = str_shuffle($word);
    } while ($scrambled === $word);
    return $scrambled;
}

/* ========= WORD LIST ========= */
$wordLevels = [
    1 => ["code","game","play","data","node"],
    2 => ["escape","matrix","cipher","binary","system"],
    3 => ["algorithm","function","variable","security","compiler"]
];

/* ========= INITIALIZE GAME ========= */
if (!isset($_SESSION['start_time'])) {

    $_SESSION['start_time'] = time();
    $_SESSION['level'] = 1;
    $_SESSION['hints_used'] = 0;

    $_SESSION['solved'] = [
        "math" => false,
        "cipher" => false,
        "pattern" => false
    ];

    $_SESSION['attempts'] = [
        "math" => 0,
        "cipher" => 0,
        "pattern" => 0
    ];

    /* MATH */
    $_SESSION['math_a'] = rand(5, 15);
    $_SESSION['math_b'] = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    /* SCRAMBLE */
    $words = $wordLevels[$_SESSION['level']];
    $_SESSION['cipher_plain'] = $words[array_rand($words)];
    $_SESSION['cipher_scrambled'] = scrambleWord($_SESSION['cipher_plain']);

    /* PATTERN */
    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern'] = $colors[3];
}

/* ========= NEW PUZZLES ========= */
function newPuzzles() {
    global $wordLevels;

    $_SESSION['solved'] = [
        "math" => false,
        "cipher" => false,
        "pattern" => false
    ];

    $_SESSION['attempts'] = [
        "math" => 0,
        "cipher" => 0,
        "pattern" => 0
    ];

    /* MATH */
    $_SESSION['math_a'] = rand(5, 15);
    $_SESSION['math_b'] = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    /* SCRAMBLE */
    $words = $wordLevels[$_SESSION['level']];
    $_SESSION['cipher_plain'] = $words[array_rand($words)];
    $_SESSION['cipher_scrambled'] = scrambleWord($_SESSION['cipher_plain']);

    /* PATTERN */
    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern'] = $colors[3];
}

/* ========= HINT SYSTEM ========= */
if (isset($_POST['hint'])) {
    $_SESSION['hints_used'] = ($_SESSION['hints_used'] ?? 0) + 1;

    // subtract 60 seconds
    $_SESSION['start_time'] -= 60;

    header("Location: game.php");
    exit();
}

/* ========= PUZZLE HANDLER ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

    /* ===== MATH ===== */
    if ($_POST['type'] === "math" && !$_SESSION['solved']['math']) {
        $_SESSION['attempts']['math']++;

        if ((int)$_POST['value'] === $_SESSION['math_answer']) {
            $_SESSION['solved']['math'] = true;
            $_SESSION['msg_math'] = "Correct!";
        } else {
            $_SESSION['msg_math'] = "Wrong";
        }
    }

    /* ===== CIPHER ===== */
    if ($_POST['type'] === "cipher" && !$_SESSION['solved']['cipher']) {
        $_SESSION['attempts']['cipher']++;

        if (strtolower($_POST['value']) === strtolower($_SESSION['cipher_plain'])) {
            $_SESSION['solved']['cipher'] = true;
            $_SESSION['msg_cipher'] = "Correct!";
        } else {
            $_SESSION['msg_cipher'] = "Wrong";
        }
    }

    /* ===== PATTERN ===== */
    if ($_POST['type'] === "pattern" && !$_SESSION['solved']['pattern']) {
        $_SESSION['attempts']['pattern']++;

        if ($_POST['value'] === $_SESSION['pattern']) {
            $_SESSION['solved']['pattern'] = true;
            $_SESSION['msg_pattern'] = "Correct!";
        } else {
            $_SESSION['msg_pattern'] = "Wrong";
        }
    }

    /* ===== LEVEL COMPLETE ===== */
    if ($_SESSION['solved']['math'] &&
        $_SESSION['solved']['cipher'] &&
        $_SESSION['solved']['pattern']) {

        $_SESSION['level']++;

        if ($_SESSION['level'] > 3) {
            header("Location: results.php");
            exit();
        }

        newPuzzles();
    }

    header("Location: game.php");
    exit();
}

/* ========= TIMER ========= */
$elapsed = time() - $_SESSION['start_time'];
$limit = $times[$_SESSION['difficulty']];
$remaining = max(0, $limit - $elapsed);

if ($remaining <= 0) {
    header("Location: results.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Game</title>
</head>
<body>

<h2>Level <?php echo $_SESSION['level']; ?>/3</h2>

<h2>Time Remaining: <?php echo $remaining; ?> seconds</h2>

<hr>

<!-- ================= HINT ================= -->
<form method="POST">
    <button type="submit" name="hint" value="1">
        Use Hint (-60s)
    </button>
</form>

<p>Hints used: <?php echo $_SESSION['hints_used'] ?? 0; ?></p>

<hr>

<!-- ================= MATH ================= -->
<h3>Math <?php echo $_SESSION['solved']['math'] ? "✅" : ""; ?></h3>
<p><?php echo $_SESSION['msg_math'] ?? ""; ?></p>

<?php if (!$_SESSION['solved']['math']): ?>
    <p><?php echo $_SESSION['math_a'] . " + " . $_SESSION['math_b']; ?></p>

    <form method="POST">
        <input type="hidden" name="type" value="math">
        <input type="number" name="value">
        <button type="submit">Submit</button>
    </form>
<?php endif; ?>

<hr>

<!-- ================= SCRAMBLE ================= -->
<h3>Word Scramble <?php echo $_SESSION['solved']['cipher'] ? "✅" : ""; ?></h3>
<p><?php echo $_SESSION['msg_cipher'] ?? ""; ?></p>

<?php if (!$_SESSION['solved']['cipher']): ?>
    <p><?php echo $_SESSION['cipher_scrambled']; ?></p>

    <form method="POST">
        <input type="hidden" name="type" value="cipher">
        <input type="text" name="value">
        <button type="submit">Submit</button>
    </form>
<?php endif; ?>

<hr>

<!-- ================= PATTERN ================= -->
<h3>Pattern <?php echo $_SESSION['solved']['pattern'] ? "✅" : ""; ?></h3>
<p><?php echo $_SESSION['msg_pattern'] ?? ""; ?></p>

<?php if (!$_SESSION['solved']['pattern']): ?>
    <p><?php echo implode(" → ", $_SESSION['pattern_sequence']); ?> → ?</p>

    <form method="POST">
        <input type="hidden" name="type" value="pattern">

        <select name="value">
            <option>red</option>
            <option>blue</option>
            <option>green</option>
            <option>yellow</option>
        </select>

        <button type="submit">Submit</button>
    </form>
<?php endif; ?>

</body>
</html>