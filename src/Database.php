<?php

/**
 * MySQL class PDO 版本
 */

declare(strict_types=1);

namespace yehchge\database;

class Database
{
    // Variables
    private $m_sHost = "";
    private $m_sUser = "";
    private $m_sPass = "";
    private $m_sDb   = "";
    private $m_sPort = '3306';
    private $m_iDbh  = 0;
    private $m_iRs   = 0;
    private $m_character = "utf8";
    private $dsn     = '';

    //used to control nested transaction(for nested classes' functions)
    private $iTransactionLayer = 0;

    /**
     * 連線資料庫
     * @param string $sDb   Database name
     * @param string $sHost Server IP or DNS name
     * @param string $sUser MySQL User
     * @param string $sPass MySQL Password
     * @param string $sPort MySQL Port
     */
    public function __construct($sDb = '', $sHost = '', $sUser = '', $sPass = '', $sPort = '')
    {
        $this->m_sHost = defined('_MYSQL_HOST') ? _MYSQL_HOST : null;
        $this->m_sUser = defined('_MYSQL_USER') ? _MYSQL_USER : null;
        $this->m_sPass = defined('_MYSQL_PASS') ? _MYSQL_PASS : null;
        $this->m_sDb = defined('_MYSQL_DB') ? _MYSQL_DB : null;
        $this->m_sPort = defined('_MYSQL_PORT') ? _MYSQL_DB : null;

        if ($sDb) {
            $this->m_sDb = $sDb;
        }
        if ($sHost) {
            $this->m_sHost = $sHost;
        }
        if ($sUser) {
            $this->m_sUser = $sUser;
        }
        if ($sPass) {
            $this->m_sPass = $sPass;
        }
        if ($sPort) {
            $this->m_sPort = $sPort;
        }

        if (!$this->m_iDbh) {
            $this->vConnect();
        }
    }

    public function __destruct()
    {
        $this->m_iDbh = null;

        if ($this->iTransactionLayer !== 0) {
            $sLogicErrorMsg = "vBegin & vCommit's quantity do not match on database: {$this->m_sDb}!";
            die($sLogicErrorMsg);
        }
    }

    public static function oDB($sDBName)
    {
        $port = defined('_' . $sDBName . '_PORT') ? constant('_' . $sDBName . '_PORT') : '3306';
        $aDB[$sDBName] = new self(
            constant('_' . $sDBName . '_DB'),
            constant('_' . $sDBName . '_HOST'),
            constant('_' . $sDBName . '_USER'),
            constant('_' . $sDBName . '_PASS'),
            $port
        );

        return $aDB[$sDBName];
    }

    /**
     * 設定 MySQL 連結為 UTF-8
     * @created 2014/11/14
     */
    public function bSetCharacter($encode = 'utf8')
    {
        // mysqli_set_charset($this->m_iDbh, $encode);
        $this->iQuery("SET character_set_client = '$encode'");
        $this->iQuery("SET character_set_results = '$encode'");
        $this->iQuery("SET character_set_connection = '$encode'");
    }

    /**
     * 連線資料庫
     * @return void
     */
    public function vConnect()
    {
        // 判斷是否為 SQLite 記憶體模式
        if ($this->m_sHost === ':memory:' || $this->m_sDb === ':memory:') {
            $this->dsn = "sqlite::memory:";
        } else {
            $this->dsn = "mysql:host={$this->m_sHost};port={$this->m_sPort};dbname={$this->m_sDb};charset={$this->m_character}";
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // PDO::FETCH_ASSOC, FETCH_FUNC
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $user = $this->m_sUser;
        $pass = $this->m_sPass;

        try {
            if (strpos($this->dsn, 'sqlite:') === 0) {
                $this->m_iDbh = new \PDO($this->dsn, null, null, $options);
            } else {
                $this->m_iDbh = new \PDO($this->dsn, $user, $pass, $options);
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * 關閉資料庫
     * @return void
     */
    public function vClose()
    {
        $this->m_iDbh = null;
    }

    /**
     * query db
     * @param  string $sSql   SQL語法
     * @param  array  $aBinds 綁定的資料
     * @return \PDOStatement returns value of variable $m_iRs
     */
    public function iQuery($sSql, $aBinds = array())
    {
        $i = 0;

        try {
            $this->m_iRs = $this->m_iDbh->prepare($sSql); // Returns a PDOStatement object

            foreach ($aBinds as $key => $value) {
                $this->m_iRs->bindValue($key + 1, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            }

            $this->m_iRs->execute();
        } catch (\PDOException $e) {
            throw new \PDOException("\nSQL Error: $sSql\n" . $e->getMessage());
        }

        return $this->m_iRs;
    }

    /**
    * 取得 sql 結果比數
    * @param $iRs resource result
    * @return int Get number of rows in result
    */
    public function iNumRows($iRs = 0)
    {
        if ($iRs) {
            $iTmpRs = $iRs;
        } else {
            $iTmpRs = $this->m_iRs;
        }
        if (!$iTmpRs) {
            return 0;
        }
        // return $iTmpRs->rowCount(); // for MySQL
        $results = $iTmpRs->fetchAll(\PDO::FETCH_ASSOC);
        $count = count($results);
        return $count;
    }

    /**
    * 取得 sql 結果
    * @param $iRs resource result
    * @return array Fetch a result row as an associative array, a numeric array, or both.
    */
    public function aFetchAssoc($iRs = 0)
    {
        if (!$this->m_iRs && !$iRs) {
            return [];
        }

        if ($iRs) {
            $iTmpRs = $iRs;
        } else {
            $iTmpRs = $this->m_iRs;
        }
        return   $iTmpRs->fetch(\PDO::FETCH_ASSOC);
    }

    public function aFetchArray($iRs = 0)
    {
        return $this->aFetchAssoc($iRs);
    }

    /**
    * 取得 insert 後的自動流水號
    * @return int Get the ID generated from the previous INSERT operation
    */
    public function iGetInsertId()
    {
        if (!$this->m_iRs) {
            return 0;
        }
        return $this->m_iDbh->lastInsertId();
    }

    /**
     * insert into table
     *
     * @param string $sTable The table name
     * @param array  $aBinds The add data array
     * @return int The ID generated from the previous INSERT operation
     */
    public function bInsert($sTable, $aBinds)
    {
        if (!is_array($aBinds)) {
            return 0;
        }

        $sSql = "INSERT INTO $sTable ";
        $aField = array_keys($aBinds);
        $sSql .= '(' . implode(",", $aField) . ')';
        $sSql .= 'VALUES(:' . implode(", :", $aField) . ')';
        $this->m_iRs = $this->m_iDbh->prepare($sSql);
        foreach ($aBinds as $bindKey => $value) {
            $this->m_iRs->bindValue(":$bindKey", $value);
        }

        try {
            $this->m_iRs->execute();
            $insertId = $this->iGetInsertId();
            $this->m_iRs->closeCursor();
            return $insertId;
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * insert into table
     *
     * @deprecated 於版本 2.0 棄用，請改用 bInsert()
     *             此方法會回傳 SQL 字串，之後會移除。
     * @param string $sTable db table
     * @param array  $aBinds 欄位 => 值
     * @return string 執行的 SQL；失敗回傳空字串
     */
    public function sInsert($sTable, $aBinds)
    {
        // 可選：在執行時觸發使用警告，幫助開發期發現
        trigger_error(
            __METHOD__ . ' is deprecated, use ' . __CLASS__ . '::bInsert() instead',
            E_USER_DEPRECATED
        );

        if (!is_array($aBinds)) {
            return '';
        }

        $sSql = "INSERT INTO $sTable ";
        $aField = array_keys($aBinds);
        $sSql .= '(' . implode(",", $aField) . ')';
        $sSql .= 'VALUES(:' . implode(", :", $aField) . ')';
        $this->m_iRs = $this->m_iDbh->prepare($sSql);
        foreach ($aBinds as $bindKey => $value) {
            $this->m_iRs->bindValue(":$bindKey", $value);
        }

        try {
            $this->m_iRs->execute();
            $this->m_iRs->closeCursor();
            return $sSql;
        } catch (\PDOException $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * update table
     *
     * @param string $sTable The table name
     * @param array  $aWhere The where data array (will be used with prepared statements for safety)
     * @param array  $aBinds The update data array
     * @return int The number of affected rows
     */
    public function bUpdate($sTable, $aWhere, $aBinds)
    {
        if (!is_array($aBinds)) {
            return 0;
        }

        $aField = array_keys($aBinds);
        $aWhereField = $aWhere ? array_keys($aWhere) : [];

        // Build SET clause with named parameters
        $setClause = [];
        foreach ($aField as $field) {
            $setClause[] = "`" . $field . "` = :" . $field;
        }

        $sSql = "UPDATE `" . $sTable . "` SET " . implode(", ", $setClause);

        // Build WHERE clause with named parameters (safe from SQL injection)
        if ($aWhere && is_array($aWhere) && count($aWhere) > 0) {
            $whereClause = [];
            foreach ($aWhereField as $key) {
                $whereClause[] = "`" . $key . "` = :where_" . $key;
            }
            $sSql .= " WHERE " . implode(" AND ", $whereClause);
        }

        try {
            $this->m_iRs = $this->m_iDbh->prepare($sSql);

            // Bind SET values
            foreach ($aBinds as $bindKey => $value) {
                $this->m_iRs->bindValue(":" . $bindKey, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            }

            // Bind WHERE values with distinct parameter names to avoid conflicts
            if ($aWhere && is_array($aWhere)) {
                foreach ($aWhere as $key => $value) {
                    $this->m_iRs->bindValue(":where_" . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                }
            }

            $this->m_iRs->execute();
            $iAffectedRows = $this->m_iRs->rowCount();
            $this->m_iRs->closeCursor();

            return $iAffectedRows;
        } catch (\PDOException $ex) {
            throw new \PDOException($ex->getMessage());
        }
    }

    /**
     * update table
     *
     * @deprecated 於版本 2.0 棄用，請改用 bUpdate()
     *             此方法會回傳 SQL 字串，之後會移除。
     * @param string $sTable The table name
     * @param array  $aBinds The update data array
     * @param string $sWhere The where condition
     * @return string The SQL string if successful, empty string on failure
     */
    public function sUpdate($sTable, $aBinds, $sWhere)
    {
        // 可選：在執行時觸發使用警告，幫助開發期發現
        trigger_error(
            __METHOD__ . ' is deprecated, use ' . __CLASS__ . '::bUpdate() instead',
            E_USER_DEPRECATED
        );

        if (!is_array($aBinds)) {
            return '';
        }
        $aField = array_keys($aBinds);

        $sSql = "UPDATE $sTable SET ";
        for ($i = 0; $i < count($aField); $i++) {
            $sSql .= "`" . $aField[$i] . "`=:" . $aField[$i];
            if (($i + 1) != count($aField)) {
                $sSql .= ",";
            }
        }
        if ($sWhere) {
            $sSql .= " WHERE " . $sWhere;
        }

        try {
            $this->m_iRs = $this->m_iDbh->prepare($sSql);

            foreach ($aBinds as $bindKey => $value) {
                $this->m_iRs->bindValue(":$bindKey", $value, \PDO::PARAM_STR | \PDO::PARAM_INT);
            }

            $this->m_iRs->execute();
            // $iAffectedRows = $this->m_iRs->rowCount();
            $this->m_iRs->closeCursor();
            return $sSql;
        } catch (\PDOException $ex) {
            throw new \PDOException($ex->getMessage());
        }
    }

    /**
     * 刪除零寬字元
     * Replace non-breaking spaces with normal spaces
     *
     * @param $str
     * @return string
     */
    public static function removeNbsp($str)
    {
        if (is_string($str)) {
            $str = trim($str);
            $str = preg_replace('/[\x{00A0}\x{2002}\x{2003}\x{2004}\x{2005}\x{2006}\x{2007}\x{2008}\x{2009}\x{200A}\x{202F}\x{205F}\x{3000}]/isu', ' ', $str);
            $str = preg_replace('/[\x{00AD}\x{180E}\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/isu', '', $str);
        }
        return $str;
    }

    /**
     * delete data from target table with SQL injection protection
     *
     * @param string $sTable The table name
     * @param string $sWhere The WHERE clause with placeholders (e.g., "id = ? AND status = ?")
     * @param array  $aBinds The values to bind (indexed array matching ? placeholders in order)
     * @return void
     *
     * SECURITY NOTICE:
     * This method is safe from SQL injection ONLY if:
     * 1. $sWhere contains ONLY the WHERE condition template with ? placeholders
     * 2. ALL user input and dynamic values are passed through $aBinds
     * 3. Do NOT concatenate values directly into $sWhere
     * 4. $aBinds should be an indexed array with values in the same order as ? placeholders
     *
     * ✅ SAFE examples:
     * $db->vDelete('users', 'id = ?', [123]);
     * $db->vDelete('users', 'id = ? AND status = ?', [123, 'inactive']);
     * $db->vDelete('users', 'email LIKE ?', ['%@example.com']);
     * $db->vDelete('users', 'age > ? AND (status = ? OR role = ?)', [18, 'inactive', 'guest']);
     *
     * ❌ UNSAFE examples (DO NOT USE):
     * $db->vDelete('users', 'id = ' . $userId);  // ❌ Direct concatenation
     * $db->vDelete('users', "status = '{$status}'");  // ❌ String interpolation
     * $db->vDelete('users', "id IN (" . implode(',', $ids) . ")");  // ❌ Array concatenation
     * $db->vDelete('users', "email = '{$email}' OR id = 1");  // ❌ Always dangerous
     */
    public function vDelete($sTable, $sWhere, $aBinds = array())
    {
        // Validate inputs
        if (empty($sTable) || !is_string($sTable)) {
            throw new \InvalidArgumentException("sTable must be a non-empty string");
        }
        if (empty($sWhere) || !is_string($sWhere)) {
            throw new \InvalidArgumentException("sWhere must be a non-empty string");
        }
        if (!is_array($aBinds)) {
            throw new \InvalidArgumentException("aBinds must be an array");
        }

        // Security warning: Check for suspicious patterns that might indicate direct concatenation
        // This is NOT a complete protection, but helps catch common mistakes
        $suspiciousPatterns = [
            '/\bunion\b/i',           // UNION keyword (often used in injection attacks)
            '/;\s*(drop|delete|insert|update|create|alter)\b/i',  // Stacked queries
            "/'\s*(or|and)\s*'/i",    // Quote patterns like ' OR '
            "/\b(or|and)\s+1\s*=\s*1/i",  // OR 1=1 pattern
            "/['\"]\s*\)/i",  // Closing quote and paren (query termination attempt)
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sWhere)) {
                // Log warning but still allow execution (for backwards compatibility)
                // In production, this could trigger an alert or block the query
                trigger_error(
                    "vDelete: Suspicious SQL pattern detected in WHERE clause. " .
                    "Ensure all dynamic values are passed through aBinds, not concatenated into sWhere. " .
                    "Query: DELETE FROM {$sTable} WHERE {$sWhere}",
                    E_USER_WARNING
                );
            }
        }

        $sSql = "DELETE FROM `" . $sTable . "` WHERE " . $sWhere;

        try {
            $this->iQuery($sSql, $aBinds);
            if (!$this->m_iRs) {
                throw new \Exception("CDbShell->vDelete: fail to delete data in $sTable");
            }
            $this->m_iRs->closeCursor();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * delete data from target table with complex WHERE conditions
     *
     * Use this method when you need complex WHERE logic (OR, comparison operators, LIKE, BETWEEN, etc.)
     * All dynamic values MUST be passed through $aBinds to prevent SQL injection
     *
     * @param string $sTable The table name
     * @param string $sWhereClause The WHERE clause with ? placeholders (e.g., "age > ? AND (status = ? OR role = ?)")
     * @param array  $aBinds The values to bind (indexed array matching ? placeholders in order)
     * @return void
     *
     * ✅ SAFE examples:
     * $db->vDeleteComplex('users', 'age > ? AND (status = ? OR role = ?)', [18, 'inactive', 'guest']);
     * $db->vDeleteComplex('users', 'email LIKE ?', ['%@example.com']);
     * $db->vDeleteComplex('users', 'created_at BETWEEN ? AND ?', ['2025-01-01', '2025-12-31']);
     * $db->vDeleteComplex('users', 'name IN (?, ?, ?)', ['admin', 'moderator', 'user']);
     *
     * ❌ UNSAFE examples (DO NOT USE):
     * $db->vDeleteComplex('users', 'id IN (' . implode(',', $ids) . ')', []);  // Array concatenation
     * $db->vDeleteComplex('users', "status = '{$status}'", []);  // String interpolation
     */
    public function vDeleteComplex($sTable, $sWhereClause, $aBinds)
    {
        // Validate inputs
        if (empty($sTable) || !is_string($sTable)) {
            throw new \InvalidArgumentException("sTable must be a non-empty string");
        }
        if (empty($sWhereClause) || !is_string($sWhereClause)) {
            throw new \InvalidArgumentException("sWhereClause must be a non-empty string");
        }
        if (!is_array($aBinds)) {
            throw new \InvalidArgumentException("aBinds must be an array");
        }

        // Security warning: Check for suspicious patterns
        $suspiciousPatterns = [
            '/\bunion\b/i',           // UNION keyword
            '/;\s*(drop|delete|insert|update|create|alter)\b/i',  // Stacked queries
            "/'\s*(or|and)\s*'/i",    // Quote patterns like ' OR '
            "/\b(or|and)\s+1\s*=\s*1/i",  // OR 1=1 pattern
            "/['\"]\s*\)/i",  // Closing quote and paren
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sWhereClause)) {
                trigger_error(
                    "vDeleteComplex: Suspicious SQL pattern detected in WHERE clause. " .
                    "Ensure all dynamic values are passed through aBinds, not concatenated into sWhereClause. " .
                    "Query: DELETE FROM {$sTable} WHERE {$sWhereClause}",
                    E_USER_WARNING
                );
            }
        }

        $sSql = "DELETE FROM `" . $sTable . "` WHERE " . $sWhereClause;

        try {
            $this->iQuery($sSql, $aBinds);
            if (!$this->m_iRs) {
                throw new \Exception("CDbShell->vDeleteComplex: fail to delete data in $sTable");
            }
            $this->m_iRs->closeCursor();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * 得到 table create sql info
     *
     * @param string $sTable The table name
     * @return array The create table info
     */
    public function aGetCreateTableInfo($sTable)
    {
        $this->iQuery("SET SQL_QUOTE_SHOW_CREATE = 1");
        $this->iQuery("SHOW CREATE TABLE $sTable");
        $aRow = $this->aFetchArray();
        return $aRow;
    }

    /**
     * 檢查資料表是否存在
     *
     * @param string $sTable The table name
     * @return boolean
     */
    public function bIsTableExist($sTable)
    {
        if (strpos($this->dsn, 'sqlite:') === 0) {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%$sTable%'";
        } else {
            $sql = "SHOW TABLES LIKE '%$sTable%'";
        }

        $iDbq = $this->iQuery($sql);
        if ($this->iNumRows($iDbq)) {
            return true;
        }
        return false;
    }

    // transactions function
    public function vBegin()
    {
        //if this CDbShell already start a trancation, it won't "begin" again , but only +1 on layer
        if ($this->iTransactionLayer === 0) {
            $this->iQuery("begin");
        }
        $this->iTransactionLayer++;
    }

    public function vCommit()
    {
        //if this CDbShell's trancation layer is more than 1, it won't "commit" right away
        //, but only -1 on layer, there will be another vCommit later
        $this->iTransactionLayer--;
        if ($this->iTransactionLayer === 0) {
            $this->iQuery("commit");
        }
    }

    public function vRollback()
    {
        $this->iTransactionLayer = 0;
        $this->iQuery("rollback");
    }

    /**
     * 得到某筆資料是在第幾頁
     *
     * @param string $sTable The table name
     * @param string $sField The field name
     * @param integer $iGoId 流水號
     * @param integer $iPageItems 每頁顯示筆數
     * @param string $sSearchSql 條件
     * @param array $aBinds The binds array
     * @param string $sPostFix 順序&limit
     * @return integer The page number
     */
    public function iGetItemAtPage($sTable = "", $sField = "", $iGoId = 0, $iPageItems = 0, $sSearchSql = '', $aBinds = array(), $sPostFix = '')
    {
        if (!$sTable || !$sField) {
            return 0;
        }
        $sSql = "SELECT $sField FROM $sTable";
        if ($sSearchSql !== '') {
            $sSql .= " WHERE $sSearchSql";
        }
        if ($sPostFix !== '') {
            $sSql .= " $sPostFix";
        }

        $this->iQuery($sSql, $aBinds);
        $i = 0;
        $biFind = false;
        while ($aRow = $this->aFetchArray()) {
            if ($aRow[$sField] == $iGoId) {
                $biFind = true;
                break;
            }
            $i++;
        }
        if (!$biFind) {
            $i = 0;
        }

        return (int)($i / $iPageItems);
    }
}
