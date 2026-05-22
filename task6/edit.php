<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/csrf.php';

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
    $pageData['csrf_token'] = getOrCreateCSRFToken();
    renderEdit($pageData, $login);
    exit;
}

if ($method === 'POST') {
    if (!validateCSRFToken()) {
        http_response_code(403);
        echo 'Request denied';
        exit;
    }

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
    $csrfToken = htmlspecialchars($pageData['csrf_token']);

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
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
        .topbar-user { color: #b34e72; }
        .topbar a {
            color: #d95580;
            text-decoration: none;
            font-weight: 500;
        }
        .card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
            border-radius: 2rem;
            box-shadow: 0 20px 35px -12px rgba(236, 72, 153, 0.12), 0 0 0 1px rgba(255, 245, 245, 0.7) inset;
            padding: 2.2rem 2rem;
            max-width: 680px;
            margin: 0 auto;
        }
        h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #c4456c, #b8315a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1.8rem;
        }
        .field { margin-bottom: 1.5rem; }
        .field > label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #9e4466;
            margin-bottom: 0.4rem;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #f3cdd8;
            border-radius: 1.2rem;
            background: #ffffffdd;
        }
        .field-error input { border-color: #e86c8c; background: #fff5f7; }
        .error-msg { font-size: 0.75rem; color: #d94a73; margin-top: 0.3rem; }
        .btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(95deg, #e47297, #d95580);
            color: white;
            border: none;
            border-radius: 2rem;
            cursor: pointer;
        }
        .btn:hover { transform: scale(0.98); }
        .success-banner {
            background: #fff0f3;
            border: 1.5px solid #e38aa8;
            border-radius: 1.2rem;
            padding: 0.9rem;
            color: #b6436a;
            text-align: center;
            margin-bottom: 1.8rem;
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
        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

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
?>