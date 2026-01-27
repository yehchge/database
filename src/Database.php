<?php

/**
 * MySQL class PDO 版本
 */

declare(strict_types=1);

namespace yehchge\database;

class Database {

    // Variables
    private $m_sHost = "";
    private $m_sUser = "";
    private $m_sPass = "";
    private $m_sDb   = "";
    private $m_sPort = '3306';
    private $m_iDbh  = 0;
    private $m_iRs   = 0;
    private $m_character = "utf8";

    /**
     * 連線資料庫
     * @param string $sDb   Database name
     * @param string $sHost Server IP or DNS name
     * @param string $sUser MySQL User
     * @param string $sPass MySQL Password
     * @param string $sPort MySQL Port
     */
    public function __construct($sDb='',$sHost='',$sUser='',$sPass='',$sPort='') {
        $this->m_sHost=defined('_MYSQL_HOST')?_MYSQL_HOST:null;
        $this->m_sUser=defined('_MYSQL_USER')?_MYSQL_USER:null;
        $this->m_sPass=defined('_MYSQL_PASS')?_MYSQL_PASS:null;
        $this->m_sDb=defined('_MYSQL_DB')?_MYSQL_DB:null;
        $this->m_sPort=defined('_MYSQL_PORT')?_MYSQL_DB:null;

        if($sDb) $this->m_sDb=$sDb;
        if($sHost) $this->m_sHost=$sHost;
        if($sUser) $this->m_sUser=$sUser;
        if($sPass) $this->m_sPass=$sPass;
        if($sPort) $this->m_sPort=$sPort;

        if(!$this->m_iDbh) {
            $this->vConnect();
        }
    }

    public function __destruct()
    {
        $this->m_iDbh = null;
    }

    public static function oDB($sDBName){
        $port = defined('_'.$sDBName.'_PORT') ? constant('_'.$sDBName.'_PORT') : '3306';
        $aDB[$sDBName] = new self(
            constant('_'.$sDBName.'_DB'),
            constant('_'.$sDBName.'_HOST'),
            constant('_'.$sDBName.'_USER'),
            constant('_'.$sDBName.'_PASS'),
            $port
        );

        return $aDB[$sDBName];
    }

    /**
     * 設定 MySQL 連結為 UTF-8
     * @created 2014/11/14
     */
    public function bSetCharacter($encode = 'utf8') {
        // mysqli_set_charset($this->m_iDbh, $encode);
        $this->iQuery("SET character_set_client = '$encode'");
        $this->iQuery("SET character_set_results = '$encode'");
        $this->iQuery("SET character_set_connection = '$encode'");
    }

    /**
     * 連線資料庫
     * @return void
     */
    public function vConnect() {
        // 判斷是否為 SQLite 記憶體模式
        if ($this->m_sHost === ':memory:' || $this->m_sDb === ':memory:') {
            $dsn = "sqlite::memory:";
        } else {
            $dsn = "mysql:host={$this->m_sHost};port={$this->m_sPort};dbname={$this->m_sDb};charset={$this->m_character}";
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // PDO::FETCH_ASSOC, FETCH_FUNC
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $user = $this->m_sUser;
        $pass = $this->m_sPass;

        try {
            if (strpos($dsn, 'sqlite:') === 0) {
                $this->m_iDbh = new \PDO($dsn, null, null, $options);
            } else {
                $this->m_iDbh = new \PDO($dsn, $user, $pass, $options);
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * 關閉資料庫
     * @return void
     */
    public function vClose() {
        $this->m_iDbh = null;
    }

    /**
     * query db
     * @param  string $sSql   SQL語法
     * @param  array  $aBinds 綁定的資料
     */
    public function iQuery($sSql, $aBinds=array()) {
        $i = 0;

        try {
            $this->m_iRs = $this->m_iDbh->prepare($sSql); // Returns a PDOStatement object

            // for($i=0;$i<count($aBinds);$i++){
            //     $this->m_iRs->bindParam($i+1, $aBinds[$i], \PDO::PARAM_STR | \PDO::PARAM_INT);
            // }

            foreach ($aBinds as $key => $value) {
                $this->m_iRs->bindValue($key + 1, $value, is_int($value) ? \PDO::PARAM_INT :  \PDO::PARAM_STR);
            }

            $this->m_iRs->execute();
        } catch(\PDOException $e) {
            throw new \PDOException("\nSQL Error: $sSql\n".$e->getMessage());
        }

        return $this->m_iRs;
    }

    /**
    * 取得sql結果比數
    * @param $iRs resource result
    * @return Get number of rows in result
    */
    public function iNumRows($iRs=0) {
        if($iRs) $iTmpRs=$iRs;
        else    $iTmpRs=$this->m_iRs;
        if(!$iTmpRs) return 0;
        return $iTmpRs->rowCount();
    }

    /**
    * 取得sql結果
    * @param $iRs resource result
    * @return Fetch a result row as an associative array, a numeric array, or both.
    */
    public function aFetchAssoc($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;

        if($iRs) $iTmpRs=$iRs;
        else    $iTmpRs=$this->m_iRs;
        return  $iTmpRs->fetch(\PDO::FETCH_ASSOC);
    }

    /**
    * 取得insert後的自動流水號
    * @return Get the ID generated from the previous INSERT operation
    */
    public function iGetInsertId() {
        if(!$this->m_iRs) return 0;
        return $this->m_iDbh->lastInsertId();
    }

    /**
    * insert into table
    * @param string $sTable The table name, array $aBinds The add data array
    * @return boolean
    */
    public function bInsert($sTable, $aBinds) {
        if(!is_array($aBinds)) return 0;

        $sSql="INSERT INTO $sTable ";
        $aField = array_keys($aBinds);
        $sSql.='('.implode(",",$aField).')';
        $sSql.='VALUES(:'.implode(", :", $aField).')';
        $this->m_iRs = $this->m_iDbh->prepare($sSql);
        foreach($aBinds as $bindKey => $value){
            $this->m_iRs->bindValue(":$bindKey", $value);
        }

        try{
            $this->m_iRs->execute();
            $insertId = $this->iGetInsertId();
            $this->m_iRs->closeCursor();
            return $insertId;
        }catch(\PDOException $e){
            throw new \PDOException($e->getMessage());
        }
    }

    /**
    * update table
    * @param string $sTable The table name, array $aSrc The source data array, array $aTar The target data array
    * @return boolean
    */
    public function bUpdate($sTable, $aWhere, $aBinds) {
        if(!is_array($aBinds)) return 0;
        $aField = array_keys($aBinds);

        $sSql="UPDATE $sTable SET ";
        for($i=0;$i<count($aField);$i++) {
            $sSql.="`".$aField[$i]."`=:".$aField[$i];
            if(($i+1)!=count($aField)) $sSql.=",";
        }

        if($aWhere){
            $aTmpWhere = array();
            foreach( $aWhere AS $key => $value ) {
                $aTmpWhere[] = "$key = '".$this->my_quotes($value)."'";
            }

            $sSql.=" WHERE ".implode( " AND " , $aTmpWhere );
        }

        try{
            $this->m_iRs = $this->m_iDbh->prepare($sSql);
            foreach($aBinds as $bindKey => $value){
                $this->m_iRs->bindValue(":$bindKey", $value, \PDO::PARAM_STR | \PDO::PARAM_INT);
            }

            $this->m_iRs->execute();

            $iAffectedRows = $this->m_iRs->rowCount();
            
            $this->m_iRs->closeCursor();

            return $iAffectedRows;
        }catch(\PDOException $ex){
            throw new \PDOException($ex->getMessage());
        }
    }

    private function my_quotes($data) {
        if(is_null($data)) return null;
        if(is_array($data)) {
            foreach($data as $key => $val) {
                $data[$key] = is_array($val) ? $this->my_quotes($val) : addslashes(trim((string)$val));
                $data[$key] = self::removeNbsp($data[$key]);
            }
        } else {
            $data = addslashes(trim((string)$data));
            $data = self::removeNbsp($data);
        }
        return $data;
    }

    /**
     * 刪除零寬字元
     * Replace non-breaking spaces with normal spaces
     * @param $str
     * @return string
     */
    public static function removeNbsp($str){
        if (is_string($str)) {
            $str = trim($str);
            $str = preg_replace('/[\x{00A0}\x{2002}\x{2003}\x{2004}\x{2005}\x{2006}\x{2007}\x{2008}\x{2009}\x{200A}\x{202F}\x{205F}\x{3000}]/isu', ' ', $str);
            $str = preg_replace('/[\x{00AD}\x{180E}\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/isu', '', $str);
        }
        return $str;
    }
}
