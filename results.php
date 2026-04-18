<?php
session_start();

$status = $_SESSION['status'];

if ($status == "win") {
    $score = $_SESSION['time_remaining'] + (100 - ($_SESSION['hints_used'] * 10));
    $_SESSION['final_score'] = $score;

    echo "<h1 class='win'>ESCAPED!</h1>";
    echo "Score: $score";

} else {
    echo "<h1 class='fail'>TIME UP!</h1>";
}

// Reset game session
session_unset();
?>

<a href="dashboard.php">Back to Dashboard</a>