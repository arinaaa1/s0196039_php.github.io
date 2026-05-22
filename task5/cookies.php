<?php
// cookies.php – работа с cookie и кодирование JSON
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
?>