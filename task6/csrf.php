<?php
// csrf.php – CSRF-защита
require_once __DIR__ . '/cookies.php';

const CSRF_COOKIE_NAME = 'csrf_token';
const CSRF_FIELD_NAME = '_csrf';

function generateCSRFToken() {
    return bin2hex(random_bytes(32));
}

function getOrCreateCSRFToken($forceNew = false) {
    $token = getCookieValue(CSRF_COOKIE_NAME);
    if ($token === null || $forceNew) {
        $token = generateCSRFToken();
        setSessionCookie(CSRF_COOKIE_NAME, $token);
    }
    return $token;
}

function validateCSRFToken() {
    $cookieToken = getCookieValue(CSRF_COOKIE_NAME);
    if ($cookieToken === null) return false;
    $formToken = $_POST[CSRF_FIELD_NAME] ?? $_GET[CSRF_FIELD_NAME] ?? '';
    return hash_equals($cookieToken, $formToken);
}
?>