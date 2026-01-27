<?php

/**
 * MySQL class PDO 版本
 */
class Database_20260127 {

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

    // public function ReConnect($sDb='',$sHost='',$sUser='',$sPass='',$sPort='') {
    //     if($sDb) $this->m_sDb=$sDb;
    //     if($sHost) $this->m_sHost=$sHost;
    //     if($sUser) $this->m_sUser=$sUser;
    //     if($sPass) $this->m_sPass=$sPass;
    //     if($sPass) $this->m_sPass=$sPass;
    //     if($sPort) $this->m_sPort=$sPort;
    //     $this->vConnect();
    // }

    // /**
    //  * 檢查連線是否成功
    //  * @return boolean 是否成功
    //  */
    // public function bCheckConnect(){
    //     if($this->m_iDbh) return true;
    //     else return false;
    // }

    /**
     * 連線資料庫
     * @return void
     */
    public function vConnect() {
        $dsn = "mysql:host={$this->m_sHost};port={$this->m_sPort};dbname={$this->m_sDb};charset={$this->m_character}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_FUNC, // PDO::FETCH_ASSOC
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->m_iDbh = new PDO($dsn, $this->m_sUser, $this->m_sPass, $options);
            // $this->m_iDbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_FUNC);
            // $this->m_iDbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    // /**
    //  * 關閉資料庫
    //  * @return void
    //  */
    // public function vClose() {
    //     $this->m_iDbh = null;
    // }

    /**
     * query db
     * @param  string $sSql   SQL語法
     * @param  array  $aBinds 綁定的資料
     */
    public function iQuery($sSql, $aBinds=array()) {
        $i = 0;

        try {
            $this->m_iRs = $this->m_iDbh->prepare($sSql); // Returns a PDOStatement object

            for($i=0;$i<count($aBinds);$i++){
                $this->m_iRs->bindParam($i+1, $aBinds[$i], PDO::PARAM_STR | PDO::PARAM_INT);
            }

            $this->m_iRs->execute();
        } catch(\PDOException $e) {
            throw new \PDOException("\nSQL Error: $sSql\n".$e->getMessage());
        }

        return $this->m_iRs;
    }

    // /**
    //  * Free result memory
    //  * @param integer $iRs [description]
    //  */
    // public function bFreeRows($iRs=0) {
    //     if($iRs) $iTmpRs=$iRs;
    //     else    $iTmpRs=$this->m_iRs;
    //     if(!is_resource($iTmpRs)) return false;
    //     return  $iTmpRs->closeCursor();
    // }

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
        return  $iTmpRs->fetch(PDO::FETCH_ASSOC);
    }

    // public function aFetchArray($iRs=0) {
    //     return $this->aFetchAssoc($iRs);
    // }

    /**
    * 取得insert後的自動流水號
    * @return Get the ID generated from the previous INSERT operation
    */
    public function iGetInsertId() {
        if(!$this->m_iRs) return 0;
        return $this->m_iDbh->lastInsertId();
    }

    // /**
    // * insert into table
    // * @param $sTable db table $aField field array $aValue value array
    // * @return if return sql is ok  "" is failure
    // */
    // public function sInsert($sTable,$aBinds) {
    //     if(!is_array($aBinds)) return 0;

    //     $sSql="INSERT INTO $sTable ";
    //     $aField = array_keys($aBinds);
    //     $sSql.='('.implode(",",$aField).')';
    //     $sSql.='VALUES(:'.implode(", :", $aField).')';
    //     $this->m_iRs = $this->m_iDbh->prepare($sSql);
    //     foreach($aBinds as $bindKey => $value){
    //         $this->m_iRs->bindValue(":$bindKey", $value);
    //     }

    //     try{
    //         $this->m_iRs->execute();
    //         $this->m_iRs->closeCursor();
    //         return $sSql;
    //     }catch(PDOException $e){
    //         throw new \Exception($e->getMessage());
    //     }
    // }

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
            $this->m_iRs->closeCursor();
            return $this->iGetInsertId();
        }catch(\PDOException $e){
            throw new \PDOException($e->getMessage());
        }
    }

    // /**
    // * update  table
    // * @param $sTable db table $aField field array $aValue value array $sWhere trem
    // * @return if return sql is ok  "" is failure
    // */
    // public function sUpdate($sTable,$aBinds,$sWhere) {

    //     if(!is_array($aBinds)) return 0;
    //     $aField = array_keys($aBinds);

    //     $sSql="UPDATE $sTable SET ";
    //     for($i=0;$i<count($aField);$i++) {
    //         $sSql.="`".$aField[$i]."`=:".$aField[$i];
    //         if(($i+1)!=count($aField)) $sSql.=",";
    //     }
    //     if($sWhere)
    //         $sSql.=" WHERE ".$sWhere;

    //     try{
    //         $this->m_iRs = $this->m_iDbh->prepare($sSql);

    //         foreach($aBinds as $bindKey => $value){
    //             $this->m_iRs->bindValue(":$bindKey", $value, PDO::PARAM_STR| PDO::PARAM_INT);
    //         }

    //         $this->m_iRs->execute();
    //         $this->m_iRs->closeCursor();

    //         return $sSql;
    //     }catch(\PDOException $e){
    //         throw new \PDOException($e->getMessage());
    //     }
    // }

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
                $this->m_iRs->bindValue(":$bindKey", $value, PDO::PARAM_STR | PDO::PARAM_INT);
            }

            $this->m_iRs->execute();
            $this->m_iRs->closeCursor();

            return $sSql;
        }catch(\PDOException $ex){
            throw new \PDOException($ex->getMessage());
        }
    }

    private function my_quotes($data) {
        if(is_null($data)) return null;

        if(is_array($data)) {
            foreach($data as $key => $val) {
                if(is_array($val)) {
                    $data[$key] = $this->my_quotes($val);
                } else {
                    $data[$key] = ini_set("magic_quotes_runtime",0) ? trim($val) : addslashes(trim($val));
                    $data[$key] = self::removeNbsp($data[$key]);
                }
            }
        } else {
            $data = ini_set("magic_quotes_runtime",0) ? trim($data) : addslashes(trim($data));
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
        $str = trim($str);
        $str = preg_replace('/[\x{00A0}\x{2002}\x{2003}\x{2004}\x{2005}\x{2006}\x{2007}\x{2008}\x{2009}\x{200A}\x{202F}\x{205F}\x{3000}]/isu', ' ', $str);
        $str = preg_replace('/[\x{00AD}\x{180E}\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/isu', '', $str);

        return $str;
    }

    // /*
    //     delete data from target table
    // */
    // public function vDelete($sTable,$sWhere, $aBinds=array()){
    //     try{
    //         $this->iQuery("DELETE FROM $sTable WHERE $sWhere",$aBinds);
    //         if(!$this->m_iRs)
    //             throw new \Exception("CDbShell->vDelete: fail to delete data in $sTable");
    //     }catch (\PDOException $e){
    //         throw new \PDOException($e->getMessage());
    //     }

    //     $this->m_iRs->closeCursor();
    // }

    // /**
    // * 得到table所有欄位資訊
    // * @param $sTable db table
    // * @return array
    // */
    // public function aGetAllFieldsInfo($sTable){
    //     try{
    //         $this->iQuery("SHOW FULL FIELDS FROM $sTable");
    //         while($aRow=$this->aFetchArray()){
    //             //array( Field       Type    Collation       Null    Key     Default     Extra       Privileges      Comment)
    //             $aFields[]=$aRow;
    //         }
    //         return $aFields;
    //     }catch (\PDOException $e){
    //         throw new \PDOException($e->getMessage());
    //     }
    // }

    // /**
    // * 得到table create sql info
    // * @param $sTable db table
    // * @return array
    // */
    // public function aGetCreateTableInfo($sTable){
    //     $this->iQuery("SET SQL_QUOTE_SHOW_CREATE = 1");
    //     $this->iQuery("SHOW CREATE TABLE $sTable");
    //     $aRow=$this->aFetchArray();
    //     //arrary field (Table,Create Table)
    //     return $aRow;
    // }

    // public function aGetTableStatus($sTable){
    //     $this->iQuery("SHOW TABLE STATUS LIKE '$sTable'");
    //     //arrary field ( Name    Engine      Version     Row_format      Rows    Avg_row_length      Data_length     Max_data_length     Index_length    Data_free       Auto_increment      Create_time     Update_time     Check_time      Collation       Checksum    Create_options      Comment
    //     $aRows = array();
    //     while($aRow=$this->aFetchArray()){
    //         $aRows[] = $aRow;
    //     }
    //     return $aRows;
    // }

    // /**
    // * 得到table create sql info
    // * @param $sTable db table
    // * @return array
    // */
    // public function bIsTableExist($sTable){
    //     //echo "show tables like '$sTable'";
    //     //SHOW TABLES LIKE '%project_article%'
    //     $sql = "SHOW TABLES LIKE '%$sTable%'";
    //     $iDbq = $this->iQuery($sql);
    //     if($this->iNumRows($iDbq))
    //         return true;
    //     return false;
    // }

    // /**
    // * 得到table create sql info
    // * @param $sTable db table
    // * @return array
    // */
    // public function bTableExist2($sTable){
    //     //echo "show tables like '$sTable'";
    //     //SHOW TABLES LIKE '%project_article%'
    //     $sql = "SHOW TABLES LIKE '$sTable'";
    //     $iDbq = $this->iQuery($sql);
    //     if($this->iNumRows($iDbq))
    //         return true;
    //     return false;
    // }

    // /**
    // * 得到table create sql info
    // * @param $sTable db table
    // * @return array
    // */
    // public function bIsDatabaseExist($sDatabase){
    //     $iDbq = $this->iQuery("show databases like '$sDatabase'");
    //     if($this->iNumRows($iDbq))
    //         return true;
    //     return false;
    // }

    // /**
    // * 得到table create sql info
    // * @param $sTable db table
    // * @return array
    // */
    // public function bCheckTableExist($sTable){
    //     $this->iQuery("CHECK TABLE $sTable");
    //     $aRow=$this->aFetchArray();
    //     if($aRow['Msg_text'] == "OK")
    //         return true;
    //     return false;
    // }

    // // transactions function
    // function vBegin () {
    //     $this->iQuery("begin");
    // }

    // function vCommit () {
    //     $this->iQuery("commit");
    // }

    // function vRollback () {
    //     $this->iQuery("rollback");
    // }

    // /**
    // * 得到某筆資料是在第幾頁
    // * @param $sTable db table $iGoId 流水號 $iPageItems 每頁顯示比數 $sSearchSql 條件 $sPostFix 順序&limit
    // * @return 數字
    // */
    // public function iGetItemAtPage($sTable="", $sField="", $iGoId=0, $iPageItems=0, $sSearchSql='', $aBinds=array(), $sPostFix=''){

    //     if(!$sTable || !$sField) return 0;
    //     $sSql = "SELECT $sField  FROM $sTable";
    //     if($sSearchSql!=='')
    //         $sSql .= " WHERE $sSearchSql";
    //     if($sPostFix!=='')
    //         $sSql .= " $sPostFix";

    //     $this->iQuery($sSql,$aBinds);
    //     $i=0;
    //     $biFind = false;
    //     while($aRow=$this->aFetchArray()) {
    //         if($aRow[$sField]==$iGoId) {
    //             $biFind = true;
    //             break;
    //         }
    //         $i++;
    //     }
    //     if(!$biFind) $i=0;

    //     return (INT)($i/$iPageItems);
    // }

    // public function iGetJoinItemAtPage($sTable1="", $sTable2="", $sField="", $iGoId=0, $iPageItems=0, $sSearchSql='', $aBinds=array(), $sPostFix=''){
    //     if(!$sTable ||$sTable2 || !$sField) return 0;
    //     $sSql = "SELECT $sField FROM $sTable1 LEFT JOIN $sTable2 ON $sTable1.$sField=$sTable2.$sField";
    //     if($sSearchSql!=='')
    //         $sSql .= " WHERE $sSearchSql";
    //     if($sPostFix!=='')
    //         $sSql .= " $sPostFix";
    //     $this->iQuery($sSql,$aBinds);
    //     $i=0;
    //     $biFind = false;
    //     while($aRow=$this->aFetchArray()) {
    //         if($aRow[$sField]==$iGoId) {
    //             $biFind = true;
    //             break;
    //         }
    //         $i++;
    //     }
    //     if(!$biFind) $i=0;

    //     return (INT)($i/$iPageItems);
    // }
}
