<?php
// validation.php – валидация данных анкеты
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
?>