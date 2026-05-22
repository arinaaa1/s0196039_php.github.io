<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/jwt.php';

$payload = getJWTFromCookie();
if (!$payload) {
    header('Location: login.php');
    exit;
}
$applicationId = $payload['application_id'];
$login = $payload['login'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $pageData = loadFromCookies();
    if (empty($pageData['errors'])) {
        $appData = getApplicationByID($pdo, $applicationId);
        if ($appData) {
            $pageData['values'] = $appData;
        } else {
            http_response_code(500);
            echo 'Failed to load application data';
            exit;
        }
    }
    renderEdit($pageData, $login);
    exit;
}

if ($method === 'POST') {
    list($formData, $errors) = validateFormData($_POST);
    if (!empty($errors)) {
        saveErrorsToCookie($errors, $formData);
        header('Location: edit.php');
        exit;
    }
    if (!updateApplication($pdo, $applicationId, $formData)) {
        http_response_code(500);
        echo 'Internal server error';
        exit;
    }
    saveSuccessToCookie($formData);
    header('Location: edit.php');
    exit;
}

http_response_code(405);
echo 'Method not allowed';

function renderEdit($pageData, $login) {
    global $ALL_LANGUAGES;
    $values = $pageData['values'];
    $errors = $pageData['errors'];
    $success = $pageData['success'];

    $isSelectedLang = function($id) use ($values) {
        return in_array($id, $values['languages'] ?? []);
    };
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование анкеты</title>
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
            padding: 40px 20px;
        }
        .topbar {
            max-width: 680px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            padding: 0.8rem 1.5rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 245, 245, 0.8);
        }
        .topbar-user { font-size: 0.9rem; color: #b34e72; }
        .topbar-user strong { color: #c4456c; }
        .topbar a {
            font-size: 0.85rem;
            color: #d95580;
            text-decoration: none;
            font-weight: 500;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            transition: background 0.2s;
        }
        .topbar a:hover { background: rgba(217, 85, 128, 0.1); }
        .card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
            border-radius: 2rem;
            box-shadow: 0 20px 35px -12px rgba(236, 72, 153, 0.12), 0 0 0 1px rgba(255, 245, 245, 0.7) inset;
            padding: 2.2rem 2rem 2.5rem;
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            transition: all 0.2s ease;
        }
        h1 {
            font-size: 2rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            background: linear-gradient(135deg, #c4456c, #b8315a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(200, 70, 110, 0.2);
            display: inline-block;
        }
        .field { margin-bottom: 1.5rem; }
        .field > label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #9e4466;
            margin-bottom: 0.4rem;
            letter-spacing: -0.2px;
        }
        input[type="text"], input[type="tel"], input[type="email"], input[type="date"], select, textarea {
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
        input:focus, select:focus, textarea:focus {
            border-color: #e07c9e;
            box-shadow: 0 0 0 3px rgba(224, 124, 158, 0.2);
            background: #fff;
        }
        .field-error input, .field-error select, .field-error textarea {
            border-color: #e86c8c;
            background: #fff5f7;
        }
        .error-msg {
            font-size: 0.75rem;
            color: #d94a73;
            margin-top: 0.3rem;
            margin-left: 0.5rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .error-msg::before { content: "✦"; font-size: 0.7rem; color: #e85d8b; }
        textarea { height: 110px; resize: vertical; }
        select[multiple] {
            height: 160px;
            padding: 0.6rem;
            border-radius: 1rem;
            background: #ffffffdd;
        }
        select[multiple] option {
            padding: 0.4rem 0.6rem;
            border-radius: 0.8rem;
            margin: 2px 0;
        }
        select[multiple] option:checked {
            background: #fbc1d2 linear-gradient(0deg, #f7a9c0 0%, #f7a9c0 100%);
            color: #4a1e2f;
        }
        .radio-group {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 0.3rem;
        }
        .radio-group label, .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 400;
            color: #a65472;
            cursor: pointer;
        }
        input[type="radio"], input[type="checkbox"] {
            accent-color: #e36c92;
            width: 1rem;
            height: 1rem;
            margin: 0;
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
            margin-top: 0.6rem;
            box-shadow: 0 4px 10px rgba(217, 85, 128, 0.2);
            letter-spacing: 0.3px;
        }
        .btn:hover {
            background: linear-gradient(95deg, #dc5f88, #c9456f);
            transform: scale(0.98);
        }
        .success-banner {
            background: #fff0f3;
            border: 1.5px solid #e38aa8;
            border-radius: 1.2rem;
            padding: 0.9rem 1.2rem;
            color: #b6436a;
            font-size: 0.9rem;
            margin-bottom: 1.8rem;
            text-align: center;
            font-weight: 500;
        }
        @media (max-width: 600px) {
            body { padding: 1.2rem; }
            .card, .topbar { padding: 1.6rem; }
            h1 { font-size: 1.8rem; }
            .radio-group { flex-direction: column; align-items: flex-start; gap: 0.6rem; }
        }
        .card { animation: fadeSlideUp 0.45s ease-out; }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="topbar">
    <span class="topbar-user">Вы вошли как <strong><?= htmlspecialchars($login) ?></strong></span>
    <a href="logout.php">Выйти</a>
</div>
<div class="card">
    <h1>✏️ Редактирование анкеты</h1>

    <?php if ($success): ?>
        <div class="success-banner">✅ Данные успешно обновлены!</div>
    <?php endif; ?>

    <form action="edit.php" method="POST">
        <div class="field <?= isset($errors['name']) ? 'field-error' : '' ?>">
            <label>ФИО</label>
            <input type="text" name="name" value="<?= htmlspecialchars($values['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['phone']) ? 'field-error' : '' ?>">
            <label>Телефон</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($values['phone'] ?? '') ?>">
            <?php if (isset($errors['phone'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['phone']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['email']) ? 'field-error' : '' ?>">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['birthdate']) ? 'field-error' : '' ?>">
            <label>Дата рождения</label>
            <input type="date" name="birthdate" value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>">
            <?php if (isset($errors['birthdate'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['birthdate']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['gender']) ? 'field-error' : '' ?>">
            <label>Пол</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>> Мужской</label>
                <label><input type="radio" name="gender" value="female" <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>> Женский</label>
            </div>
            <?php if (isset($errors['gender'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['gender']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['languages']) ? 'field-error' : '' ?>">
            <label>Любимый язык программирования</label>
            <select name="languages[]" multiple>
                <?php foreach ($ALL_LANGUAGES as $lang): ?>
                <option value="<?= htmlspecialchars($lang['id']) ?>" <?= $isSelectedLang($lang['id']) ? 'selected' : '' ?>><?= htmlspecialchars($lang['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['languages'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['languages']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['bio']) ? 'field-error' : '' ?>">
            <label>Биография</label>
            <textarea name="bio"><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
            <?php if (isset($errors['bio'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['bio']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['contract']) ? 'field-error' : '' ?>">
            <label class="checkbox-label">
                <input type="checkbox" name="contract" <?= ($values['contract'] ?? false) ? 'checked' : '' ?>> С контрактом ознакомлен(а)
            </label>
            <?php if (isset($errors['contract'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['contract']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn">Сохранить изменения</button>
    </form>
</div>
</body>
</html>
<?php
}