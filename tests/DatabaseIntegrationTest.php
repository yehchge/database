<?php

require __DIR__.'/../src/Database.php';

use PHPUnit\Framework\TestCase;
use yehchge\database\Database;
use PHPUnit\Framework\Attributes\DataProvider;

class DatabaseIntegrationTest extends TestCase {
    // private $db;

    /**
     * 每次測試前執行的初始化
     */
    public static function databaseProvider(): array {
        // 使用 SQLite
        $sqlite = new Database(':memory:', ':memory:');
        $sqlite->iQuery("CREATE TABLE test_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            status INTEGER
        )");

        // 使用 MySQL
        $mysql = new Database();
        $mysql->iQuery("CREATE TABLE IF NOT EXISTS test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            status INT
        )");
        $mysql->iQuery("TRUNCATE TABLE test_table");

        return [
            'SQLite 環境' => [$sqlite],
            'MySQL 環境'  => [$mysql],
        ];
    }

    /**
     * @dataProvider databaseProvider
     */
    #[DataProvider('databaseProvider')]
    public function testCompleteCRUD(Database $db) {
        // 1. Insert
        $insertData = ['name' => 'TestUser', 'status' => 1];
        $id = $db->bInsert('test_table', $insertData);
        $this->assertGreaterThan(0, $id);

        // 2. Read (Query & Fetch)
        $res = $db->iQuery("SELECT * FROM test_table WHERE id = ?", [$id]);
        $row = $db->aFetchAssoc($res);
        $this->assertIsArray($row);
        $this->assertEquals('TestUser', $row['name']);

        // 3. Update (注意：MySQL 成功後 rowCount 為 1，沒變動為 0)
        $updateData = ['name' => 'UpdatedUser'];
        $affectedRows = $db->bUpdate('test_table', ['id' => $id], $updateData);
        $this->assertEquals(1, $affectedRows);

        // 4. Verify Update
        $res = $db->iQuery("SELECT name FROM test_table WHERE id = ?", [$id]);
        $row = $db->aFetchAssoc($res);
        $this->assertEquals('UpdatedUser', $row['name']);
    }

    
    // public function testInsert() {
    //     $data = [
    //         'name' => 'Test User',
    //         'status' => 1
    //     ];

    //     $insertId = $this->db->bInsert('test_table', $data);

    //     $this->assertGreaterThan(0, $insertId);
    //     $this->assertEquals(1, $insertId);
    // }

    // /**
    //  * 測試 Query 與 Fetch 功能
    //  */
    // public function testQueryAndFetch() {
    //     // 先新增一筆
    //     $id = $this->db->bInsert('test_table', ['name' => 'Gemini', 'status' => 1]);
    //     $this->assertEquals(1, $id);

    //     // 執行查詢
    //     $res = $this->db->iQuery("SELECT * FROM test_table WHERE name = ?", ['Gemini']);
    //     $row = $this->db->aFetchAssoc($res);

    //     // 如果這裡失敗，請印出 $row 看看裡面是什麼
    //     if (!$row) {
    //         $check = $this->db->iQuery("SELECT count(*) as total FROM test_table")->fetch();
    //         fwrite(STDERR, "Total rows in table: " . $check['total'] . "\n");
    //     }

    //     // 斷言
    //     $this->assertIsArray($row);
    //     $this->assertEquals('Gemini', $row['name']);
    // }

    // /**
    //  * 測試 Update 功能
    //  */
    // public function testUpdate() {
    //     // 先新增
    //     $this->db->bInsert('test_table', ['name' => 'Old Name', 'status' => 0]);

    //     // 執行更新
    //     $where = ['id' => 1];
    //     $updateData = ['name' => 'New Name'];
    //     $this->db->bUpdate('test_table', $where, $updateData);

    //     // 驗證
    //     $res = $this->db->iQuery("SELECT name FROM test_table WHERE id = 1");
    //     $row = $this->db->aFetchAssoc($res);
    //     $this->assertEquals('New Name', $row['name']);
    // }

    // /**
    //  * 測試 SQL 報錯處理
    //  */
    // public function testQueryException() {
    //     $this->expectException(\PDOException::class);
    //     // 故意寫錯語法
    //     $this->db->iQuery("SELECT * FROM non_exists_table");
    // }


}