<?php
session_start();

$status = $_SESSION['status'] ?? "fail";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Results</title>
    <link rel="stylesheet" href="cyberbreakers.css">
</head>

<body class="result-page">

<?php if ($status === "win"): ?>

    <!-- UNLOCK ANIMATION  -->
    <div class="level-overlay unlock-overlay">
        <div class="level-content">
            <img src="assets/unlock.png" class="unlock-icon" alt="Unlock">
            <h1>Congrats! You escaped!</h1>
        </div>
    </div>

    <div class="container win">
        <h1>🎉 ESCAPED!</h1>

        <?php
        $time_remaining = $_SESSION['time_remaining'] ?? 0;
        $hints = $_SESSION['hints_used'] ?? 0;

        $score = ($time_remaining * 2) + (50 - ($hints * 15));
        if ($score < 0) $score = 0;

        $_SESSION['final_score'] = $score;
        ?>

        <h2>Your Score: <?= $score ?></h2>
        <p>Time Bonus: <?= $time_remaining ?></p>
        <p>Hints Used: <?= $hints ?></p>
    </div>

    <?php
    $entry = date("Y-m-d H:i:s") . " | WIN | Score: $score\n";
    file_put_contents("leaderboard.txt", $entry, FILE_APPEND);
    ?>

<?php else: ?>

    <div class="container fail">
        <h1>⏰ TIME UP!</h1>
        <h2>You failed to escape</h2>
    </div>

    <?php
    $entry = date("Y-m-d H:i:s") . " | LOSS\n";
    file_put_contents("leaderboard.txt", $entry, FILE_APPEND);
    ?>

<?php endif; ?>

<!-- LEADERBOARD  -->
<div class="container leaderboard">
    <h2>Leaderboard</h2>

    <?php
    if (file_exists("leaderboard.txt")) {
        $lines = file("leaderboard.txt");

        usort($lines, function($a, $b) {
            preg_match('/Score: (\d+)/', $a, $a1);
            preg_match('/Score: (\d+)/', $b, $b1);

            return (int)($b1[1] ?? 0) <=> (int)($a1[1] ?? 0);
        });

        foreach (array_slice($lines, 0, 5) as $line) {
            echo "<p>$line</p>";
        }
    }
    ?>
</div>

<?php session_unset(); ?>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>