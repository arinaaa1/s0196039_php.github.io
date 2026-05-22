<?php
// jwt.php – ручная JWT (HS256) без cookie, только для Bearer-токенов
function getJWTSecret() {
    $secret = getenv('JWT_SECRET');
    if ($secret === false) {
        http_response_code(500);
        die('JWT_SECRET environment variable is not set');
    }
    return $secret;
}

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
    $signature = hash_hmac('sha256', $signingInput, getJWTSecret(), true);
    $signatureB64 = base64url_encode($signature);
    return $signingInput . '.' . $signatureB64;
}

function validateJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    list($headerB64, $payloadB64, $signatureB64) = $parts;
    $signingInput = $headerB64 . '.' . $payloadB64;
    $expectedSignature = hash_hmac('sha256', $signingInput, getJWTSecret(), true);
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
?>