<?php
// jwt.php – ручная JWT (HS256)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cookies.php';

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJWT($applicationId, $login) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
        'application_id' => (int)$applicationId,
        'login' => $login,
        'exp' => time() + 86400 // 24 часа
    ];
    $headerB64 = base64url_encode(json_encode($header));
    $payloadB64 = base64url_encode(json_encode($payload));
    $signingInput = $headerB64 . '.' . $payloadB64;
    $signature = hash_hmac('sha256', $signingInput, JWT_SECRET, true);
    $signatureB64 = base64url_encode($signature);
    return $signingInput . '.' . $signatureB64;
}

function validateJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    list($headerB64, $payloadB64, $signatureB64) = $parts;
    $signingInput = $headerB64 . '.' . $payloadB64;
    $expectedSignature = hash_hmac('sha256', $signingInput, JWT_SECRET, true);
    $providedSignature = base64url_decode($signatureB64);
    if (!hash_equals($expectedSignature, $providedSignature)) return null;
    $payloadJson = base64url_decode($payloadB64);
    $payload = json_decode($payloadJson, true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return null;
    return [
        'application_id' => $payload['application_id'],
        'login' => $payload['login']
    ];
}

function getJWTFromCookie() {
    $token = getCookieValue('jwt_token');
    if ($token === null) return null;
    return validateJWT($token);
}

function setJWTCookie($token) {
    setPersistentCookie('jwt_token', $token);
}

function deleteJWTCookie() {
    deleteCookie('jwt_token');
}
?>