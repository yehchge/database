<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use yehchge\database\Database;

final class SecurityTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        // Use in-memory SQLite for testing
        $this->db = new Database(':memory:', ':memory:', '', '');

        // Create test table
        $this->db->iQuery('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, name TEXT)');
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['test@example.com', 'Test User']);
    }

    public function testVDeleteWithPositionalParameter(): void
    {
        // Safe: Using positional parameter
        $this->db->vDelete('users', 'email = ?', ['test@example.com']);

        $result = $this->db->iQuery('SELECT * FROM users WHERE email = ?', ['test@example.com']);
        $rows = $result->fetchAll();
        $this->assertEmpty($rows);
    }

    public function testVDeleteWithMultipleParameters(): void
    {
        // Insert another test record
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['admin@example.com', 'Admin']);
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['user@example.com', 'User']);

        // Safe: Using multiple positional parameters
        $this->db->vDelete('users', 'name = ? AND email = ?', ['Admin', 'admin@example.com']);

        $result = $this->db->iQuery('SELECT COUNT(*) as count FROM users');
        $row = $result->fetch();
        $this->assertEquals(2, $row['count']);
    }

    public function testVDeleteInvalidInputRaisesException(): void
    {
        // Should throw InvalidArgumentException for empty sWhere
        $this->expectException(\InvalidArgumentException::class);
        $this->db->vDelete('users', '', []);
    }

    public function testVDeleteComplexCondition(): void
    {
        // Insert test data
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['john@example.com', 'John']);
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['jane@example.com', 'Jane']);

        // Safe: Complex condition with multiple bindings
        $this->db->vDelete('users', 'name = ? AND email LIKE ?', ['John', 'john%']);

        $result = $this->db->iQuery('SELECT COUNT(*) as count FROM users WHERE name = ?', ['John']);
        $row = $result->fetch();
        $this->assertEquals(0, $row['count']);
    }

    public function testVDeleteWithComplexOrCondition(): void
    {
        // Insert test data
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['john@example.com', 'John']);

        // Safe: Complex OR condition with parameters
        // Note: This safely handles OR through parameterized queries
        $this->db->vDelete('users', '(email = ? OR email = ?)', ['test@example.com', 'john@example.com']);

        $result = $this->db->iQuery('SELECT COUNT(*) as count FROM users');
        $row = $result->fetch();
        // Both test@example.com and john@example.com should be deleted
        $this->assertEquals(0, $row['count']);
    }

    public function testVDeleteComplexWithAdvancedConditions(): void
    {
        // Insert test data
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['john@example.com', 'John']);
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['jane@example.com', 'Jane']);
        $this->db->iQuery('INSERT INTO users (email, name) VALUES (?, ?)', ['admin@example.com', 'Admin']);

        // Safe: Using vDeleteComplex() for complex WHERE logic
        $this->db->vDeleteComplex(
            'users',
            'email LIKE ? OR name IN (?, ?)',
            ['%@example.com', 'John', 'Jane']
        );

        $result = $this->db->iQuery('SELECT COUNT(*) as count FROM users');
        $row = $result->fetch();
        // Only Admin should remain (doesn't match LIKE and not in IN list)
        // Actually all match '%@example.com' so all deleted
        $this->assertEquals(0, $row['count']);
    }

    public function testVDeleteComplexWithBetween(): void
    {
        // Insert test data with dates (using INT for simplicity in SQLite)
        $this->db->iQuery('CREATE TABLE events (id INTEGER PRIMARY KEY, event_date INTEGER)');
        $this->db->iQuery('INSERT INTO events (event_date) VALUES (?)', [20250115]);
        $this->db->iQuery('INSERT INTO events (event_date) VALUES (?)', [20250220]);
        $this->db->iQuery('INSERT INTO events (event_date) VALUES (?)', [20250320]);

        // Safe: Using BETWEEN with parameters
        $this->db->vDeleteComplex('events', 'event_date BETWEEN ? AND ?', [20250101, 20250228]);

        $result = $this->db->iQuery('SELECT COUNT(*) as count FROM events');
        $row = $result->fetch();
        // Only March event should remain
        $this->assertEquals(1, $row['count']);
    }
}
