<?php
session_start();

// redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$mode = $_POST['mode'] ?? 'login';

$users_file = 'users.json';

function load_users($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function save_users($file, $users) {
    file_put_contents($file, json_encode($users));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $users = load_users($users_file);

    if ($mode === 'register') {
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match!';
        } elseif (isset($users[$username])) {
            $error = 'Username already taken, try another one.';
        } else {
            $users[$username] = [
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'games_played' => 0,
                'best_score' => 0
            ];
            save_users($users_file, $users);
            $success = 'Account created! You can log in now.';
            $mode = 'login';
        }

    } elseif ($mode === 'login') {
        if (empty($username) || empty($password)) {
            $error = 'Please enter your username and password.';
        } elseif (!isset($users[$username])) {
            $error = 'Username not found.';
        } elseif (!password_verify($password, $users[$username]['password'])) {
            $error = 'Wrong password, try again.';
        } else {
            $_SESSION['user_id'] = $username;
            $_SESSION['username'] = $username;
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Breakers - Login</title>
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

        .container {
            background-color: #16213e;
            padding: 40px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        h1 {
            text-align: center;
            color: #e94560;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            font-size: 13px;
            color: #aaa;
            margin-bottom: 25px;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #0f3460;
        }

        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            color: #aaa;
            font-size: 14px;
        }

        .tab.active {
            color: #e94560;
            border-bottom: 2px solid #e94560;
            margin-bottom: -2px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #ccc;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #0f3460;
            border-radius: 5px;
            background-color: #0f3460;
            color: #eee;
            box-sizing: border-box;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #e94560;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #e94560;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }

        button[type="submit"]:hover {
            background-color: #c73652;
        }

        .error {
            background-color: #c0392b;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .success {
            background-color: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .difficulty-info {
            margin-top: 20px;
            border-top: 1px solid #0f3460;
            padding-top: 15px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }

        .difficulty-info span {
            display: inline-block;
            margin: 3px 5px;
            padding: 3px 8px;
            border-radius: 10px;
        }

        .easy   { background-color: #1e8449; }
        .normal { background-color: #d4ac0d; color: #333; }
        .expert { background-color: #c0392b; }
    </style>
</head>
<body>
<div class="container">
    <h1>🕵️ Code Breakers</h1>
    <p class="subtitle">Escape Room — Timed Puzzle Challenge</p>

    <!-- tab buttons to switch between login and register -->
    <div class="tabs">
        <button class="tab <?= $mode === 'login' ? 'active' : '' ?>"
                onclick="switchTab('login', event)">Login</button>
        <button class="tab <?= $mode === 'register' ? 'active' : '' ?>"
                onclick="switchTab('register', event)">Register</button>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($mode) ?>">

        <label>Username</label>
        <input type="text" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Enter username" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>

        <!-- only show confirm password on register -->
        <div id="confirmDiv" style="<?= $mode !== 'register' ? 'display:none;' : '' ?>">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password">
        </div>

        <button type="submit" id="submitBtn">
            <?= $mode === 'login' ? 'Login' : 'Create Account' ?>
        </button>
    </form>

    <div class="difficulty-info">
        Difficulty options:
        <span class="easy">Easy: 10 min</span>
        <span class="normal">Normal: 7 min</span>
        <span class="expert">Expert: 4 min</span>
    </div>
</div>

<script>
    function switchTab(mode, e) {
        document.getElementById('modeInput').value = mode;
        document.getElementById('confirmDiv').style.display = mode === 'register' ? 'block' : 'none';
        document.getElementById('submitBtn').textContent = mode === 'login' ? 'Login' : 'Create Account';

        // update active tab styling
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        e.target.classList.add('active');
    }
</script>
</body>
</html>
