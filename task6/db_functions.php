<?php
// db_functions.php – запросы к БД (общие + для админки)
require_once __DIR__ . '/db.php';

// --- Общие функции (из task5) ---
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

// --- Административные функции ---
function getAllApplications($pdo) {
    $stmt = $pdo->query("
        SELECT id, full_name, phone, email, birth_date, gender, biography, contract_accepted
        FROM applications ORDER BY id DESC
    ");
    $apps = [];
    while ($row = $stmt->fetch()) {
        $app = [
            'id' => $row['id'],
            'name' => $row['full_name'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'birthdate' => $row['birth_date'],
            'gender' => $row['gender'],
            'bio' => $row['biography'],
            'contract' => (bool)$row['contract_accepted'],
            'languages' => []
        ];
        $langStmt = $pdo->prepare("
            SELECT pl.name FROM application_languages al
            JOIN programming_languages pl ON al.language_id = pl.id
            WHERE al.application_id = ?
        ");
        $langStmt->execute([$row['id']]);
        while ($langRow = $langStmt->fetch()) {
            $app['languages'][] = $langRow['name'];
        }
        $apps[] = $app;
    }
    return $apps;
}

function deleteApplication($pdo, $id) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM credentials WHERE application_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('deleteApplication error: ' . $e->getMessage());
        return false;
    }
}

function getLanguageStats($pdo) {
    $stmt = $pdo->query("
        SELECT pl.name, COUNT(al.application_id) as count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id, pl.name
        ORDER BY count DESC
    ");
    $stats = [];
    while ($row = $stmt->fetch()) {
        $stats[] = ['name' => $row['name'], 'count' => (int)$row['count']];
    }
    return $stats;
}

function getAdminByLogin($pdo, $login) {
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_credentials WHERE login = ?");
    $stmt->execute([$login]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return $row['password_hash'];
}
?>