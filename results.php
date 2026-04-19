<?php
session_start();

$status = $_SESSION['status'] ?? "fail";

if ($status == "win") {

    // score calculation
    $score = ($_SESSION['time_remaining'] * 2) + (50 - ($_SESSION['hints_used'] * 15));
    $_SESSION['final_score'] = $score;

    echo "<body style='background:black;color:lime;'>";
    echo "<h1>ESCAPED!</h1>";
    echo "<p>Score: $score</p>";

    // save to leaderboard
    $entry = date("Y-m-d H:i:s") . " | Score: $score\n";
    file_put_contents("leaderboard.txt", $entry, FILE_APPEND);

} else {

    echo "<body style='background:red;color:white;'>";
    echo "<h1>TIME UP!</h1>";
}

// leaderboard display
echo "<h2>Leaderboard</h2>";

if (file_exists("leaderboard.txt")) {
    $lines = file("leaderboard.txt");
    rsort($lines);

    foreach (array_slice($lines, 0, 5) as $line) {
        echo "<p>$line</p>";
    }
}

// reset session
session_unset();
?>

<br>
<a href="dashboard.php">Back to Dashboard</a>