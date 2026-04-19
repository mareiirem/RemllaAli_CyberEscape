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

/* ========= STATUS INIT ========= */
if (!isset($_SESSION['status'])) {
    $_SESSION['status'] = "playing";
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

    $_SESSION['math_a'] = rand(5, 15);
    $_SESSION['math_b'] = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

    $words = $wordLevels[$_SESSION['level']];
    $_SESSION['cipher_plain'] = $words[array_rand($words)];
    $_SESSION['cipher_scrambled'] = scrambleWord($_SESSION['cipher_plain']);

    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);
    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern'] = $colors[3];
}

/* ========= HINT ========= */
if (isset($_POST['hint'])) {
    $_SESSION['hints_used']++;
    $_SESSION['start_time'] += 60;
    header("Location: game.php");
    exit();
}

/* ========= PUZZLE HANDLER ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

    if ($_POST['type'] === "math" && !$_SESSION['solved']['math']) {
        $_SESSION['solved']['math'] = ((int)$_POST['value'] === $_SESSION['math_answer']);
        $_SESSION['msg_math'] = $_SESSION['solved']['math'] ? "Correct!" : "Wrong";
    }

    if ($_POST['type'] === "cipher" && !$_SESSION['solved']['cipher']) {
        $_SESSION['solved']['cipher'] =
            strtolower($_POST['value']) === strtolower($_SESSION['cipher_plain']);
        $_SESSION['msg_cipher'] = $_SESSION['solved']['cipher'] ? "Correct!" : "Wrong";
    }

    if ($_POST['type'] === "pattern" && !$_SESSION['solved']['pattern']) {
        $_SESSION['solved']['pattern'] = ($_POST['value'] === $_SESSION['pattern']);
        $_SESSION['msg_pattern'] = $_SESSION['solved']['pattern'] ? "Correct!" : "Wrong";
    }

    if ($_SESSION['solved']['math'] &&
        $_SESSION['solved']['cipher'] &&
        $_SESSION['solved']['pattern']) {

        $_SESSION['level']++;

        if ($_SESSION['level'] > 3) {
            $_SESSION['status'] = "win";
            $elapsed = time() - $_SESSION['start_time'];
            $_SESSION['time_remaining'] = max(0, $times[$_SESSION['difficulty']] - $elapsed);
            header("Location: results.php");
            exit();
        }

        // reset puzzles (simplified inline)
        $_SESSION['solved'] = ["math"=>false,"cipher"=>false,"pattern"=>false];

        $_SESSION['math_a'] = rand(5, 15);
        $_SESSION['math_b'] = rand(1, 10);
        $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

        $words = $wordLevels[$_SESSION['level']];
        $_SESSION['cipher_plain'] = $words[array_rand($words)];
        $_SESSION['cipher_scrambled'] = scrambleWord($_SESSION['cipher_plain']);

        $colors = ["red", "blue", "green", "yellow"];
        shuffle($colors);
        $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
        $_SESSION['pattern'] = $colors[3];
    }

    header("Location: game.php");
    exit();
}

/* ========= TIMER ========= */
$elapsed = time() - $_SESSION['start_time'];
$limit = $times[$_SESSION['difficulty']];
$remaining = max(0, $limit - $elapsed);

if ($remaining <= 0) {
    $_SESSION['status'] = "fail";
    $_SESSION['time_remaining'] = 0;
    header("Location: results.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CyberEscape</title>
    <link rel="stylesheet" href="cyberbreakers.css">
</head>

<body class="game-page">

<div class="game-container">

    <!-- ===== HUD ===== -->
    <div class="hud">
        <div class="hud-item">Level <?php echo $_SESSION['level']; ?>/3</div>
        <div class="hud-item">Time: <?php echo $remaining; ?>s</div>
        <div class="hud-item">Hints: <?php echo $_SESSION['hints_used']; ?></div>

        <form method="POST">
            <button type="submit" name="hint">Use Hint (-60s)</button>
        </form>
    </div>

    <!-- ===== PUZZLES STACKED ===== -->
    <div class="puzzle-grid">

        <!-- MATH -->
        <div class="card">
            <h3>Math <?php echo $_SESSION['solved']['math'] ? "✅" : ""; ?></h3>
            <p><?php echo $_SESSION['msg_math'] ?? ""; ?></p>

            <?php if (!$_SESSION['solved']['math']): ?>
                <p><?php echo $_SESSION['math_a'] . " + " . $_SESSION['math_b']; ?></p>
                <form method="POST">
                    <input type="hidden" name="type" value="math">
                    <input type="number" name="value">
                    <button>Submit</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- SCRAMBLE -->
        <div class="card">
            <h3>Word Scramble <?php echo $_SESSION['solved']['cipher'] ? "✅" : ""; ?></h3>
            <p><?php echo $_SESSION['msg_cipher'] ?? ""; ?></p>

            <?php if (!$_SESSION['solved']['cipher']): ?>
                <p><?php echo $_SESSION['cipher_scrambled']; ?></p>
                <form method="POST">
                    <input type="hidden" name="type" value="cipher">
                    <input type="text" name="value">
                    <button>Submit</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- PATTERN -->
        <div class="card">
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
                    <button>Submit</button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>