<?php
session_start();

// save username before clearing session (for the goodbye message)
$username = $_SESSION['username'] ?? 'Player';

// clear everything and destroy the session
session_unset();
session_destroy();

// also clear the progress cookie
setcookie('progress', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Breakers - Logged Out</title>
    <!-- redirect to login after 3 seconds -->
    <meta http-equiv="refresh" content="3;url=/~sbodapati1/WebProgramming/pw/Project%202/index.php">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a2e;
            color: #eee;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .box {
            background-color: #16213e;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            width: 320px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        h2 {
            color: #e94560;
            margin-bottom: 10px;
        }

        p {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .redirect-msg {
            font-size: 13px;
            color: #888;
        }

        a {
            color: #e94560;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="box">
    <div class="icon">🔒</div>
    <h2>Logged Out</h2>
    <p>See you next time, <strong><?= htmlspecialchars($username) ?></strong>!<br>Your session has been cleared.</p>
    <p class="redirect-msg">Redirecting to login in 3 seconds...<br>
    <a href="/~sbodapati1/WebProgramming/pw/Project%202/index.php">Click here</a>
    </p>
</div>
</body>
</html>