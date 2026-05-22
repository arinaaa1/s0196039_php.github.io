<?php
session_start();
require_once __DIR__ . '/db.php';

// ---------- Вспомогательные функции для работы с cookie ----------
function setSessionCookie($name, $value) {
    setcookie($name, $value, [
        'expires' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function setPersistentCookie($name, $value) {
    setcookie($name, $value, [
        'expires' => time() + 365 * 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function deleteCookie($name) {
    setcookie($name, '', [
        'expires' => 1,
        'path' => '/',
        'httponly' => true
    ]);
}

function getCookieValue($name) {
    return $_COOKIE[$name] ?? null;
}

function encodeToCookie($data) {
    return urlencode(json_encode($data));
}

function decodeFromCookie($encoded, &$target) {
    $decoded = urldecode($encoded);
    $data = json_decode($decoded, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $target = $data;
        return true;
    }
    return false;
}

function saveErrorsToCookie($errors, $formValues) {
    if (!empty($errors)) {
        setSessionCookie('form_errors', encodeToCookie($errors));
    }
    if (!empty($formValues)) {
        setSessionCookie('form_values', encodeToCookie($formValues));
    }
}

function saveSuccessToCookie($formValues) {
    setPersistentCookie('form_values', encodeToCookie($formValues));
    setSessionCookie('form_success', '1');
}

function loadFromCookies() {
    $data = [
        'values' => [],
        'errors' => [],
        'success' => false
    ];
    $rawValues = getCookieValue('form_values');
    if ($rawValues !== null) {
        decodeFromCookie($rawValues, $data['values']);
    }
    $rawErrors = getCookieValue('form_errors');
    if ($rawErrors !== null) {
        decodeFromCookie($rawErrors, $data['errors']);
        deleteCookie('form_errors');
    }
    if (getCookieValue('form_success') !== null) {
        $data['success'] = true;
        deleteCookie('form_success');
    }
    return $data;
}

// ---------- Данные для выпадающего списка языков ----------
$ALL_LANGUAGES = [
    ['id' => '1', 'name' => 'Pascal'], ['id' => '2', 'name' => 'C'],
    ['id' => '3', 'name' => 'C++'], ['id' => '4', 'name' => 'JavaScript'],
    ['id' => '5', 'name' => 'PHP'], ['id' => '6', 'name' => 'Python'],
    ['id' => '7', 'name' => 'Java'], ['id' => '8', 'name' => 'Haskell'],
    ['id' => '9', 'name' => 'Clojure'], ['id' => '10', 'name' => 'Prolog'],
    ['id' => '11', 'name' => 'Scala'], ['id' => '12', 'name' => 'Go'],
];

// ---------- Функция валидации (аналог validate.go) ----------
// Эмуляция mb_strlen, если расширение не установлено
if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = 'UTF-8') {
        return preg_match_all('/./us', $str, $matches);
    }
}

function validateFormData($post) {
    $data = [
        'name' => '', 'phone' => '', 'email' => '', 'birthdate' => '',
        'gender' => '', 'bio' => '', 'languages' => [], 'contract' => false
    ];
    $errors = [];

    // Name
    $name = trim($post['name'] ?? '');
    if ($name === '') {
        $errors['name'] = 'Name is required';
    } else {
        $len = mb_strlen($name, 'UTF-8');
        if ($len > 150) {
            $errors['name'] = 'Name must be at most 150 characters';
        } elseif (!preg_match('/^[\p{L} ]+$/u', $name)) {
            $errors['name'] = 'Name contains invalid characters';
        } else {
            $data['name'] = $name;
        }
    }

    // Phone
    $phone = trim($post['phone'] ?? '');
    if ($phone === '') {
        $errors['phone'] = 'Phone is required';
    } elseif (!preg_match('/^\+?[0-9()\- ]{7,32}$/', $phone)) {
        $errors['phone'] = 'Phone contains invalid characters';
    } else {
        $data['phone'] = $phone;
    }

    // Email
    $email = trim($post['email'] ?? '');
    if ($email === '') {
        $errors['email'] = 'Email is required';
    } elseif (strlen($email) > 255) {
        $errors['email'] = 'Email must be at most 255 characters';
    } elseif (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
        $errors['email'] = 'Email format is invalid, try name@domain.com';
    } else {
        $data['email'] = $email;
    }

    // Birthdate
    $birthdate = trim($post['birthdate'] ?? '');
    if ($birthdate === '') {
        $errors['birthdate'] = 'Birthdate is required';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$date || $date->format('Y-m-d') !== $birthdate) {
            $errors['birthdate'] = 'Birthdate format is invalid (expected YYYY-MM-DD)';
        } elseif ($date > new DateTime()) {
            $errors['birthdate'] = 'Birthdate cannot be in the future';
        } else {
            $data['birthdate'] = $birthdate;
        }
    }

    // Gender
    $gender = $post['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'], true)) {
        $errors['gender'] = "Gender must be 'male' or 'female'";
    } else {
        $data['gender'] = $gender;
    }

    // Languages
    $languages = $post['languages'] ?? [];
    if (!is_array($languages)) $languages = [];
    if (count($languages) === 0) {
        $errors['languages'] = 'At least one language must be selected';
    } else {
        $validIds = array_map('strval', range(1, 12));
        $allValid = true;
        foreach ($languages as $lang) {
            if (!in_array((string)$lang, $validIds, true)) {
                $errors['languages'] = 'Invalid language selection';
                $allValid = false;
                break;
            }
        }
        if ($allValid) {
            $data['languages'] = $languages;
        }
    }

    // Bio
    $bio = trim($post['bio'] ?? '');
    if ($bio === '') {
        $errors['bio'] = 'Bio is required';
    } else {
        $data['bio'] = $bio;
    }

    // Contract
    $contract = $post['contract'] ?? '';
    if ($contract === '') {
        $errors['contract'] = 'You must accept the contract';
    } elseif ($contract !== 'on') {
        $errors['contract'] = 'Invalid contract value';
    } else {
        $data['contract'] = true;
    }

    return [$data, $errors];
}

// ---------- Сохранение в БД (аналог db.go) ----------
function saveToDatabase($pdo, $data) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_accepted)
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, 1)
        ");
        $stmt->execute([
            ':full_name' => $data['name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birth_date' => $data['birthdate'],
            ':gender' => $data['gender'],
            ':biography' => $data['bio']
        ]);
        $appId = $pdo->lastInsertId();

        $langStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:app_id, :lang_id)");
        foreach ($data['languages'] as $langId) {
            $langStmt->execute([':app_id' => $appId, ':lang_id' => $langId]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('saveToDatabase error: ' . $e->getMessage());
        return false;
    }
}

// ---------- Рендеринг формы (встроенный шаблон из template.go) ----------
function renderForm($pageData, $languages) {
    $values = $pageData['values'];
    $errors = $pageData['errors'];
    $success = $pageData['success'];

    // Функция для проверки выбранного языка (аналог IsSelectedLang)
    $isSelectedLang = function($id) use ($values) {
        return in_array($id, $values['languages'] ?? []);
    };

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета</title>
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
            padding: 40px 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
            border-radius: 2rem;
            box-shadow: 0 20px 35px -12px rgba(236, 72, 153, 0.12), 0 0 0 1px rgba(255, 245, 245, 0.7) inset;
            padding: 2.2rem 2rem 2.5rem;
            width: 100%;
            max-width: 680px;
            transition: all 0.2s ease;
        }
        h1 {
            font-size: 2.1rem;
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
            text-align: left;
        }
        .field {
            margin-bottom: 1.5rem;
        }
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
        .field-error input:focus, .field-error select:focus, .field-error textarea:focus {
            border-color: #d9537a;
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
        .error-msg::before {
            content: "✦";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: #e85d8b;
            flex-shrink: 0;
        }
        textarea {
            height: 110px;
            resize: vertical;
        }
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
            text-transform: none;
            letter-spacing: 0;
        }
        input[type="radio"], input[type="checkbox"] {
            accent-color: #e36c92;
            width: 1rem;
            height: 1rem;
            margin: 0;
            cursor: pointer;
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
            box-shadow: 0 6px 14px rgba(217, 85, 128, 0.25);
        }
        .btn:active {
            transform: scale(0.97);
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
            .card { padding: 1.6rem; }
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
<div class="card">
    <h1>Анкета</h1>

    <?php if ($success): ?>
    <div class="success-banner">✅ Анкета успешно сохранена!</div>
    <?php endif; ?>

    <form action="form.php" method="POST">
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
                <?php foreach ($languages as $lang): ?>
                <option value="<?= htmlspecialchars($lang['id']) ?>" <?= $isSelectedLang($lang['id']) ? 'selected' : ?>><?= htmlspecialchars($lang['name']) ?></option>
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

        <button type="submit" class="btn">Сохранить</button>
    </form>
</div>
</body>
</html>
<?php
}

// ---------- Основная логика (handler) ----------
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET: загружаем данные из cookies и показываем форму
    $pageData = loadFromCookies();
    renderForm($pageData, $ALL_LANGUAGES);
    exit;
}

if ($method === 'POST') {
    // POST: валидация, сохранение, установка cookies, редирект
    list($formData, $errors) = validateFormData($_POST);

    if (!empty($errors)) {
        // Ошибки: сохраняем ошибки и отправленные значения в cookies, редирект на GET
        saveErrorsToCookie($errors, $formData);
        header('Location: form.php');
        exit;
    }

    // Нет ошибок: сохраняем в БД
    $saved = saveToDatabase($pdo, $formData);
    if (!$saved) {
        // Ошибка БД – показываем 500
        http_response_code(500);
        echo 'Internal server error. Try again later';
        exit;
    }

    // Успех: сохраняем данные в persistent cookie, success cookie и редирект
    saveSuccessToCookie($formData);
    header('Location: form.php');
    exit;
}

// Любой другой метод – 405
http_response_code(405);
echo 'Method is not allowed';
?>