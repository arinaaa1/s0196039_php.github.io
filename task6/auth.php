<?php
// auth.php – генерация логина/пароля, хэширование (bcrypt)
require_once __DIR__ . '/config.php';

function secureRandomString($alphabet, $length) {
    $max = strlen($alphabet) - 1;
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $alphabet[random_int(0, $max)];
    }
    return $result;
}

function generateLogin() {
    return secureRandomString(LOGIN_ALPHABET, LOGIN_LENGTH);
}

function generatePassword() {
    return secureRandomString(PASSWORD_ALPHABET, PASSWORD_LENGTH);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function checkPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>