<?php
header('Content-type:text/html;Charset=utf8');
/**
 * 封装PDO操作MySQL
 * Class MyPDO
 */
class MyPDO{
    //常量：
    //变量：
    public $pdo;
    private $config = array();
	//单例
	private static $instance = null;
    //构造函数，实例化
    private function __construct($config)
    {
        $this->config = include_once($config);
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']}";
        $username = $this->config['username'];
        $password = $this->config['password'];
        try{
            $this->pdo = new PDO($dsn,$username,$password,$this->config['options']);
        }catch(PDOException $e){
            $this->outputErrMsg($e->getMessage());
        }
    }
	private function __clone(){}
	public static function getInstance($config){
		if(self::$instance == null){
			return self::$instance = new self($config);
		}
		return self::$instance;
	}
    /**
     * insert/update/delete
     * @param $sql
     * 返回影响行数
     */
    public function myExec($sql,$debug = false){
        if($debug) $this->debug($sql);
        $count = $this->pdo->exec($sql);
        $this->getPDOErr();
        return $count;
    }

    /**
     * @param string $strSql 查询语句
     * @param string $queryMode All/Row
     * @param boolean $debug
     * @return array 返回查询结果关联数组
     * $strSql, $queryMode = 'All', $debug = false
     */
    public function myQuery($strSql, $queryMode = 'All', $debug = false){
        if($debug) $this->debug($strSql);
        $stmt = $this->pdo->query($strSql);
        $this->getPDOErr();
        if($stmt){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            if($queryMode == 'All'){
                $result = $stmt->fetchAll();
            }elseif($queryMode == 'Row'){
                $result = $stmt->fetch();
            }
        }else{
            $result = null;
        }
        return $result;
    }
    /**
     * Update 更新
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param String $where 条件
     * @param Boolean $debug
     * @return Int
     */
    public function update($table, $arrayDataValue, $where = '', $debug = false){
        $this->checkFields($table,array_keys($arrayDataValue));
        $strUpdate = '';
        foreach ($arrayDataValue as $key => $value){
            $strUpdate .= "$key='$value'" . ',';
        }
        $strUpdate = rtrim($strUpdate,',');

        if($where){
            $sql = "update $table set $strUpdate where $where";
        }else{
            $fieldlist = implode(',',array_keys($arrayDataValue));
            $valuelist = '';
            foreach ($arrayDataValue as $value){
                $valuelist .= "'$value',";
            }
            $sql = "replace into $table ($fieldlist) values(" . trim($valuelist,',') . ")";
        }
        if($debug) $this->debug($sql);
        $result = $this->myExec($sql);
        $this->getPDOErr();
        return $result;
    }
    /**
     * Insert 插入
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param Boolean $debug
     * @return Int
     */
    public function insert($table, $arrayDataValue, $debug = false){
        $this->checkFields($table,array_keys($arrayDataValue));
        $fieldlist = implode(',',array_keys($arrayDataValue));
        $valuelist = '';
        foreach ($arrayDataValue as $value){
            $valuelist .= "'$value'" . ',';
        }
        $sql = "insert into $table ($fieldlist) values(" . rtrim($valuelist,',') . ")";
        if($debug) $this->debug($sql);
        $result = $this->myExec($sql);
        $this->getPDOErr();
        return $result;
    }
    /**
     * Replace 覆盖方式插入
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param Boolean $debug
     * @return Int
     */
    public function replace($table, $arrayDataValue, $debug = false){
        $this->checkFields($table,array_keys($arrayDataValue));
        $fieldlist = implode(',',array_keys($arrayDataValue));
        $valuelist = '';
        foreach ($arrayDataValue as $value){
            $valuelist .= "'$value',";
        }
        $sql = "replace into $table ($fieldlist) values(" . trim($valuelist,',') . ")";
        if($debug) $this->debug($sql);
        $result = $this->myExec($sql);
        $this->getPDOErr();
        return $result;
    }

    /**
     * Delete 删除
     *
     * @param String $table 表名
     * @param String $where 条件
     * @param Boolean $debug
     * @return Int
     */
    public function delete($table, $where = '', $debug = false){
        if($where == '') $this->outputErrMsg('$where is NULL');
        $sql = "delete from $table where $where";
        if($debug) $this->debug($sql);
        $result = $this->myExec($sql);
        $this->getPDOErr();
        return $result;
    }
    /**
     * PDO预处理实现增删查改
     * @param  $action 0/1/2/3查增删改，默认查数据
     * $param array $table 要操作的表
     * @param array $column 字段列表数组，索引数组
     * @param array $data 字段对应的索引数组
     */
    public function myBatch($table,$column = array(),$data = array(),$action = 0){
        if(is_array($column)){
            $values_num = count($column);
            $column = implode(',',$column);
            $values = rtrim(str_repeat('?,',$values_num),',');
        }else{
            $column[] = $column;
        }
        switch($action){
            case 1:
                $sql = "insert into $table ($column) values($values)";
                break;
            case 2:
                $sql = "delete from $table where {$column[0]} = {$data[0]}";
                break;
            case 3:
                //$colum[0]更新的对应信息，$colum[1]是where条件信息，是个二维数组
                $sql = "update $table set $column[0] = $data[0] where $column[1] = $data[1]";
                break;
            default:
                $sql = "select * from $table";
        }
        $stmt = $this->pdo->prepare($sql);
        if($action != 0){
            if(!$result = $stmt->execute($data)){
                $this->outputErrMsg('<br>数据操作失败!');
                return false;
            }else{
                return true;
            }
        }else{
            $stmt = $this->pdo->query($sql);
            try{
                $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }catch(PDOException $e){
                $this->outputErrMsg($e->getMessage());
            }
            return $all;
        }
    }
    /**
     * 获取字段最大值
     *
     * @param string $table 表名
     * @param string $field_name 字段名
     * @param string $where 条件
     */
    public function getMaxValue($table, $field_name, $where = '', $debug = false){
        if(is_string($field_name)) $field_name_arr[] = $field_name;
        $this->checkFields($table,$field_name_arr);
        $sql = "select max($field_name) as MAX_VALUE from $table";
        if($debug) $this->debug($sql);
        $row = $this->myQuery($sql,'Row');
        $this->getPDOErr();
        if($row == '' || $row == null) $row['MAX_VALUE'] = 0;
        return $row['MAX_VALUE'];
    }
    /**
     * 获取指定列的数量
     *
     * @param string $table
     * @param string $field_name
     * @param string $where
     * @param bool $debug
     * @return int
     */
    public function getCount($table, $field_name, $where = '', $debug = false){
        if(is_string($field_name)) $sql = "SELECT COUNT($field_name) AS NUM FROM $table";
        if ($where != '') $sql .= " WHERE $where";
        if ($debug === true) $this->debug($sql);
        $row = $this->myQuery($sql, 'Row');
        return $row['NUM'];
    }
    /**
     * 获取表引擎
     *
     * @param String $dbName 库名
     * @param String $tableName 表名
     * @param Boolean $debug
     * @return String
     */
    public function getTableStatus($dbName, $tableName){
        $sql = "show table status from $dbName where name = '$tableName'";
        $infoArr = $this->myQuery($sql);
        $this->getPDOErr();
        return $infoArr;
    }
    /**
     * beginTransaction 事务开始
     */
    private function beginTransaction(){
        $this->pdo->beginTransaction();
    }
    /**
     * commit 事务提交
     */
    private function commit(){
        $this->pdo->commit();
    }
    /**
     * rollback 事务回滚
     */
    private function rollback(){
        $this->pdo->rollBack();
    }
    /**
     * transaction 通过事务处理多条SQL语句
     * 调用前需通过getTableEngine判断表引擎是否支持事务
     *
     * @param array $arraySql
     * @return Boolean
     */
    public function execTransaction($arraySql){
        if(!is_array($arraySql)) $arraySql[] = $arraySql;
        $flag = 1;
        $this->pdo->beginTransaction();
        foreach ($arraySql as $value){
            //注意返回值可能为false没执行成功
            if($this->myExec($value) == 0) $flag = 0;
        }
        if($flag){
            $this->commit();
        }else{
            $this->rollback();
        }
    }
    /**
     * 判断字段数组元素是否都在表中
     * @param $table
     * @param $arrayFields
     */
    public function checkFields($table, $arrayFields){
        $fields = $this->getFields($table);
        foreach ($arrayFields as $value){
            if(!in_array($value,$fields))
                $this->outputErrMsg('unknown filed:' . $value . ',in table:'.$table.'\'s filed list');
        }
    }

    /**
     * 得到表中所有字段，返回字段数组
     * @param $table
     */
    private function getFields($table){
        $fields = array();
        $recordset = $this->myQuery("show columns from $table",'All');
        foreach ($recordset as $value){
            $fields[] = $value['Field'];
        }
        return $fields;
    }
    //错误显示函数
    public function getPDOErr(){
        if($this->pdo->errorCode() != '000000'){
            $this->outputErrMsg($this->pdo->errorInfo()[2]);
        }
    }
    private function debug($debugInfo){
        var_dump($debugInfo);
        exit();
    }
    private function outputErrMsg($strErrMsg){
        throw new Exception("MySQL Error:" . $strErrMsg);
    }
    //析构函数，销毁PDO对象
    public function __destruct()
    {
        $this->pdo = NULL;
    }
}
