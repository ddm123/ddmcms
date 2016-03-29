<?php
/**
 * MySQL 操作类
 *
 * @author DDM
 * @copyright (c) 2014-2015
 */

class Ddm_Db_Mysqli implements Ddm_Db_Interface{
	protected $_mysqli = NULL;
	protected $_databaseName = '';
	protected $_querynum = 0;
	protected $_closed = false;

	public function __construct($ser, $un, $pw, $db = NULL, $port = NULL, $character = NULL, $pconnect = false) {
		if($pconnect)$ser = "p:$ser";
		$this->_mysqli = new mysqli($ser, $un, $pw, $db, $port ? $port : 3306);
		if($character===NULL)$character = 'utf8';//默认使用UTF-8编码
		if($character)$this->_mysqli->set_charset($character);
	}

	public function __destruct() {
		$this->_closed or $this->close();
	}

	/**
	 * @param string $databaseName
	 * @return Ddm_Db_Mysqli
	 */
	public function setDatabase($databaseName) {
		$this->_databaseName = $databaseName;
		$this->_mysqli->select_db($databaseName);

		return $this;
	}

	/**
	 * @param resource $dblink
	 * @return Ddm_Db_Mysqli
	 */
	public function close($dblink = false) {
		$this->_closed = $this->_mysqli->close();

		return $this;
	}

	public function quote($string){
		return "'".$this->_mysqli->real_escape_string($string)."'";
	}

	public function query($sql) {
		$result = $this->_mysqli->query($sql,MYSQLI_USE_RESULT) or $this->_error($sql);
		$this->_querynum++;
		return $result;
	}

	//第一个参数是SQL, 第二个参数开始是填充SQL的变量
	public function queryf() {
		$numargs = func_num_args();
		if(!$numargs)return false;
		if($numargs==1)return $this->query(func_get_arg(0));
		return $this->query(call_user_func_array('sprintf',func_get_args()));
	}

	public function fetch($res){
		return $res->fetch_assoc();
	}

	/**
	 * 获取查询到的所有记录
	 * @param string $sql
	 * @param string $primaryKey 主键字段名
	 * @return array
	 */
	public function fetchAll($sql, $primaryKey = NULL){
		$fetchArray = array();
		$result = $this->query($sql);

		if($primaryKey){
			while($row = $this->fetch($result))$fetchArray[$row[$primaryKey]] = $row;
		}else{
			while($row = $this->fetch($result))$fetchArray[] = $row;
		}
		$this->freeResult($result);

		return $fetchArray;
	}

	public function fetchOne($sql, $getFirst = false){
		$result = $this->query($sql);
		$row = $getFirst ? $result->fetch_row() : $this->fetch($result);
		$this->freeResult($result);
		return $getFirst ? ($row ? $row[0] : NULL) : $row;
	}

	//行转列
	public function fetchPairs($sql){
		$returnValue = array();
		$result = $this->query($sql);
		if($row = $result->fetch_row()){
			$i = count($row);
			do{
				if($i>1)$returnValue[$row[0]] = $row[1];
				else $returnValue[] = $row[0];
			}while($row = $result->fetch_row());
		}
		$this->freeResult($result);
		return $returnValue;
	}

	public function count($table, array $expression = NULL){
		$where = $expression ? ' WHERE '.$this->parseExpression($expression) : '';
		$sql = "SELECT COUNT(*) AS t FROM $table".$where;
		$result = $this->query($sql);
		$row = $result->fetch_row();
		$total = $row[0];
		$this->freeResult($result);

		return $total;
	}

	public function sum($table, $fieldname, array $expression = NULL){
		$returnValue = 0;
		$where = $expression ? ' WHERE '.$this->parseExpression($expression) : '';
		is_array($fieldname) or $fieldname = explode(',',$fieldname);
		$sql = '';
		foreach($fieldname as $k=>$v)$sql .= ",SUM($v) AS `".(is_numeric($k) ? $v : $k).'`';
		$sql = "SELECT ".substr($sql,1)." FROM $table{$where}";
		$result = $this->query($sql);
		$len = count($fieldname);
		$row = $len>1 ? $this->fetch($result) : $result->fetch_row();
		if($row){
			$returnValue = $len>1 ? $row : 1*$row[0];
		}
		$this->freeResult($result);
		return $returnValue;
	}

	public function getLastInsertId(){
		return $this->_mysqli->insert_id;
	}

	/**
	 * $v 一个数组，是需要保存到表的数据：array(字段1=>值1, 字段2=>值2[, ......])
	 * 如果$type值为DUPLICATE时，$expression即为替换值，不再是条件表达式，格式和$v一样
	 * @param string $table
	 * @param array $v
	 * @param string $type
	 * @param array $expression
	 * @return bool
	 */
	public function save($table, array $v, $type = self::SAVE_INSERT, array $expression = NULL){
		$returnValue = false;
		if(!$v)return $returnValue;
		switch($type){
			case self::SAVE_INSERT:case 'I':
			case self::SAVE_REPLACE:case 'R':
			case self::SAVE_DUPLICATE:case 'D':
				$this->_formatData($v);
				$sql = ($type==self::SAVE_REPLACE||$type=='R' ? 'REPLACE' : 'INSERT')." INTO $table(`".implode('`,`',array_keys($v))."`) VALUES(".implode(',',$v).")";
				if($type==self::SAVE_DUPLICATE||$type=='D'){
					if($expression){
						$sql .= ' ON DUPLICATE KEY UPDATE '.$this->_formatData($expression,',');
					}
				}
				$returnValue = $this->query($sql);
				break;
			case self::SAVE_UPDATE:case 'U':
				$sql = $this->_formatData($v,',');
				if($expression)$expression = $expression ? ' WHERE '.$this->parseExpression($expression) : '';
				$sql = "UPDATE $table SET ".$sql.$expression;
				$returnValue = $this->query($sql);
				break;
		}

		return $returnValue;

	}

	public function delete($table, array $expression = NULL) {
		$where = $expression ? ' WHERE '.$this->parseExpression($expression) : '';
		$sql = "DELETE FROM $table".$where;
		$result = $this->query($sql);
		return $result;
	}

	public function insertMultiple($table, array $data, array $OnDuplicateFields = array()){
		$row = reset($data);
		$isMultiple = is_array($row);
		$fields = array_keys($isMultiple ? $row : $data);
		$sql = "INSERT INTO $table(`".implode('`,`',$fields)."`) VALUES ";
		if($isMultiple){
			$i = 0;
			foreach($data as $row){
				if($i++)$sql .= ',';
				$sql .= '('.$this->_dataToSqlString($row).')';
			}
		}else{
			$sql .= '('.$this->_dataToSqlString($data).')';
		}
		if($OnDuplicateFields){
			$sql .= ' ON DUPLICATE KEY UPDATE '.$this->_formatData($OnDuplicateFields,',');
		}
		$result = $this->query($sql);
		return $result;
	}

	public function freeResult($result){
		$result->free();
		return $this;
	}

	public function getMysqlVersion(){
		$result = $this->query("SELECT VERSION() AS version");
		$row = $result->fetch_row();
		$version = $row[0];
		$this->freeResult($result);

		return $version;
	}

	public function getQueryCount(){
		return $this->_querynum;
	}

	public function parseExpression(array $expression){
		$where = '';
		if($expression){
			$select = new Ddm_Db_Select();
			$where = $select->quoteInto($expression);
		}
		return $where;
	}

	public function beginTransaction(){
		return method_exists($this->_mysqli,'begin_transaction') ? $this->_mysqli->begin_transaction() : $this->_mysqli->autocommit(false);
	}

	public function commit(){
		return $this->_mysqli->commit();
	}

	public function rollBack(){
		return $this->_mysqli->rollback();
	}

	public function getSelect() {
		return new Ddm_Db_Select($this);
	}

	private function _formatData(array &$data,$separator = ','){
		$str = '';
		foreach($data as $key=>$value){
			$str=='' or $str .= $separator;
			if(is_int($key)){
				$str .= "`$value`=VALUES(`$value`)";
			}else{
				if($value instanceof Ddm_Db_Expression){
					$data[$key] = $value->__toString();
				}else{
					$data[$key] = $value===NULL ? 'NULL' : (is_numeric($value)||is_bool($value) ? "'$value'" : $this->quote($value));
				}
				$str .= "`$key`={$data[$key]}";
			}
		}
		return $str;
	}

	private function _dataToSqlString(array $data){
		$str = '';
		foreach($data as $value){
			$str=='' or $str .= ',';
			if($value instanceof Ddm_Db_Expression){
				$str .= $value->__toString();
			}else{
				$str .= $value===NULL ? 'NULL' : (is_numeric($value)||is_bool($value) ? "'$value'" : $this->quote($value));
			}
		}
		return $str;
	}

	private function _error($sql){
		$mysqlErrorMessage = '';
		if($this->_mysqli->connect_errno){
			isset($this->_mysqli->connect_error) or $this->_mysqli->connect_error = mysqli_connect_error();
			$mysqlErrorMessage .= "\r\nConnect Error: ".$this->_mysqli->connect_error;
		}
		if($this->_mysqli->error)$mysqlErrorMessage .= "\r\nErrormessage: ".$this->_mysqli->error."\r\n";
		if(defined('SITE_ROOT')){
			$f = SITE_ROOT.'/data/errors/mysql/mysql-errors.txt';
			$logstring = date('Y-m-d H:i:s')."\r\n-----------------------------------";
			$logstring .= "\r\nURL: ".$_SERVER['REQUEST_URI']."\r\nIP: ".$_SERVER['REMOTE_ADDR']."\r\nUSER_AGENT: ".(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'None')."\r\nMETHOD: ".$_SERVER['REQUEST_METHOD'];
			if(!empty($_POST))$logstring .= "\r\nPOST_DATA: ".print_r($_POST,true);
			if(empty($_SERVER['QUERY_STRING']) && !empty($_GET))$logstring .= "\r\nGET_DATA: ".print_r($_GET,true);
			$logstring .= "\r\nSQL: $sql";
			$logstring .= $mysqlErrorMessage;
			if(!is_file($f) || filesize($f)>2097152){//2M
				@rename($f,SITE_ROOT.'/data/errors/mysql/mysql-errors-'.date('YmdHis').'.txt');
				Ddm::getHelper('core')->saveFile($f,$logstring);
			}else{
				Ddm::getHelper('core')->saveFile($f,"\r\n".$logstring, FILE_APPEND);
			}
		}

		throw new Exception('MYSQL Error:'.$mysqlErrorMessage."\r\nSQL: $sql");//exit;
	}
}
