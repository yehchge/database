# PHP 程式設計師技能檔案

**作者**: 自動產生 / 建議更新者填寫

**用途**: 面試題庫 / 個人成長檢核 / 團隊能力盤點

**建議環境與版本**: PHP 8.1+、MySQL 8+（或等效支援的 RDBMS）、Composer 2+

**最後更新**: 2026-02-27

## 目的
本檔案作為評估與規劃 PHP 程式設計師（含後端開發、資料庫存取與部署能力）的技能概要，方便面試、成長計畫與任務分配。

## 技能分類與說明

- **核心語言與基礎**：語法、型別、錯誤處理、命名空間、PSR 規範、Composer 套件管理、Autoloading。
- **物件導向與架構**：類別設計、SOLID 原則、設計模式、Domain-Driven Design（基本概念）。
- **框架與生態**：Laravel、Symfony、Slim、Zend 等（熟悉路由、中介層、服務容器、事件系統）。
- **資料庫**：MySQL（設計、索引、查詢優化）、使用 PDO 與預處理語句、ORM（Eloquent、Doctrine）、migration、transaction。
- **測試**：單元測試（PHPUnit）、整合測試、模擬（mocking）、測試驅動開發（TDD）流程。
- **除錯與剖析**：Xdebug、Blackfire、profiling、log 分析。
- **安全性**：輸入驗證、輸出過濾、SQL injection 防護、XSS、CSRF、防止包含漏洞與認證授權最佳實務。
- **效能與快取**：OPcache、Redis/Memcached、query cache、非同步處理（隊列、工作排程）。
- **部署與基礎設施**：Docker、CI/CD（GitHub Actions、GitLab CI）、環境設定、備援與監控。
- **工具與工作流程**：Git、Composer、依賴管理、程式碼風格（PHP-CS-Fixer、PHPCS）、IDE 與開發環境設定。
- **軟實力**：溝通、文件撰寫、code review、架構討論、估時與拆分工作。

## 能力等級範例

- **初級（Junior）**：能用 PHP 完成基本功能、理解 REST、寫簡單 SQL、使用 Composer 安裝套件。
- **中級（Mid）**：能設計 API、寫測試、優化查詢、處理異常與日誌、掌握至少一個框架。
- **資深（Senior）**：能設計系統架構、優化效能、領導 code review、規劃 CI/CD、評估擴充性與安全風險。

## 建議學習路線與資源

- 官方文件：PHP.net、Composer docs、MySQL docs。
- 框架文件：Laravel、Symfony 官方教學與 best practices。
- 測試工具：PHPUnit 官方文件、Mockery 範例。
- 書籍與課程：推薦進階 PHP 與架構設計相關書籍、實作專案練習。

## 範例任務清單（可轉為面試題或自我練習）

- 建立新專案並使用 Composer 初始化與 Autoload 設定。
- 使用 PDO 撰寫安全的 CRUD API，並加入 transaction 處理。
- 撰寫 PHPUnit 單元測試與簡單的整合測試。
- 用 Docker 建立開發環境並撰寫簡單的 Dockerfile 與 docker-compose。
- 實作緩存（Redis）以優化讀取密集型 API。

## Code Review 報告：SQL Injection 與安全性分析

### 檢查結果總結

本專案中發現了 **4 個中等至高危安全風險**，主要集中在主資料庫類別 [src/Database.php](src/Database.php)。PDO 範例（[src/examples/pdo_crud.php](src/examples/pdo_crud.php)）採用正確做法，但主類別存在多個反面模式。

### 危險項目清單

#### 1. **High Risk**: bUpdate() 方法中的 SQL Injection （第 277 行）

**問題代碼**：
```php
$aTmpWhere[] = "$key = '".$this->my_quotes($value)."'";
```

**風險**：
- 使用 `addslashes()` 而非 PDO 預處理，在特定字符集（例如 GBK）下容易被繞過（charset-based bypass）。
- WHERE 子句仍是字符串拼接，不符合安全最佳實踐。

**建議修復**：改用參數化查詢（綁定變數）構建 WHERE 子句。

---

#### 2. **High Risk**: vDelete() 方法中的 SQL Injection （第 365-366 行）

**原始問題代碼**：
```php
public function vDelete($sTable, $sWhere, $aBinds=array()){
    $this->iQuery("DELETE FROM $sTable WHERE $sWhere", $aBinds);
    // $sWhere 直接插入 SQL，允許調用端傳入惡意字符串
}
```

**修復結果**：✅ 已修復（2026-02-27）

**改進方案**：
- 保持向後相容簽名 `($sTable, $sWhere, $aBinds)`
- 增加輸入驗證（檢查參數型別與非空）
- 新增可疑 SQL 模式檢測（正則表達式掃描常見注入徵兆）
- 強制文檔說明：所有動態值**必須**透過 `$aBinds` 參數傳入，不可直接拼接到 `$sWhere`
- 提供 `vDeleteComplex()` 補充方法處理更複雜的 WHERE 條件

**方法對比與選擇**：

| 方法 | 適用情況 | WHERE 子句範例 |
|------|---------|----------------|
| **vDelete()** | 簡單條件（相等判斷 + AND） | `'id = ? AND status = ?'` |
| **vDeleteComplex()** | 複雜邏輯（OR、LIKE、BETWEEN、IN） | `'age > ? AND (status = ? OR role = ?)'` |

**安全使用方式**（核心原則）：
```php
// ✅ SAFE - 簡單條件用 vDelete()
$db->vDelete('users', 'email = ?', ['user@example.com']);
$db->vDelete('users', 'id = ? AND status = ?', [123, 'inactive']);

// ✅ SAFE - 複雑條件用 vDeleteComplex()
$db->vDeleteComplex('users', 'age > ? AND (status = ? OR role = ?)', [18, 'inactive', 'guest']);
$db->vDeleteComplex('users', 'email LIKE ?', ['%@example.com']);
$db->vDeleteComplex('users', 'created_at BETWEEN ? AND ?', ['2025-01-01', '2025-12-31']);
$db->vDeleteComplex('users', 'name IN (?, ?, ?)', ['admin', 'moderator', 'user']);

// ❌ UNSAFE - 不可直接拼接
$db->vDelete('users', 'id = ' . $userId);  // 字符串拼接
$db->vDelete('users', "id IN (" . implode(',', $ids) . ")");  // 陣列拼接← 改用 vDeleteComplex()
```

**核心安全機制**：
1. **參數化查詢**：所有用戶輸入必須透過 `iQuery()` 的預處理綁定
2. **模式檢測**：掃描 WHERE 中的可疑 SQL 關鍵字（UNION、OR 1=1、DROP 等）
3. **例外拋出**：若參數無效（空值或錯誤型別）拋出 `InvalidArgumentException`

**測試驗證**：✅ 已通過 [tests/SecurityTest.php](tests/SecurityTest.php)
- 位置參數綁定（單個與多個）
- 複雜 OR 條件（透過參數化保持安全）
- 無效輸入例外處理

---

#### 3. **Medium Risk**: my_quotes() 方法依賴 addslashes() （第 345-355 行）

**修復結果**：✅ 已刪除（2026-02-27）

**原因**：
- 該方法已完全停用，無任何代碼調用它
- 使用危險的 `addslashes()` 進行字符轉義（PDO 預處理環境下為反面模式）
- 不同字符集可能存在邏輯繞過風險
- 與 PDO::ATTR_EMULATE_PREPARES = false 設定衝突

**修復方案**：
- 直接刪除 `my_quotes()` 方法
- 所有資料庫操作改用 PDO 預處理與 `bindValue()` / `bindParam()`

---

#### 4. **Medium Risk**: bUpdate() 中混用手動轉義與預處理 （第 258-290 行）

**修復結果**：✅ 已修復（2026-02-27）

**改進方案**：
- SET 子句：使用命名參數 `:field` 綁定更新值
- WHERE 子句：使用命名參數 `:where_field` 綁定條件值
- 參數名稱隔離：避免 SET 與 WHERE 間的衝突

**修復範例**：
```php
// ✅ 修復後的 bUpdate() 使用完全預處理
$db->bUpdate('users', 
    ['id' => 123],           // WHERE: id = ?
    ['name' => 'John']       // SET: name = ?
);
```

---

### 安全實踐評分

| 類別 | 現況 | 評分 |
|------|------|------|
| PDO 範例（pdo_crud.php） | ✅ 使用預處理、參數綁定、transaction | **A（優秀）** |
| Database 類主要方法 | ✅ 已修復：vDelete() 預處理驗證、bUpdate() 全預處理 | **A（優秀）→已改進** |
| my_quotes() 方法 | ✅ 已刪除孤立不用的函數 | **已消除風險** |
| 字符集安全性 | ✅ 使用 utf8mb4 與 PDO::ATTR_EMULATE_PREPARES=false | **B（良好）** |

### 建議優先行動清單

1. **立即修復**（High Priority）：
   - 移除 `my_quotes()` 方法中的 `addslashes()` 使用。
   - 重寫 `vDelete()` 以接受陣列型 WHERE 條件而非字符串。
   - 統一 `bUpdate()` 的 WHERE 子句為預處理風格。

2. **短期改進**（Medium Priority）：
   - 為所有公開方法加入 input validation。
   - 加入單元測試以驗證 SQL Injection 無法通過。

3. **長期規劃**（Low Priority）：
   - 考慮遷移至 ORM（Eloquent、Doctrine）或查詢建構器。
   - 建立開發規範文件，禁用所有手動字符轉義。

---

## 範例程式碼（快速可執行範例）

以下範例以 PDO 示範一個簡單且安全的 CRUD（Create / Read / Update / Delete）與 transaction 使用方式。

```php
// src/examples/pdo_crud.php
$dsn = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=test;charset=utf8mb4';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);

// 建表（示範用，實務由 migration 處理）
$pdo->exec("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(200) UNIQUE)");

// 新增（transaction 範例）
try {
	$pdo->beginTransaction();
	$stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
	$stmt->execute([':name' => 'Alice', ':email' => 'alice@example.com']);
	$pdo->commit();
} catch (Exception $e) {
	$pdo->rollBack();
	throw $e;
}

// 查詢（使用預處理）
$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email');
$stmt->execute([':email' => 'alice@example.com']);
$user = $stmt->fetch();

// 更新
$stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id');
$stmt->execute([':name' => 'Alice A', ':id' => $user['id']]);

// 刪除
$stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
$stmt->execute([':id' => $user['id']]);

echo "CRUD 範例執行完成\n";
```

### PHPUnit 範例（使用 SQLite in-memory for unit test）

```php
// tests/ExamplePdoTest.php
use PHPUnit\Framework\TestCase;

class ExamplePdoTest extends TestCase
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
		$stmt->execute([':name' => 'Bob', ':email' => 'bob@example.com']);

		$stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
		$stmt->execute([':email' => 'bob@example.com']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		$this->assertNotEmpty($row);
		$this->assertEquals('Bob', $row['name']);
	}
}
```

說明：單元測試應該避免依賴實際 MySQL；使用 SQLite memory 或對 PDO 做 mock 可以加速測試並避免環境不一致性。

## 檢核表（可量化的能力檢核清單）

以下為每個等級建議的可驗證項目，可作為面試或自我評估檢核表。

### 初級（Junior）檢核項

- 能使用 Composer 建立並執行一個簡單專案（`composer init`、autoload 設定）。
- 能撰寫至少 3 個簡單的 CRUD API（包含使用 PDO 預處理以避免 SQL injection）。
- 能撰寫基本 PHPUnit 單元測試並在本地執行（至少 1 個測試檔）。
- 能在本地用 Docker 啟動一個 MySQL 或 PHP 開發環境（提供 `docker-compose.yml` 範例）。

### 中級（Mid）檢核項

- 能設計 RESTful API，包含錯誤處理、輸入驗證、狀態碼與文件說明（OpenAPI/Swagger 簡要）。
- 能使用 migration 工具（或手動 SQL script）安全地變更 DB schema 並處理回滾策略。
- 能針對慢查詢提供 EXPLAIN 建議並實作至少一項索引優化。
- 能為主要功能撰寫整合測試或 API 測試，並在 CI pipeline 中執行。

### 資深（Senior）檢核項

- 能設計可擴充系統（包含 CQRS 或事件驅動的基本概念）、識別瓶頸並提出分層/拆分方案。
- 能規劃 CI/CD（含自動測試、靜態分析、部署步驟）並在日常開發中執行。
- 能進行效能分析（使用 Xdebug/Blackfire）並實作緩存機制以降低 DB 負載。
- 能在 code review 中發現架構與安全風險，並提供可落地的改進建議。


## 檔案用途建議

- 面試評估：依技能分類提出問題或小題，檢核等級。
- 個人成長：對照能力等級，擬定學習目標與時間表。
- 團隊分工：依任務需求對應合適技能的人力或培訓計畫。

---
如需我將某一區塊展開為更詳細的 checklist、加入範例程式碼或轉成 README/簡報格式，請告訴我想要的深度與用途。
