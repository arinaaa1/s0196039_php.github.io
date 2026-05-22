<?php
// db_functions.php – запросы к БД
require_once __DIR__ . '/db.php';

function saveAPIApplication($pdo, $req) {
    $stmt = $pdo->prepare("
        INSERT INTO applications_v2 (name, phone, email, message, contract_accepted)
        VALUES (:name, :phone, :email, :message, 1)
    ");
    $stmt->execute([
        ':name' => $req['name'],
        ':phone' => $req['phone'],
        ':email' => $req['email'],
        ':message' => $req['message']
    ]);
    return $pdo->lastInsertId();
}

function saveAPICredentials($pdo, $applicationId, $login, $passwordHash) {
    $stmt = $pdo->prepare("
        INSERT INTO credentials_v2 (application_id, login, password_hash)
        VALUES (:app_id, :login, :hash)
    ");
    return $stmt->execute([
        ':app_id' => $applicationId,
        ':login' => $login,
        ':hash' => $passwordHash
    ]);
}

function findAPICredentialsByLogin($pdo, $login) {
    $stmt = $pdo->prepare("
        SELECT application_id, password_hash, login
        FROM credentials_v2
        WHERE login = :login
    ");
    $stmt->execute([':login' => $login]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return [
        'application_id' => (int)$row['application_id'],
        'passwordhash' => $row['password_hash'],
        'login' => $row['login']
    ];
}

function getAPIApplicationByID($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT name, phone, email, message
        FROM applications_v2
        WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return [
        'name' => $row['name'],
        'phone' => $row['phone'],
        'email' => $row['email'],
        'message' => $row['message']
    ];
}

function updateAPIApplication($pdo, $id, $req) {
    $stmt = $pdo->prepare("
        UPDATE applications_v2
        SET name = :name, phone = :phone, email = :email, message = :message
        WHERE id = :id
    ");
    return $stmt->execute([
        ':name' => $req['name'],
        ':phone' => $req['phone'],
        ':email' => $req['email'],
        ':message' => $req['message'],
        ':id' => $id
    ]);
}
?>