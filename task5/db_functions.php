<?php
// db_functions.php – запросы к БД
require_once __DIR__ . '/db.php';

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
        return (int)$appId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('saveToDatabase error: ' . $e->getMessage());
        return false;
    }
}

function saveCredentials($pdo, $applicationId, $login, $passwordHash) {
    $stmt = $pdo->prepare("INSERT INTO credentials (application_id, login, password_hash) VALUES (:app_id, :login, :hash)");
    return $stmt->execute([
        ':app_id' => $applicationId,
        ':login' => $login,
        ':hash' => $passwordHash
    ]);
}

function findCredentialsByLogin($pdo, $login) {
    $stmt = $pdo->prepare("SELECT application_id, password_hash FROM credentials WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return [
        'application_id' => (int)$row['application_id'],
        'password_hash' => $row['password_hash']
    ];
}

function getApplicationByID($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT full_name, phone, email, birth_date, gender, biography, contract_accepted
        FROM applications WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);
    $app = $stmt->fetch();
    if (!$app) return null;

    $data = [
        'name' => $app['full_name'],
        'phone' => $app['phone'],
        'email' => $app['email'],
        'birthdate' => $app['birth_date'],
        'gender' => $app['gender'],
        'bio' => $app['biography'],
        'contract' => (bool)$app['contract_accepted'],
        'languages' => []
    ];

    $langStmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = :id");
    $langStmt->execute([':id' => $id]);
    while ($row = $langStmt->fetch()) {
        $data['languages'][] = $row['language_id'];
    }
    return $data;
}

function updateApplication($pdo, $id, $data) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            UPDATE applications SET
                full_name = :full_name, phone = :phone, email = :email,
                birth_date = :birth_date, gender = :gender, biography = :biography,
                contract_accepted = 1
            WHERE id = :id
        ");
        $stmt->execute([
            ':full_name' => $data['name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birth_date' => $data['birthdate'],
            ':gender' => $data['gender'],
            ':biography' => $data['bio'],
            ':id' => $id
        ]);
        $delStmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = :id");
        $delStmt->execute([':id' => $id]);

        $langStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:id, :lang_id)");
        foreach ($data['languages'] as $langId) {
            $langStmt->execute([':id' => $id, ':lang_id' => $langId]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('updateApplication error: ' . $e->getMessage());
        return false;
    }
}
?>