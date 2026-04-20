<?php
session_start();
$file = "/home/sbodapati1/public_html/WebProgramming/pw/Project 2/leaderboard.json";
/* ===== LOAD DATA ===== */
if (!file_exists($file)) {
    $entries = [];
} else {
    $entries = json_decode(file_get_contents($file), true);
    if (!is_array($entries)) $entries = [];
}
/* ===== SORT BY SCORE ===== */
usort($entries, function($a, $b) {
    return $b['score'] <=> $a['score'];
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leaderboard</title>
    <link rel="stylesheet" href="cyberbreakers.css">
</head>
<body class="dashboard-page">
<div class="leaderboard-container">
    <h1>🏆 Leaderboard</h1>
    <table>
        <tr>
            <th>#</th>
            <th>Player</th>
            <th>Score</th>
            <th>Time Left</th>
            <th>Hints</th>
            <th>Difficulty</th>
        </tr>
        <?php foreach ($entries as $index => $entry): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($entry['username']) ?></td>
            <td><?= $entry['score'] ?></td>
            <td><?= $entry['time_remaining'] ?>s</td>
            <td><?= $entry['hints_used'] ?></td>
            <td><?= ucfirst($entry['difficulty']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="/~sbodapati1/WebProgramming/pw/Project%202/dashboard.php" class="btn">⬅ Back</a>
</div>
</body>
</html>
