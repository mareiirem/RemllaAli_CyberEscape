<?php
session_start();

// must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$users_file = 'users.json';

/* ========= LOAD USERS ========= */
function load_json($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

$users = load_json($users_file);

/* ========= SAFE USER DATA ========= */
$user_data = $users[$username] ?? [
    'games_played' => 0,
    'best_score' => 0
];

$games_played = $user_data['games_played'];
$best_score   = $user_data['best_score'];

/* ========= LEADERBOARD (FROM USERS ONLY) ========= */
$leaderboard = [];

foreach ($users as $user => $data) {
    $leaderboard[] = [
        'username' => $user,
        'score' => $data['best_score'] ?? 0,
        'games' => $data['games_played'] ?? 0
    ];
}

usort($leaderboard, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

$leaderboard = array_slice($leaderboard, 0, 10);

/* ========= DIFFICULTY START ========= */
if (isset($_GET['difficulty'])) {

    $diff = $_GET['difficulty'];

    $times = [
        "easy" => 600,
        "normal" => 420,
        "expert" => 240
    ];

    if (isset($times[$diff])) {

        unset($_SESSION['start_time']);
        unset($_SESSION['solved']);
        unset($_SESSION['attempts']);
        unset($_SESSION['pattern_sequence']);
        unset($_SESSION['pattern']);
        unset($_SESSION['level']);

        $_SESSION['difficulty'] = $diff;
        $_SESSION['time_limit'] = $times[$diff];

        $_SESSION['start_time'] = time();
        $_SESSION['hints_used'] = 0;
        $_SESSION['puzzles_solved'] = 0;

        header('Location: game.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Breakers - Dashboard</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a2e;
            color: #eee;
            min-height: 100vh;
        }

        /* top navigation bar */
        .navbar {
            background-color: #16213e;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e94560;
        }

        .navbar h2 {
            color: #e94560;
            font-size: 20px;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #ccc;
        }

        .navbar a {
            color: #e94560;
            text-decoration: none;
            padding: 6px 14px;
            border: 1px solid #e94560;
            border-radius: 5px;
            font-size: 13px;
        }

        .navbar a:hover {
            background-color: #e94560;
            color: white;
        }

        .main-content {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h3 {
            margin-bottom: 15px;
            color: #e94560;
            font-size: 18px;
        }

        /* stats cards at the top */
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: #16213e;
            border: 1px solid #0f3460;
            border-radius: 8px;
            padding: 20px;
            flex: 1;
            text-align: center;
        }

        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            color: #e94560;
        }

        .stat-box .label {
            font-size: 12px;
            color: #aaa;
            margin-top: 5px;
        }

        /* difficulty selection buttons */
        .difficulty-section {
            background-color: #16213e;
            border: 1px solid #0f3460;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .diff-buttons {
            display: flex;
            gap: 15px;
        }

        .diff-btn {
            flex: 1;
            padding: 20px 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: white;
            transition: opacity 0.2s;
        }

        .diff-btn:hover {
            opacity: 0.85;
        }

        .diff-btn .time {
            display: block;
            font-size: 12px;
            font-weight: normal;
            margin-top: 5px;
            opacity: 0.85;
        }

        .btn-easy   { background-color: #1e8449; }
        .btn-normal { background-color: #d4ac0d; color: #222; }
        .btn-expert { background-color: #c0392b; }

        /* leaderboard table */
        .leaderboard-section {
            background-color: #16213e;
            border: 1px solid #0f3460;
            border-radius: 8px;
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #0f3460;
            color: #aaa;
            font-weight: normal;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #0f3460;
            color: #ddd;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* highlight logged in user's row */
        tr.my-row td {
            color: #e94560;
            font-weight: bold;
        }

        .rank-num {
            font-weight: bold;
            color: #e94560;
        }

        .diff-tag {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .tag-easy   { background-color: #1e8449; }
        .tag-normal { background-color: #d4ac0d; color: #333; }
        .tag-expert { background-color: #c0392b; }

        .no-scores {
            text-align: center;
            color: #888;
            padding: 20px;
            font-size: 14px;
        }

        /* how to play section */
        .howto {
            background-color: #16213e;
            border: 1px solid #0f3460;
            border-radius: 8px;
            padding: 20px 25px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #bbb;
            line-height: 1.7;
        }

        .howto ul {
            padding-left: 20px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🕵️ Code Breakers</h2>
    <div class="user-info">
        <span>Welcome, <strong><?= htmlspecialchars($username) ?></strong></span>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main-content">

    <!-- player stats -->
    <div class="stats">
        <div class="stat-box">
            <div class="number"><?= $games_played ?></div>
            <div class="label">Games Played</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= $best_score ?></div>
            <div class="label">Best Score</div>
        </div>
        <div class="stat-box">
            <div class="number">
                <?php
                    // figure out what rank the current user is
                    $rank = '—';
                    foreach ($leaderboard as $i => $entry) {
                        if ($entry['username'] === $username) {
                            $rank = $i + 1;
                            break;
                        }
                    }
                    echo $rank;
                ?>
            </div>
            <div class="label">Leaderboard Rank</div>
        </div>
    </div>

    <!-- how to play info -->
    <div class="howto">
        <strong>How to Play:</strong>
        <ul>
            <li>Pick a difficulty below to start — the timer begins immediately.</li>
            <li>Solve the cipher, math, and pattern puzzles before the timer runs out.</li>
            <li>You can use hints but each one removes 60 seconds from the clock.</li>
            <li>Escape before time hits 0 to get a score. Your progress is saved with cookies.</li>
        </ul>
    </div>

    <!-- pick difficulty -->
    <div class="difficulty-section">
        <h3>Start a New Game</h3>
        <div class="diff-buttons">
            <a href="?difficulty=easy" class="diff-btn btn-easy">
                🟢 Easy
                <span class="time">10 minutes</span>
            </a>
            <a href="?difficulty=normal" class="diff-btn btn-normal">
                🟡 Normal
                <span class="time">7 minutes</span>
            </a>
            <a href="?difficulty=expert" class="diff-btn btn-expert">
                🔴 Expert
                <span class="time">4 minutes</span>
            </a>
        </div>
    </div>

    <!-- leaderboard -->
    <div class="leaderboard-section">
        <h3>Leaderboard (Top 10)</h3>

        <?php if (empty($leaderboard)): ?>
            <p class="no-scores">No scores yet — be the first to escape!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Difficulty</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $i => $entry): ?>
                    <tr <?= $entry['username'] === $username ? 'class="my-row"' : '' ?>>
                        <td class="rank-num"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($entry['username']) ?>
                            <?= $entry['username'] === $username ? ' (you)' : '' ?>
                        </td>
                        <td>
                            <span class="diff-tag tag-<?= $entry['difficulty'] ?? 'normal' ?>">
                                <?= ucfirst($entry['difficulty'] ?? 'normal') ?>
                            </span>
                        </td>
                        <td><?= intval($entry['score']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>