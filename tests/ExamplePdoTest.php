<?php
declare(strict_types=1);

namespace yehchge\database\tests;

use PHPUnit\Framework\TestCase;
use PDO;

final class ExamplePdoTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE)');
    }

    public function testInsertAndFetch(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
        $this->assertTrue($stmt->execute([':name' => 'Bob', ':email' => 'bob@example.com']));

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => 'bob@example.com']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertEquals('Bob', $row['name']);
    }
}
