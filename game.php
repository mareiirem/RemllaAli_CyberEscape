<?php
session_start();

/* ========= RESET GAME ========= */
if (isset($_GET['new'])) {
    session_unset();
    session_destroy();
    session_start();
}

/* ========= LEVEL TRANSITION RESET ========= */
if (isset($_GET['clear_level_up'])) {
    unset($_SESSION['level_up'], $_SESSION['level_up_number']);
}

/* ========= TIME LIMIT (DASHBOARD CONTROLLED) ========= */
$default_times = [
    "easy" => 600,
    "normal" => 420,
    "expert" => 240
];

if (
    isset($_SESSION['difficulty']) &&
    isset($default_times[$_SESSION['difficulty']])
) {
    if (
        !isset($_SESSION['time_limit']) ||
        !in_array($_SESSION['time_limit'], $default_times)
    ) {
        $_SESSION['time_limit'] = $default_times[$_SESSION['difficulty']];
    }
} else {
    $_SESSION['time_limit'] = 600;
}

/* ========= SESSION SAFETY ========= */
$_SESSION['difficulty'] = $_SESSION['difficulty'] ?? "easy";
$_SESSION['status'] = $_SESSION['status'] ?? "playing";

if (!isset($_SESSION['pattern_sequence']) || !is_array($_SESSION['pattern_sequence'])) {
    $_SESSION['pattern_sequence'] = [];
}

if (!isset($_SESSION['pattern']) || !is_string($_SESSION['pattern'])) {
    $_SESSION['pattern'] = "red";
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
if (
    !isset($_SESSION['start_time']) ||
    !isset($_SESSION['solved']) ||
    !isset($_SESSION['attempts']) ||
    !isset($_SESSION['pattern_sequence']) ||
    !is_array($_SESSION['pattern_sequence']) ||
    !isset($_SESSION['time_limit'])
) {

    $_SESSION['start_time'] = time();
    $_SESSION['level'] = 1;
    $_SESSION['hints_used'] = 0;
    $_SESSION['hint_penalty'] = 0;

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

/* ========= TIMER ========= */
$limit = $_SESSION['time_limit'];
$elapsed = time() - $_SESSION['start_time'] + ($_SESSION['hint_penalty'] ?? 0);
$remaining = max(0, $limit - $elapsed);

if ($remaining <= 0) {
    $_SESSION['status'] = "fail";
    $_SESSION['time_remaining'] = 0;
    header("Location: results.php");
    exit();
}

/* ========= HINT ========= */
if (isset($_POST['hint'])) {
    $_SESSION['hints_used']++;
    $_SESSION['hint_penalty'] += 60;
    header("Location: game.php");
    exit();
}

/* ========= PUZZLE HANDLER ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

    $_SESSION['sound'] = null;

    if ($_POST['type'] === "math" && !$_SESSION['solved']['math']) {
        $_SESSION['attempts']['math']++;

        if ((int)$_POST['value'] === $_SESSION['math_answer']) {
            $_SESSION['solved']['math'] = true;
            $_SESSION['msg_math'] = "Correct!";
            $_SESSION['sound'] = "correct";
        } else {
            $_SESSION['msg_math'] = "Wrong";
            $_SESSION['sound'] = "wrong";
        }
    }

    if ($_POST['type'] === "cipher" && !$_SESSION['solved']['cipher']) {
        $_SESSION['attempts']['cipher']++;

        if (strtolower($_POST['value']) === strtolower($_SESSION['cipher_plain'])) {
            $_SESSION['solved']['cipher'] = true;
            $_SESSION['msg_cipher'] = "Correct!";
            $_SESSION['sound'] = "correct";
        } else {
            $_SESSION['msg_cipher'] = "Wrong";
            $_SESSION['sound'] = "wrong";
        }
    }

    if ($_POST['type'] === "pattern" && !$_SESSION['solved']['pattern']) {
        $_SESSION['attempts']['pattern']++;

        if ($_POST['value'] === $_SESSION['pattern']) {
            $_SESSION['solved']['pattern'] = true;
            $_SESSION['msg_pattern'] = "Correct!";
            $_SESSION['sound'] = "correct";
        } else {
            $_SESSION['msg_pattern'] = "Wrong";
            $_SESSION['sound'] = "wrong";
        }
    }

    if (
        $_SESSION['solved']['math'] &&
        $_SESSION['solved']['cipher'] &&
        $_SESSION['solved']['pattern']
    ) {

        $_SESSION['level']++;

        if ($_SESSION['level'] <= 3) {
            $_SESSION['level_up'] = true;
            $_SESSION['level_up_number'] = $_SESSION['level'];
        }

        if ($_SESSION['level'] > 3) {
            $_SESSION['status'] = "win";

            $elapsed = time() - $_SESSION['start_time'] + ($_SESSION['hint_penalty'] ?? 0);
            $_SESSION['time_remaining'] = max(0, $_SESSION['time_limit'] - $elapsed);

            header("Location: results.php");
            exit();
        }

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
?>

<!DOCTYPE html>
<html>
<head>
    <title>CyberEscape</title>
    <link rel="stylesheet" href="cyberbreakers.css">
</head>

<body class="game-page">

<?php if (isset($_SESSION['sound'])): ?>
<audio autoplay>
    <source src="assets/<?php echo $_SESSION['sound']; ?>.mp3" type="audio/mpeg">
</audio>
<?php unset($_SESSION['sound']); endif; ?>

<?php if (isset($_SESSION['level_up'])): ?>
<meta http-equiv="refresh" content="2.2;url=game.php?clear_level_up=1">

<div class="level-overlay">
    <div class="level-content">
        <h1>Level Up!</h1>
        <p>Entering Level <?php echo $_SESSION['level_up_number']; ?></p>
        <audio autoplay>
            <source src="assets/correct.mp3" type="audio/mpeg">
        </audio>
    </div>
</div>
<?php endif; ?>

<div class="game-container">

    <div class="hud">
        <div>Level <?php echo $_SESSION['level']; ?>/3</div>
        <div>Time: <?php echo $remaining; ?>s</div>
        <div>Hints: <?php echo $_SESSION['hints_used']; ?></div>

        <form method="POST">
            <button type="submit" name="hint">Use Hint (-60s)</button>
        </form>
    </div>

    <div class="puzzle-grid">

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

        <div class="card">
            <h3>Pattern <?php echo $_SESSION['solved']['pattern'] ? "✅" : ""; ?></h3>
            <p><?php echo $_SESSION['msg_pattern'] ?? ""; ?></p>

            <?php
                $seq = $_SESSION['pattern_sequence'];
                if (!is_array($seq)) $seq = [];
            ?>

            <?php if (!$_SESSION['solved']['pattern']): ?>
                <p><?php echo implode(" → ", $seq); ?> → ?</p>

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