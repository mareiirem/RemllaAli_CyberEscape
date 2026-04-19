<?php
session_start();

/* ========= USERS SYSTEM ========= */
$users_file = "users.json";

function load_users($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_users($file, $users) {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT), LOCK_EX);
}

function update_user_stats($score, $won = false) {
    global $users_file;

    if (!isset($_SESSION['username'])) return;

    $users = load_users($users_file);
    $user = $_SESSION['username'];

    if (!isset($users[$user])) return;

    $users[$user]['games_played'] = $users[$user]['games_played'] ?? 0;
    $users[$user]['games_played']++;

    if ($won) {
        $users[$user]['best_score'] = $users[$user]['best_score'] ?? 0;

        if ($score > $users[$user]['best_score']) {
            $users[$user]['best_score'] = $score;
        }
    }

    save_users($users_file, $users);
}

/* ========= RESET ========= */
if (isset($_GET['new'])) {
    session_unset();
    session_destroy();
    session_start();
}

/* ========= LEVEL RESET ========= */
if (isset($_GET['clear_level_up'])) {
    unset($_SESSION['level_up'], $_SESSION['level_up_number']);
}

/* ========= TIME ========= */
$default_times = [
    "easy" => 600,
    "normal" => 420,
    "expert" => 240
];

$_SESSION['difficulty'] = $_SESSION['difficulty'] ?? "easy";
$_SESSION['time_limit'] = $default_times[$_SESSION['difficulty']] ?? 600;

/* ========= SAFETY ========= */
$_SESSION['status'] = $_SESSION['status'] ?? "playing";

/* ========= SCRAMBLE ========= */
function scrambleWord($word) {
    do {
        $scrambled = str_shuffle($word);
    } while ($scrambled === $word);
    return $scrambled;
}

/* ========= WORDS ========= */
$wordLevels = [
    1 => ["code","game","play","data","node"],
    2 => ["escape","matrix","cipher","binary","system"],
    3 => ["algorithm","function","variable","security","compiler"]
];

/* ========= INIT GAME ========= */
if (!isset($_SESSION['start_time'])) {

    $_SESSION['start_time'] = time();
    $_SESSION['level'] = 1;
    $_SESSION['hints_used'] = 0;
    $_SESSION['hint_penalty'] = 0;

    $_SESSION['solved'] = ["math"=>false,"cipher"=>false,"pattern"=>false];
}

/* ========= HARD GAME STATE REPAIR (IMPORTANT FIX) ========= */

/* LEVEL SAFETY */
$_SESSION['level'] = $_SESSION['level'] ?? 1;

/* MATH SAFETY */
if (!isset($_SESSION['math_a']) || !isset($_SESSION['math_b']) || !isset($_SESSION['math_answer'])) {
    $_SESSION['math_a'] = rand(5, 15);
    $_SESSION['math_b'] = rand(1, 10);
    $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];
}

/* CIPHER SAFETY */
if (!isset($_SESSION['cipher_plain']) || !isset($_SESSION['cipher_scrambled'])) {
    $words = $wordLevels[$_SESSION['level']] ?? $wordLevels[1];
    $_SESSION['cipher_plain'] = $words[array_rand($words)];
    $_SESSION['cipher_scrambled'] = scrambleWord($_SESSION['cipher_plain']);
}

/* PATTERN SAFETY */
if (!isset($_SESSION['pattern_sequence']) || !is_array($_SESSION['pattern_sequence']) || count($_SESSION['pattern_sequence']) !== 3) {
    $colors = ["red", "blue", "green", "yellow"];
    shuffle($colors);

    $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
    $_SESSION['pattern'] = $colors[3] ?? "red";
}

/* SOLVED SAFETY */
$_SESSION['solved'] = $_SESSION['solved'] ?? ["math"=>false,"cipher"=>false,"pattern"=>false];

/* ========= TIMER ========= */
$elapsed = time() - $_SESSION['start_time'] + ($_SESSION['hint_penalty'] ?? 0);
$remaining = max(0, $_SESSION['time_limit'] - $elapsed);

if ($remaining <= 0) {

    $_SESSION['status'] = "fail";

    $score = ($_SESSION['level'] ?? 1) * 100;
    update_user_stats($score, false);

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

/* ========= PUZZLES ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

    $_SESSION['sound'] = null;
    $_SESSION['wrong_flash'] = false;

    $_SESSION['msg_math'] = "";
    $_SESSION['msg_cipher'] = "";
    $_SESSION['msg_pattern'] = "";

    /* ===== MATH ===== */
    if ($_POST['type'] === "math" && !$_SESSION['solved']['math']) {
        if ((int)$_POST['value'] === $_SESSION['math_answer']) {
            $_SESSION['solved']['math'] = true;
            $_SESSION['sound'] = "correct";
        } else {
            $_SESSION['sound'] = "wrong";
            $_SESSION['wrong_flash'] = true;
            $_SESSION['msg_math'] = "❌ Incorrect answer";
        }
    }

    /* ===== CIPHER ===== */
    if ($_POST['type'] === "cipher" && !$_SESSION['solved']['cipher']) {
        if (strtolower($_POST['value']) === strtolower($_SESSION['cipher_plain'])) {
            $_SESSION['solved']['cipher'] = true;
            $_SESSION['sound'] = "correct";
        } else {
            $_SESSION['sound'] = "wrong";
            $_SESSION['wrong_flash'] = true;
            $_SESSION['msg_cipher'] = "❌ Incorrect word";
        }
    }

    /* ===== PATTERN ===== */
    if ($_POST['type'] === "pattern" && !$_SESSION['solved']['pattern']) {
        if ($_POST['value'] === $_SESSION['pattern']) {
            $_SESSION['solved']['pattern'] = true;
            $_SESSION['sound'] = "correct";
        } else {
            $_SESSION['sound'] = "wrong";
            $_SESSION['wrong_flash'] = true;
            $_SESSION['msg_pattern'] = "❌ Incorrect pattern";
        }
    }

    /* ========= LEVEL COMPLETE ========= */
    if ($_SESSION['solved']['math'] &&
        $_SESSION['solved']['cipher'] &&
        $_SESSION['solved']['pattern']) {

        $_SESSION['level']++;

        if ($_SESSION['level'] > 3) {

            $_SESSION['status'] = "win";

            $score = ($_SESSION['level'] * 1000) - ($_SESSION['hints_used'] * 50);
            $_SESSION['time_remaining'] = $_SESSION['time_limit'] - (time() - $_SESSION['start_time']);

            update_user_stats($score, true);

            header("Location: results.php");
            exit();
        }

        $_SESSION['level_up'] = true;
        $_SESSION['level_up_number'] = $_SESSION['level'];

        $_SESSION['solved'] = ["math"=>false,"cipher"=>false,"pattern"=>false];

        /* regenerate puzzles */
        $_SESSION['math_a'] = rand(5, 15);
        $_SESSION['math_b'] = rand(1, 10);
        $_SESSION['math_answer'] = $_SESSION['math_a'] + $_SESSION['math_b'];

        $words = $wordLevels[$_SESSION['level']];
        $_SESSION['cipher_plain'] = $words[array_rand($words)];
        $_SESSION['cipher_scrambled'] = scrambleWord($_SESSION['cipher_plain']);

        $colors = ["red", "blue", "green", "yellow"];
        shuffle($colors);

        $_SESSION['pattern_sequence'] = array_slice($colors, 0, 3);
        $_SESSION['pattern'] = $colors[3] ?? "red";
    }

    if (!isset($_SESSION['level_up'])) {
        header("Location: game.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CyberEscape</title>
    <link rel="stylesheet" href="cyberbreakers.css">
</head>

<body class="game-page <?php echo (!empty($_SESSION['wrong_flash'])) ? 'wrong-flash' : ''; ?>">

<?php unset($_SESSION['wrong_flash']); ?>

<?php if (isset($_SESSION['sound'])): ?>
<audio autoplay>
    <source src="assets/<?php echo $_SESSION['sound']; ?>.mp3">
</audio>
<?php unset($_SESSION['sound']); endif; ?>

<!-- LEVEL UP -->
<?php if (isset($_SESSION['level_up'])): ?>
<meta http-equiv="refresh" content="2.2;url=game.php?clear_level_up=1">

<div class="level-overlay glitch">
    <div class="level-content">
        <img src="assets/unlock.png" class="unlock-icon">
        <h1>Level Up!</h1>
        <p>Entering Level <?php echo $_SESSION['level_up_number']; ?></p>

        <audio autoplay>
            <source src="assets/correct.mp3">
        </audio>
    </div>
</div>
<?php endif; ?>

<!-- GAME -->
<div class="game-container">

    <div class="hud">
        <div>Level <?php echo $_SESSION['level']; ?>/3</div>
        <div>Time: <?php echo $remaining; ?>s</div>
        <div>Hints: <?php echo $_SESSION['hints_used']; ?></div>

        <form method="POST">
            <button name="hint">Use Hint (-60s)</button>
        </form>
    </div>

    <div class="puzzle-grid">

        <!-- MATH -->
        <div class="card">
            <h3>Math <?php echo $_SESSION['solved']['math'] ? "✅" : ""; ?></h3>
            <p class="error-msg"><?php echo $_SESSION['msg_math'] ?? ""; ?></p>

            <?php if (!$_SESSION['solved']['math']): ?>
                <p><?php echo $_SESSION['math_a']." + ".$_SESSION['math_b']; ?></p>
                <form method="POST">
                    <input type="hidden" name="type" value="math">
                    <input type="number" name="value">
                    <button>Submit</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- CIPHER -->
        <div class="card">
            <h3>Word <?php echo $_SESSION['solved']['cipher'] ? "✅" : ""; ?></h3>
            <p class="error-msg"><?php echo $_SESSION['msg_cipher'] ?? ""; ?></p>

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
            <p class="error-msg"><?php echo $_SESSION['msg_pattern'] ?? ""; ?></p>

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