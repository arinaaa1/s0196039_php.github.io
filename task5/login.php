<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (getJWTFromCookie() !== null) {
        header('Location: edit.php');
        exit;
    }
    renderLogin('');
    exit;
}

if ($method === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        renderLogin('Login and password cannot be empty');
        exit;
    }

    $creds = findCredentialsByLogin($pdo, $login);
    if (!$creds || !checkPassword($password, $creds['password_hash'])) {
        renderLogin('Invalid login or password');
        exit;
    }

    $token = generateJWT($creds['application_id'], $login);
    setJWTCookie($token);
    header('Location: edit.php');
    exit;
}

http_response_code(405);
echo 'Method not allowed';

function renderLogin($error) {
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif;
            background: linear-gradient(145deg, #ffe4ec 0%, #ffd6e2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
            border-radius: 2rem;
            box-shadow: 0 20px 35px -12px rgba(236, 72, 153, 0.12), 0 0 0 1px rgba(255, 245, 245, 0.7) inset;
            padding: 2.2rem 2rem;
            width: 100%;
            max-width: 420px;
            transition: all 0.2s ease;
            animation: fadeSlideUp 0.45s ease-out;
        }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo-icon { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        h1 {
            font-size: 1.8rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            background: linear-gradient(135deg, #c4456c, #b8315a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .field { margin-bottom: 1.2rem; }
        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #9e4466;
            margin-bottom: 0.3rem;
            letter-spacing: -0.2px;
        }
        input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #f3cdd8;
            border-radius: 1.2rem;
            font-size: 0.95rem;
            font-family: inherit;
            background: #ffffffdd;
            transition: all 0.2s ease;
            outline: none;
            color: #2d2a2b;
        }
        input:focus {
            border-color: #e07c9e;
            box-shadow: 0 0 0 3px rgba(224, 124, 158, 0.2);
            background: #fff;
        }
        .error-banner {
            background: #fff0f3;
            border: 1.5px solid #e38aa8;
            border-radius: 1.2rem;
            padding: 0.7rem 1rem;
            color: #d94a73;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(95deg, #e47297, #d95580);
            color: white;
            border: none;
            border-radius: 2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 10px rgba(217, 85, 128, 0.2);
        }
        .btn:hover {
            background: linear-gradient(95deg, #dc5f88, #c9456f);
            transform: scale(0.98);
        }
        .links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }
        .links a {
            color: #d95580;
            text-decoration: none;
            font-weight: 500;
        }
        .links a:hover { color: #b8315a; text-decoration: underline; }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo"><span class="logo-icon">🔐</span></div>
    <h1>Вход в систему</h1>

    <?php if ($error): ?>
        <div class="error-banner"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="field"><label>Логин</label><input type="text" name="login" autocomplete="username"></div>
        <div class="field"><label>Пароль</label><input type="password" name="password" autocomplete="current-password"></div>
        <button type="submit" class="btn">Войти</button>
    </form>

    <div class="links"><a href="form.php">← Заполнить новую анкету</a></div>
</div>
</body>
</html>
<?php
}