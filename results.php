<?php
session_start();

$status = $_SESSION['status'] ?? "fail";

echo "<body style='font-family:Arial;text-align:center;'>";

/* ================= WIN SCREEN ================= */
if ($status === "win") {

    $time_remaining = $_SESSION['time_remaining'] ?? 0;
    $hints = $_SESSION['hints_used'] ?? 0;

    $score = ($time_remaining * 2) + (50 - ($hints * 15));
    if ($score < 0) $score = 0;

    $_SESSION['final_score'] = $score;

    echo "<div style='background:black;color:lime;padding:40px;'>";
    echo "<h1>🎉 ESCAPED!</h1>";
    echo "<h2>Your Score: $score</h2>";
    echo "<p>Time Bonus: $time_remaining</p>";
    echo "<p>Hints Used: $hints</p>";
    echo "</div>";

    /* save leaderboard */
    $entry = date("Y-m-d H:i:s") . " | WIN | Score: $score\n";
    file_put_contents("leaderboard.txt", $entry, FILE_APPEND);
}

/* ================= LOSE SCREEN ================= */
else {

    echo "<div style='background:red;color:white;padding:40px;'>";
    echo "<h1>⏰ TIME UP!</h1>";
    echo "<h2>You failed to escape</h2>";
    echo "</div>";

    $entry = date("Y-m-d H:i:s") . " | LOSS\n";
    file_put_contents("leaderboard.txt", $entry, FILE_APPEND);
}

/* ================= LEADERBOARD ================= */
echo "<h2>Leaderboard</h2>";

if (file_exists("leaderboard.txt")) {
    $lines = file("leaderboard.txt");

    // sort by score (extract numbers safely)
    usort($lines, function($a, $b) {
        preg_match('/Score: (\d+)/', $a, $a1);
        preg_match('/Score: (\d+)/', $b, $b1);

        return (int)($b1[1] ?? 0) <=> (int)($a1[1] ?? 0);
    });

    foreach (array_slice($lines, 0, 5) as $line) {
        echo "<p>$line</p>";
    }
}

/* ================= RESET ================= */
session_unset();
?>

<br>
<a href="dashboard.php">Back to Dashboard</a>