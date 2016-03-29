<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Select{
	const COLUMNS    = 'columns';
	const JOINS      = 'joins';
	const FROM       = 'from';
	const DISTINCT   = 'distinct';
	const WHERE      = 'where';
	const GROUP      = 'group';
	const HAVING     = 'having';
	const ORDER      = 'order';
	const INNER_JOIN = 'inner';
	const LEFT_JOIN  = 'left';
	const RIGHT_JOIN = 'right';
	const LIMIT      = 'limit';

	protected $_parts = array();
	protected $_useAddslashes = true;
	protected $_connection = NULL;

	protected static $_partsInit = array(self::COLUMNS=>array(),self::FROM=>array(),self::DISTINCT=>false,self::JOINS=>array(),self::WHERE=>array(),self::GROUP=>array(),self::HAVING=>array(),self::ORDER=>array(),self::LIMIT=>'');
	protected static $_operators = array('='=>'%s=%s','<=>'=>'%s<=>%s','>='=>'%s>=%s','>'=>'%s>%s','<='=>'%s<=%s','<'=>'%s<%s','<>'=>'%s<>%s','!='=>'%s!=%s','IS'=>'%s IS %s','LIKE'=>'%s LIKE %s','REGEXP'=>'%s REGEXP %s','IN'=>'%s IN(%s)','BETWEEN'=>'%s BETWEEN %s AND %s',
		'IS NOT'=>'%s IS NOT %s','ISNOT'=>'%s IS NOT %s','NOT LIKE'=>'%s NOT LIKE %s','NOTLIKE'=>'%s NOT LIKE %s','NOT IN'=>'%s NOT IN(%s)','NOTIN'=>'%s NOT IN(%s)','NOT BETWEEN'=>'%s NOT BETWEEN %s AND %s','NOTBETWEEN'=>'%s NOT BETWEEN %s AND %s');

	/**
	 * @param Ddm_Db_Interface $connection
	 */
	public function __construct(Ddm_Db_Interface $connection = NULL){
		$this->_parts = self::$_partsInit;
		$this->_connection = $connection;
	}

	/**
	 * @param string $field
	 * @param string $table 表的别名(如果你给表使用了别名的话)
	 * @return string
	 */
	protected function _getFieldName($field,$table = ''){
		if($field instanceof Ddm_Db_Expression)return $field->__toString();
		if($table)$table = "$table.";
		return preg_match('/^\w+$/',$field) ? "$table`$field`" : ($field=='*' ? "{$table}*" : $field);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function _formatValue($value){
		if(is_array($value)){
			$_value = '';
			foreach($value as $v){
				$_value=='' or $_value .= ',';
				if($v instanceof Ddm_Db_Expression)$_value .= $v;
				else{
					$_value .= $this->_useAddslashes && !is_numeric($v) ? $this->getConnection()->quote($v) : "'$v'";
				}
			}
			return $_value==='' ? "''" : $_value;
		}else if($value===NULL)return 'NULL';
		else if($value instanceof Ddm_Db_Expression)return $value->__toString();
		else return $this->_useAddslashes && !is_numeric($value) ? $this->getConnection()->quote($value) : "'$value'";
	}

	/**
	 * @param string $fieldName
	 * @param array $value
	 * @return string
	 * @throws Exception
	 */
	protected function _quoteArrayValue($fieldName, array $value){
		$key = key($value);
		if(is_int($key))$sql = $this->_getFieldName($fieldName).' IN('.$this->_formatValue($value).')';
		else{
			$k = strtoupper($key);
			if(!isset(self::$_operators[$k])){
				throw new Exception('Operators error: '.$key);
			}
			if(is_array($value[$key])){
				$f = $this->_getFieldName($fieldName);
				if(strpos($k,'IN')!==false)$sql = sprintf(self::$_operators[$k],$f,$this->_formatValue($value[$key]));
				else if(strpos($k,'BETWEEN')!==false){
					if(count($value[$key])<2)$sql = "$f=".$this->_formatValue(current($value[$key]));
					else{
						$sql = sprintf(self::$_operators[$k],$f,$this->_formatValue(current($value[$key])),$this->_formatValue(next($value[$key])));
					}
				}else{
					$orWhere = '';
					foreach($value[$key] as $v){
						$orWhere=='' or $orWhere .= ' OR ';
						$orWhere .= sprintf(self::$_operators[$k],$f,$this->_formatValue($v));
					}
					$sql = $orWhere ? "($orWhere)" : '';
				}
			}else{
				$sql = sprintf(self::$_operators[$k],$this->_getFieldName($fieldName),$this->_formatValue($value[$key]));
			}
		}
		return $sql;
	}

	/**
	 * @param array|string $table
	 * @param string $on
	 * @param array|string $field
	 * @param string $joinType
	 * @return Ddm_Db_Select
	 */
	protected function _join($table,$on,$field = NULL,$joinType = self::INNER_JOIN){
		is_array($table) or $table = array($table=>$table);
		$key = key($table);
		$hasAliasName = !is_numeric($key) && $table[$key]!=$key;
		$joinKey = $hasAliasName ? $key : $table[$key];

		if(isset($this->_parts[self::JOINS][$joinKey])){
			throw new Exception("You cannot define a correlation name '$joinKey' more than once");
		}else{
			$this->_parts[self::JOINS][$joinKey] = array(
				'join_type' => $joinType==self::LEFT_JOIN ? 'LEFT JOIN' : ($joinType==self::RIGHT_JOIN ? 'RIGHT JOIN' : 'INNER JOIN'),
				'table' => $hasAliasName ? "$table[$key] AS `$key`" : $table[$key],
				'on' => "($on)",
				'field' => $field,
				'_table' => $hasAliasName ? "`$key`" : $table[$key]
			);
		}

		return $this;
	}

	/**
	 * @param bool|null $enable 如果值为NULL，则返回$_useAddslashes属性的值
	 * @return Ddm_Db_Select|bool
	 */
	public function enableAddslashes($enable = NULL){
		if($enable===NULL)return $this->_useAddslashes;
		$this->_useAddslashes = (bool)$enable;
		return $this;
	}

	/**
	 * @param Ddm_Db_Interface $connection
	 * @return Ddm_Db_Select
	 */
	public function setConnection(Ddm_Db_Interface $connection){
		$this->_connection = $connection;
		return $this;
	}

	/**
	 * @return Ddm_Db_Interface
	 */
	public function getConnection(){
		return $this->_connection===NULL ? ($this->_connection = Ddm_Db::getReadConn()) : $this->_connection;
	}

	/**
	 * @param array|string $table 虽然该参数可以是一个数组,但不支持传递多个表,如果需要查询多表,可以多次调用该方法
	 * @param array|string $column
	 * @return Ddm_Db_Select
	 */
	public function from($table,$column = '*'){
		if(!is_array($table)){
			$key = $table;
		}else{
			$key = key($table);
			$table = $table[$key];
			$key = is_numeric($key) ? $table : "`$key`";
		}
		if($column){
			$this->columns($column, $key==$table ? '' : $key);
		}
		$from = $table instanceof Ddm_Db_Select ? '('.$table.')' : $table;
		if($key!=$table)$from .= " AS $key";
		$this->_parts[self::FROM][] = $from;

		return $this;
	}

	/**
	 * @param bool $flag Whether or not the SELECT is DISTINCT (default true).
	 * @return Ddm_Db_Select
	 */
	public function distinct($flag = true){
		$this->_parts[self::DISTINCT] = (bool)$flag;
		return $this;
	}

	/**
	 * @param array|string $column
	 * @param string $table 表的别名(如果你给表使用了别名的话)
	 * @return Ddm_Db_Select
	 */
	public function columns($column,$table = ''){
		$this->_parts[self::COLUMNS][] = $this->parseColumn($column,$table);
		return $this;
	}

	/**
	 * @param array|string $table
	 * @param string $on
	 * @param array|string $field
	 * @return Ddm_Db_Select
	 */
	public function innerJoin($table,$on,$field = NULL){
		$this->_join($table,$on,$field,self::INNER_JOIN);
		return $this;
	}

	/**
	 * @param array|string $table
	 * @param string $on
	 * @param array|string $field
	 * @return Ddm_Db_Select
	 */
	public function leftJoin($table,$on,$field = NULL){
		$this->_join($table,$on,$field,self::LEFT_JOIN);
		return $this;
	}

	/**
	 * @param array|string $table
	 * @param string $on
	 * @param array|string $field
	 * @return Ddm_Db_Select
	 */
	public function rightJoin($table,$on,$field = NULL){
		$this->_join($table,$on,$field,self::RIGHT_JOIN);
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @param mixed $value
	 * @return Ddm_Db_Select
	 */
	public function where($fieldName, $value = NULL){
		$this->_parts[self::WHERE][] = $this->quoteInto($fieldName, $value);
		return $this;
	}

	/**
	 * @param int $offset 第几行开始
	 * @param int|NULL $rowCount 指定返回的行数的最大值
	 * @return Ddm_Db_Select
	 */
	public function limit($offset,$rowCount = NULL){
		$this->_parts[self::LIMIT] = $rowCount ? " LIMIT $offset,$rowCount" : " LIMIT $offset";
		return $this;
	}

	/**
	 * @param array|string|NULL $o
	 * @return Ddm_Db_Select
	 */
	public function order($o){
		if(is_array($o)){
			foreach($o as $_o)$this->order($_o);
		}else if($o===NULL){
			$this->_parts[self::ORDER]['NULL'] = 'NULL';
		}else if(is_string($o)){
			if(strpos($o,','))$this->order(explode(',',$o));
			else if(preg_match('/^\s*(?:`?(\w+)`?\.)?`?(\w+)`?(?:\s+(ASC|DESC))?\s*$/i',$o,$matches)){
				$fieldName = ($matches[1]=='' ? '`' : "`$matches[1]`.`").$matches[2].'`';
				$this->_parts[self::ORDER][$fieldName] = $fieldName.' '.(empty($matches[3]) ? 'ASC' : strtoupper($matches[3]));
			}
		}else if($o instanceof Ddm_Db_Expression){
			$o = $o->__toString();
			$this->_parts[self::ORDER][$o] = $o;
		}
		return $this;
	}

	/**
	 * @param array|string $g
	 * @return Ddm_Db_Select
	 */
	public function group($g){
		if(is_array($g)){
			foreach($g as $_g)$this->group($_g);
		}else{
			if(strpos($g,','))$this->group(explode(',',$g));
			else if(preg_match('/^\s*(?:`?(\w+)`?\.)?`?(\w+)`?\s*$/i',$g,$matches)){
				$this->_parts[self::GROUP][] = ($matches[1]=='' ? '' : "`$matches[1]`.")."`$matches[2]`";
			}else $this->_parts[self::GROUP][] = $g;
		}
		return $this;
	}

	/**
	 * @param string $cond
	 * @param string|NULL $value
	 * @return Ddm_Db_Select
	 */
	public function having($cond, $value = NULL){
		$this->_parts[self::HAVING][] = $this->quoteInto($cond,$value);
		return $this;
	}

	/**
	 * @return Ddm_Db_Select
	 */
	public function resetColumns(){
		$this->_parts[self::COLUMNS] = self::$_partsInit[self::COLUMNS];
		if($this->_parts[self::JOINS])foreach($this->_parts[self::JOINS] as $key=>$join)$this->_parts[self::JOINS][$key]['field'] = NULL;
		return $this;
	}

	/**
	 * @return Ddm_Db_Select
	 */
	public function resetJoin($joinKey = NULL){
		if($joinKey){
			if(isset($this->_parts[self::JOINS][$joinKey]))unset($this->_parts[self::JOINS][$joinKey]);
		}else{
			$this->_parts[self::JOINS] = self::$_partsInit[self::JOINS];
		}
		return $this;
	}

	/**
	 * @param string $part OPTIONAL
	 * @return Ddm_Db_Select
	 */
	public function reset($part = NULL){
		if($part===NULL){
			$this->_parts = self::$_partsInit;
		}else if(isset(self::$_partsInit[$part])){
			if($part==self::COLUMNS)$this->resetColumns();
			else $this->_parts[$part] = self::$_partsInit[$part];
		}
		return $this;
	}

	/**
	 * @param string $part
	 * @return mixed
	 * @throws Exception
	 */
	public function getPart($part){
		if (!isset($this->_parts[$part])) {
			throw new Exception("Invalid Select part '$part'");
		}
		return $this->_parts[$part];
	}

	/**
	 * @param string $part
	 * @param mixed $value
	 * @return Ddm_Db_Select
	 * @throws Zend_Db_Select_Exception
	 */
	public function setPart($part, $value){
		if (!isset($this->_parts[$part])) {
			throw new Exception("Invalid Select part '{$part}'");
		}
		$this->_parts[$part] = $value;
		return $this;
	}

	/**
	 * 使用例子:
	 * quoteInto('field','s') 结果: field='s'
	 * quoteInto(array('f1'=>1,'f2'=>'s')) 结果: f1='1' AND f2='s'
	 * quoteInto(array('f1'=>1,'f2'=>'s'),'or') 结果: f1='1' OR f2='s'
	 * quoteInto('field',array('in'=>array(1,2,3))) 结果: field IN('1','2','3'), 另: 也可以是notin, 如: array('notin'=>array(1,2,3))
	 * quoteInto('field',array('between'=>array(10,92))) 结果: field BETWEEN '10' AND '92', 当然也可以使用notbetween
	 * quoteInto('field',array('like'=>'%aa%')) 结果: field LIKE '%aa%', 当然也可以使用notlike
	 * quoteInto('field',array('<>'=>10)) 结果: field<>10
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return string
	 * @throws Exception
	 */
	public function quoteInto($fieldName, $value = NULL){
		if(is_array($fieldName)){
			$value = $value=='or' ? ' OR ' : ' AND ';
			$sql = '';
			foreach($fieldName as $key=>$val){
				$sql=='' or $sql .= $value;
				$sql .= is_int($key) ? $this->quoteInto($val) : $this->quoteInto($key,$val);
			}
		}else if($value===NULL && is_string($fieldName) && (strpos($fieldName,'=') || stripos($fieldName,' like '))){
			//为了安全考虑,不允许这样做,因为你或许在某些时候忘了注意存在SQL被注入的可能,请把值写在第二个参数
			throw new Exception('Parameter error');
		}else if($fieldName instanceof Ddm_Db_Expression && $value===NULL){
			$sql = $fieldName->__toString();
		}else{
			if(is_string($fieldName) && preg_match('/^\s*(?:`?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)`?\.)?`?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)`?\s*$/i',$fieldName,$matches)){
				$fieldName = new Ddm_Db_Expression(($matches[1]=='' ? '' : "`$matches[1]`.")."`$matches[2]`");
			}else if(!($fieldName instanceof Ddm_Db_Expression)){
				throw new Exception('Invalid field name: '.$fieldName);
			}
			if(is_array($value) && $value){
				$sql = $this->_quoteArrayValue($fieldName,$value);
			}else{
				$sql = $this->_getFieldName($fieldName).($value===NULL ? ' IS NULL' : '='.$this->_formatValue($value));
			}
		}
		return $sql;
	}

	/**
	 * @param string $primaryKey 主键字段名
	 * @return array
	 */
	public function fetchAll($primaryKey = NULL){
		return $this->getConnection()->fetchAll($this->getSql(),$primaryKey);
	}

	/**
	 * @param bool $getFirst 是否返回第一个字段的值
	 * @return array|mixed|false|null
	 */
	public function fetchOne($getFirst = false){
		return $this->getConnection()->fetchOne($this->getSql(),$getFirst);
	}

	/**
	 * @return array
	 */
	public function fetchPairs(){
		return $this->getConnection()->fetchPairs($this->getSql());
	}

	/**
	 * @param array|string $column
	 * @param string $table  表的别名(如果你给表使用了别名的话)
	 * @return string
	 */
	public function parseColumn($column,$table = ''){
		if(is_array($column)){
			$columns = '';
			foreach($column as $alias=>$field){
				$columns=='' or $columns .= ',';
				$columns .= $this->_getFieldName($field,$table).(is_numeric($alias) ? '' : " AS `$alias`");
			}
		}else{
			$columns = $this->_getFieldName($column,$table);
		}
		return $columns;
	}

	/**
	 * @return string
	 */
	public function getSql(){
		$joinSql = '';
		$columns = '';
		if($this->_parts[self::COLUMNS])$columns = implode(',',$this->_parts[self::COLUMNS]);
		if($this->_parts[self::JOINS]){
			foreach($this->_parts[self::JOINS] as $join){
				$joinSql .= "\r\n ".$join['join_type'].' '.$join['table'].' ON '.$join['on'];
				if($join['field']){
					$columns=='' or $columns .= ',';
					$columns .= $this->parseColumn($join['field'],$join['_table']);
				}
			}
		}
		if(!$this->_parts[self::FROM])throw new Exception('Table undefined.');
		else if(''==$columns)throw new Exception('Does not define any column.');
		$sql = 'SELECT '.($this->_parts[self::DISTINCT] ? 'DISTINCT ' : '').$columns.' FROM '.implode(',',$this->_parts[self::FROM]).$joinSql;
		if($this->_parts[self::WHERE])$sql .= "\r\n WHERE ".implode(' AND ',$this->_parts[self::WHERE]);
		if($this->_parts[self::GROUP]){
			$sql .= ' GROUP BY '.implode(',',$this->_parts[self::GROUP]);
			if($this->_parts[self::HAVING])$sql .= " HAVING ".implode(' AND ',$this->_parts[self::HAVING]);
		}
		if($this->_parts[self::ORDER])$sql .= ' ORDER BY '.implode(',',$this->_parts[self::ORDER]);
		$sql .= $this->_parts[self::LIMIT];

		return $sql;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		return $this->getSql();
	}
}
