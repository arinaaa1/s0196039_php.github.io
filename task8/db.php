<?php
// db.php – подключение через DSN из переменной окружения
$dsn = getenv('DB_DSN');
if ($dsn === false) {
    http_response_code(500);
    die('DB_DSN environment variable is not set');
}

// DSN имеет формат "user:pass@tcp(host:port)/dbname"
// Преобразуем в стандартный PDO DSN для MySQL
if (preg_match('/^(?P<user>[^:]+):(?P<pass>[^@]+)@tcp\((?P<host>[^:]+):(?P<port>\d+)\)\/(?P<dbname>.+)$/', $dsn, $matches)) {
    $pdoDsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $matches['host'],
        $matches['port'],
        $matches['dbname']
    );
    $username = $matches['user'];
    $password = $matches['pass'];
} else {
    // fallback – использовать как есть (не рекомендуется, но для обратной совместимости)
    $pdoDsn = 'mysql:' . $dsn;
    $username = null;
    $password = null;
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($pdoDsn, $username, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
?>