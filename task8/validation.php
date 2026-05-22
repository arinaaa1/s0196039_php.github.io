<?php
// validation.php – валидация полей заявки (аналог validateAPIRequest)
function validateAPIRequest($req) {
    $name = trim($req['name'] ?? '');
    if ($name === '') {
        return 'Имя обязательно для заполнения';
    }
    if (!preg_match('/^[\p{L} ]+$/u', $name)) {
        return 'Имя может содержать только буквы и пробелы';
    }

    $phone = trim($req['phone'] ?? '');
    if ($phone !== '' && !preg_match('/^\+?[0-9()\- ]{7,32}$/', $phone)) {
        return 'Некорректный телефон';
    }

    $email = trim($req['email'] ?? '');
    if ($email === '') {
        return 'Email обязателен для заполнения';
    }
    if (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
        return 'Email указан некорректно';
    }

    if (empty($req['consent'])) {
        return 'Необходимо согласие на обработку персональных данных';
    }
    return '';
}

function validateAPIUpdateRequest($req) {
    $name = trim($req['name'] ?? '');
    if ($name === '') {
        return 'Имя обязательно для заполнения';
    }
    if (!preg_match('/^[\p{L} ]+$/u', $name)) {
        return 'Имя может содержать только буквы и пробелы';
    }

    $phone = trim($req['phone'] ?? '');
    if ($phone !== '' && !preg_match('/^\+?[0-9()\- ]{7,32}$/', $phone)) {
        return 'Некорректный телефон';
    }

    $email = trim($req['email'] ?? '');
    if ($email === '') {
        return 'Email обязателен для заполнения';
    }
    if (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
        return 'Email указан некорректно';
    }
    return '';
}
?>