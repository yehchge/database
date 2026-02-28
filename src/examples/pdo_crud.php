<?php

// 簡單 PDO CRUD 範例（示範用途，實務請用 migration 與更完整錯誤處理）

declare(strict_types=1);

namespace yehchge\database\examples;

$dsn = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=test;charset=utf8mb4';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);

// 建表（僅示範）
$pdo->exec("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(200) UNIQUE)");

function createUser(PDO $pdo, string $name, string $email): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
        $stmt->execute([':name' => $name, ':email' => $email]);
        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function updateUserName(PDO $pdo, int $id, string $name): bool
{
    $stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id');
    return $stmt->execute([':name' => $name, ':id' => $id]);
}

function deleteUser(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

// 單次執行示範
if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
    $id = createUser($pdo, 'Alice', 'alice@example.com');
    echo "Created user id={$id}\n";

    $user = getUserByEmail($pdo, 'alice@example.com');
    echo "Fetched: " . json_encode($user) . "\n";

    updateUserName($pdo, $id, 'Alice A');
    echo "Updated name\n";

    deleteUser($pdo, $id);
    echo "Deleted user\n";
}
