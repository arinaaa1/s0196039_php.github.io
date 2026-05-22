<?php
// api.php – единая точка входа для REST API
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/db_functions.php';

// CORS и предварительные запросы
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function writeJSON($status, $data) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// POST /api.php (создание заявки)
if ($method === 'POST' && $action === '') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        writeJSON(400, ['error' => 'Неверный формат JSON']);
    }
    $error = validateAPIRequest($input);
    if ($error) {
        writeJSON(400, ['error' => $error]);
    }
    $appId = saveAPIApplication($pdo, $input);
    if (!$appId) {
        writeJSON(500, ['error' => 'Внутренняя ошибка сервера']);
    }
    $login = generateLogin();
    $password = generatePassword();
    $hash = hashPassword($password);
    if (!saveAPICredentials($pdo, $appId, $login, $hash)) {
        writeJSON(500, ['error' => 'Внутренняя ошибка сервера']);
    }
    writeJSON(200, [
        'success' => true,
        'login' => $login,
        'password' => $password,
        'profile_url' => '/web_backend/task8/edit.html' // соответствует edit.html
    ]);
}

// POST /api.php?action=login
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['login']) || !isset($input['password'])) {
        writeJSON(400, ['error' => 'Неверный формат JSON']);
    }
    $creds = findAPICredentialsByLogin($pdo, $input['login']);
    if (!$creds || !checkPassword($input['password'], $creds['passwordhash'])) {
        writeJSON(401, ['error' => 'Неверный логин или пароль']);
    }
    $token = generateJWT($creds['application_id'], $creds['login']);
    writeJSON(200, [
        'success' => true,
        'login' => $creds['login'],
        'token' => $token
    ]);
}

// GET /api.php?action=profile
if ($method === 'GET' && $action === 'profile') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/', $authHeader, $matches)) {
        writeJSON(401, ['error' => 'Требуется авторизация']);
    }
    $token = $matches[1];
    $payload = validateJWT($token);
    if (!$payload) {
        writeJSON(401, ['error' => 'Недействительный токен']);
    }
    $app = getAPIApplicationByID($pdo, $payload['application_id']);
    if (!$app) {
        writeJSON(500, ['error' => 'Внутренняя ошибка сервера']);
    }
    writeJSON(200, [
        'success' => true,
        'name' => $app['name'],
        'phone' => $app['phone'],
        'email' => $app['email'],
        'message' => $app['message']
    ]);
}

// PUT /api.php (обновление профиля)
if ($method === 'PUT' && $action === '') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/', $authHeader, $matches)) {
        writeJSON(401, ['error' => 'Требуется авторизация']);
    }
    $token = $matches[1];
    $payload = validateJWT($token);
    if (!$payload) {
        writeJSON(401, ['error' => 'Недействительный токен']);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        writeJSON(400, ['error' => 'Неверный формат JSON']);
    }
    $error = validateAPIUpdateRequest($input);
    if ($error) {
        writeJSON(400, ['error' => $error]);
    }
    if (!updateAPIApplication($pdo, $payload['application_id'], $input)) {
        writeJSON(500, ['error' => 'Внутренняя ошибка сервера']);
    }
    writeJSON(200, ['success' => true]);
}

// Если ничего не подошло
writeJSON(405, ['error' => 'Метод не поддерживается']);
?>