<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Builder {
	/** @var string 标识符引用符, 由数据库驱动决定, 默认MySQL反引号 */
	protected $_identifierQuote = '`';

	/** @var string 表名(已带表前缀) */
	protected $_table = '';

	/** @var string|null 表别名 */
	protected $_tableAlias = NULL;

	/** @var array 查询的字段列表(空数组表示*) */
	protected $_columns = array();

	/** @var Ddm_Db_JoinClause[] 表连接列表 */
	protected $_joins = array();

	/** @var array WHERE条件列表 */
	protected $_wheres = array();

	/** @var array 排序列表 */
	protected $_orders = array();

	/** @var array 分组列表 */
	protected $_groups = array();

	/** @var array HAVING条件列表 */
	protected $_havings = array();

	/** @var int|null 限制返回的行数 */
	protected $_limit = NULL;

	/** @var int|null 偏移量 */
	protected $_offset = NULL;

	/**
	 * @param string $table 已带表前缀的表名
	 * @param string|null $alias 表别名
	 */
	public function __construct($table,$alias = NULL){
		$this->_table = $table;
		$this->_tableAlias = $alias;
	}

	/**
	 * @param string|array $columns
	 * @return Ddm_Db_Builder
	 */
	public function select($columns = array()){
		$this->_columns = is_array($columns) ? $columns : func_get_args();
		return $this;
	}

	/**
	 * 追加查询字段(而不是覆盖)
	 * @param string|array $columns
	 * @return Ddm_Db_Builder
	 */
	public function addSelect($columns = array()){
		$this->_columns = array_merge($this->_columns,is_array($columns) ? $columns : func_get_args());
		return $this;
	}

	/**
	 * 查询多行
	 * @param string|array $columns
	 * @return array
	 */
	public function get($columns = array()){
		if($columns)$this->select($columns);
		$bindings = array();
		$sql = $this->_compileSelect($bindings);
		return $this->_read($sql,$bindings)->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * 查询一行
	 * @param string|array $columns
	 * @return array|null
	 */
	public function first($columns = array()){
		$rows = $this->limit(1)->get($columns);
		return $rows ? $rows[0] : NULL;
	}

	/**
	 * 通过主键查询一行
	 * @param mixed $id
	 * @param string $column
	 * @return array|null
	 */
	public function find($id,$column = 'id'){
		return $this->where($column,'=',$id)->first();
	}

	/**
	 * 获取某一列的值列表
	 * @param string $column
	 * @param string|null $key
	 * @return array
	 */
	public function pluck($column,$key = NULL){
		$columns = $key===NULL ? array($column) : array($column,$key);
		$rows = $this->get($columns);
		$result = array();
		foreach($rows as $row){
			if($key===NULL)$result[] = $row[$column];
			else $result[$row[$key]] = $row[$column];
		}
		return $result;
	}

	/**
	 * 获取单个值
	 * @param string $column
	 * @return mixed|null
	 */
	public function value($column){
		$row = $this->first(array($column));
		return $row ? reset($row) : NULL;
	}

	/**
	 * 获取当前SELECT语句(不含绑定值), 便于调试
	 * @return string
	 */
	public function toSql(){
		$bindings = array();
		return $this->_compileSelect($bindings);
	}

	/**
	 * 统计行数
	 * @param string $columns
	 * @return int
	 */
	public function count($columns = '*'){
		$bindings = array();
		$column = $columns==='*' ? '*' : ($columns instanceof Ddm_Db_Expression ? (string)$columns : $this->wrap($columns));
		$sql = 'SELECT COUNT('.$column.') AS '.$this->wrap('aggregate').' '.$this->_compileFrom($bindings);
		if($this->_groups)$sql .= ' GROUP BY '.$this->_compileGroups();
		if($this->_havings)$sql .= ' HAVING '.$this->_compileHavings($bindings);
		$row = $this->_read($sql,$bindings)->fetch(PDO::FETCH_ASSOC);
		return (int)$row['aggregate'];
	}

	/**
	 * 是否存在记录
	 * @return bool
	 */
	public function exists(){
		$bindings = array();
		$row = $this->_read('SELECT 1 '.$this->_compileFrom($bindings).' LIMIT 1',$bindings)->fetch(PDO::FETCH_ASSOC);
		return $row ? true : false;
	}

	/**
	 * @param string|Closure $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	public function where($column,$operator = NULL,$value = NULL,$boolean = 'and'){
		if($column instanceof Closure)return $this->_whereNested($column,$boolean);
		if(func_num_args()===2){ $value = $operator; $operator = '='; }
		return $this->_addWhere('basic',$column,$operator,$value,$boolean);
	}

	/**
	 * @param string|Closure $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @return Ddm_Db_Builder
	 */
	public function orWhere($column,$operator = NULL,$value = NULL){
		if($column instanceof Closure)return $this->_whereNested($column,'or');
		if(func_num_args()===2){ $value = $operator; $operator = '='; }
		return $this->_addWhere('basic',$column,$operator,$value,'or');
	}

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $boolean
	 * @param bool $not
	 * @return Ddm_Db_Builder
	 */
	public function whereIn($column,$values,$boolean = 'and',$not = false){
		return $this->_addWhere($not ? 'notin' : 'in',$column,NULL,(array)$values,$boolean);
	}

	/**
	 * @param string $column
	 * @param array $values
	 * @return Ddm_Db_Builder
	 */
	public function orWhereIn($column,$values){ return $this->whereIn($column,$values,'or'); }

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	public function whereNotIn($column,$values,$boolean = 'and'){ return $this->whereIn($column,$values,$boolean,true); }

	/**
	 * @param string $column
	 * @param array $values
	 * @return Ddm_Db_Builder
	 */
	public function orWhereNotIn($column,$values){ return $this->whereNotIn($column,$values,'or'); }

	/**
	 * @param string $column
	 * @param string $boolean
	 * @param bool $not
	 * @return Ddm_Db_Builder
	 */
	public function whereNull($column,$boolean = 'and',$not = false){
		return $this->_addWhere($not ? 'notnull' : 'null',$column,NULL,NULL,$boolean);
	}

	/**
	 * @param string $column
	 * @return Ddm_Db_Builder
	 */
	public function orWhereNull($column){ return $this->whereNull($column,'or'); }

	/**
	 * @param string $column
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	public function whereNotNull($column,$boolean = 'and'){ return $this->whereNull($column,$boolean,true); }

	/**
	 * @param string $column
	 * @return Ddm_Db_Builder
	 */
	public function orWhereNotNull($column){ return $this->whereNotNull($column,'or'); }

	/**
	 * @param string|Ddm_Db_Expression $column
	 * @param string $direction
	 * @return Ddm_Db_Builder
	 */
	public function orderBy($column,$direction = 'asc'){
		$direction = strtoupper($direction);
		if($direction!=='ASC' && $direction!=='DESC')$direction = 'ASC';
		$this->_orders[] = ($column instanceof Ddm_Db_Expression ? (string)$column : $this->wrap($column)).' '.$direction;
		return $this;
	}

	/**
	 * @param string|array $groups
	 * @return Ddm_Db_Builder
	 */
	public function groupBy($groups){
		$this->_groups = array_merge($this->_groups,is_array($groups) ? $groups : func_get_args());
		return $this;
	}

	/**
	 * @param string $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	public function having($column,$operator = NULL,$value = NULL,$boolean = 'and'){
		if(func_num_args()===2){ $value = $operator; $operator = '='; }
		return $this->_addHaving($column,$operator,$value,$boolean);
	}

	/**
	 * @param string $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @return Ddm_Db_Builder
	 */
	public function orHaving($column,$operator = NULL,$value = NULL){
		if(func_num_args()===2){ $value = $operator; $operator = '='; }
		return $this->_addHaving($column,$operator,$value,'or');
	}

	/**
	 * @param int $value
	 * @param int|null $offset
	 * @return Ddm_Db_Builder
	 */
	public function limit($value,$offset = NULL){
		$this->_limit = (int)$value;
		if($offset!==NULL)$this->_offset = (int)$offset;
		return $this;
	}

	/**
	 * @param int $value
	 * @return Ddm_Db_Builder
	 */
	public function offset($value){
		$this->_offset = (int)$value;
		return $this;
	}

	/**
	 * @param string $table
	 * @param string|Closure $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @param string $type
	 * @return Ddm_Db_Builder
	 */
	public function join($table,$first,$operator = NULL,$second = NULL,$type = 'inner'){
		return $this->_addJoin($table,$first,$operator,$second,$type);
	}

	/**
	 * @param string $table
	 * @param string|Closure $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @return Ddm_Db_Builder
	 */
	public function leftJoin($table,$first,$operator = NULL,$second = NULL){
		return $this->_addJoin($table,$first,$operator,$second,'left');
	}

	/**
	 * @param string $table
	 * @param string|Closure $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @return Ddm_Db_Builder
	 */
	public function rightJoin($table,$first,$operator = NULL,$second = NULL){
		return $this->_addJoin($table,$first,$operator,$second,'right');
	}

	/**
	 * 新增一条记录
	 * @param array $values
	 * @return int
	 */
	public function insert(array $values){
		return Ddm_Db::getWriteConn()->insert($this->_table,$values);
	}

	/**
	 * 新增一条记录并返回自增ID
	 * @param array $values
	 * @return int|null
	 */
	public function insertGetId(array $values){
		$conn = Ddm_Db::getWriteConn();
		$rows = $conn->insert($this->_table,$values);
		return $rows ? $conn->getLastInsertId() : NULL;
	}

	/**
	 * 修改记录
	 * @param array $values
	 * @return int 受影响的行数
	 */
	public function update(array $values){
		$bindings = array();
		$sets = array();
		foreach($values as $column=>$value){
			if($value instanceof Ddm_Db_Expression){
				$sets[] = $this->wrap($column).' = '.$value;
			}else{
				$sets[] = $this->wrap($column).' = ?';
				$bindings[] = $value;
			}
		}
		if(!$sets)return 0;
		$wheres = $this->_compileWheres($bindings);
		$sql = 'UPDATE '.$this->wrap($this->_table).' SET '.implode(', ',$sets).($wheres ? ' WHERE '.$wheres : '');
		return Ddm_Db::getWriteConn()->query($sql,$bindings)->rowCount();
	}

	/**
	 * 删除记录
	 * @param mixed $id 传ID则按主键id删除
	 * @return int 受影响的行数
	 */
	public function delete($id = NULL){
		if($id!==NULL)$this->where('id','=',$id);
		$bindings = array();
		$wheres = $this->_compileWheres($bindings);
		$sql = 'DELETE FROM '.$this->wrap($this->_table).($wheres ? ' WHERE '.$wheres : '');
		return Ddm_Db::getWriteConn()->query($sql,$bindings)->rowCount();
	}

	/**
	 * 字段自增
	 * @param string $column
	 * @param int $amount
	 * @param array $extra
	 * @return int
	 */
	public function increment($column,$amount = 1,array $extra = array()){
		$amount = (float)$amount;
		$extra[$column] = new Ddm_Db_Expression($this->wrap($column).' + '.($amount<0 ? '('.$amount.')' : $amount));
		return $this->update($extra);
	}

	/**
	 * 字段自减
	 * @param string $column
	 * @param int $amount
	 * @param array $extra
	 * @return int
	 */
	public function decrement($column,$amount = 1,array $extra = array()){
		return $this->increment($column,-$amount,$extra);
	}

	/**
	 * @param string $sql
	 * @param array $bindings
	 * @return PDOStatement
	 */
	protected function _read($sql,array $bindings){
		return Ddm_Db::getReadConn()->query($sql,$bindings,PDO::FETCH_ASSOC);
	}

	/**
	 * @param string $type
	 * @param string|Ddm_Db_Expression $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	protected function _addWhere($type,$column,$operator,$value,$boolean){
		$this->_wheres[] = array('type'=>$type,'column'=>$column,'operator'=>$operator,'value'=>$value,'boolean'=>$boolean);
		return $this;
	}

	/**
	 * @param Closure $callback
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	protected function _whereNested($callback,$boolean){
		$query = new self($this->_table,$this->_tableAlias);
		$callback($query);
		$this->_wheres[] = array('type'=>'nested','query'=>$query,'boolean'=>$boolean);
		return $this;
	}

	/**
	 * @param string|Ddm_Db_Expression $column
	 * @param string|null $operator
	 * @param mixed $value
	 * @param string $boolean
	 * @return Ddm_Db_Builder
	 */
	protected function _addHaving($column,$operator,$value,$boolean){
		$this->_havings[] = array('column'=>$column,'operator'=>$operator,'value'=>$value,'boolean'=>$boolean);
		return $this;
	}

	/**
	 * @param string $table
	 * @param string|Closure $first
	 * @param string|null $operator
	 * @param string|null $second
	 * @param string $type
	 * @return Ddm_Db_Builder
	 */
	protected function _addJoin($table,$first,$operator,$second,$type){
		list($table,$alias,$isPrefixIncluded) = self::splitTableAlias($table);
		$join = new Ddm_Db_JoinClause($isPrefixIncluded ? $table : Ddm_Db::getTable($table),$type,$alias);
		if($first instanceof Closure)$first($join);
		else $join->on($first,$operator,$second);
		$this->_joins[] = $join;
		return $this;
	}

	/**
	 * @param array $bindings
	 * @return string
	 */
	protected function _compileSelect(&$bindings){
		$sql = 'SELECT '.$this->_compileColumns().' '.$this->_compileFrom($bindings);
		if($this->_groups)$sql .= ' GROUP BY '.$this->_compileGroups();
		if($this->_havings)$sql .= ' HAVING '.$this->_compileHavings($bindings);
		if($this->_orders)$sql .= ' ORDER BY '.implode(', ',$this->_orders);
		if($this->_limit!==NULL)$sql .= ' LIMIT '.$this->_limit;
		if($this->_offset!==NULL)$sql .= ' OFFSET '.$this->_offset;
		return $sql;
	}

	/**
	 * @param array $bindings
	 * @return string
	 */
	protected function _compileFrom(&$bindings){
		$sql = 'FROM '.$this->_compileTable($this->_table,$this->_tableAlias);
		foreach($this->_joins as $join)$sql .= ' '.$this->_compileJoin($bindings,$join);
		$wheres = $this->_compileWheres($bindings);
		if($wheres)$sql .= ' WHERE '.$wheres;
		return $sql;
	}

	/**
	 * @param array $bindings
	 * @param Ddm_Db_JoinClause $join
	 * @return string
	 */
	protected function _compileJoin(&$bindings,$join){
		return $join->getType().' JOIN '.$this->_compileTable($join->getTable(),$join->getTableAlias()).' ON '.$this->_compileJoinConditions($bindings,$join->getConditions());
	}

	/**
	 * @param array $bindings
	 * @param array $conditions
	 * @return string
	 */
	protected function _compileJoinConditions(&$bindings,$conditions){
		$parts = array();
		foreach($conditions as $i=>$condition){
			$boolean = $i===0 ? '' : strtoupper($condition['boolean']).' ';
			switch($condition['type']){
				case 'on':
					$parts[] = $boolean.$this->wrap($condition['first']).' '.$condition['operator'].' '.$this->wrap($condition['second']);
					break;
				case 'where':
					if($condition['second'] instanceof Ddm_Db_Expression){
						$parts[] = $boolean.$this->wrap($condition['first']).' '.$condition['operator'].' '.$condition['second'];
					}else{
						$parts[] = $boolean.$this->wrap($condition['first']).' '.$condition['operator'].' ?';
						$bindings[] = $condition['second'];
					}
					break;
				case 'nested':
					$sub = array();
					$parts[] = $boolean.'('.$this->_compileJoinConditions($sub,$condition['join']->getConditions()).')';
					$bindings = array_merge($bindings,$sub);
					break;
			}
		}
		return implode(' ',$parts);
	}

	/**
	 * @return string
	 */
	protected function _compileColumns(){
		if(empty($this->_columns))return '*';
		$columns = array();
		foreach($this->_columns as $alias=>$column){
			$columns[] = $this->_compileColumn($column,is_string($alias) ? $alias : NULL);
		}
		return implode(', ',$columns);
	}

	/**
	 * 编译单个查询字段, 支持别名(数组键别名或 "expr AS alias")
	 * @param string|Ddm_Db_Expression $column
	 * @param string|null $alias
	 * @return string
	 */
	protected function _compileColumn($column,$alias = NULL){
		if($column instanceof Ddm_Db_Expression){
			$sql = (string)$column;
		}else{
			list($expr,$inlineAlias) = $this->_splitColumnAlias((string)$column);
			if($alias===NULL)$alias = $inlineAlias;
			$sql = $this->wrap($expr);
		}
		return $alias===NULL ? $sql : $sql.' AS '.$this->wrap($alias);
	}

	/**
	 * 从字段表达式中拆分出表达式与别名
	 * @param string $column
	 * @return array array($expr, $alias)
	 */
	protected function _splitColumnAlias($column){
		$column = trim($column);
		$parts = preg_split('/\s+AS\s+/i',$column,2);
		if(count($parts)===2)return array(trim($parts[0]),trim($parts[1]));
		return array($column,NULL);
	}

	/**
	 * 编译表名, 支持别名
	 * @param string $table
	 * @param string|null $alias
	 * @return string
	 */
	protected function _compileTable($table,$alias = NULL){
		$sql = $this->wrap($table);
		if($alias!==NULL)$sql .= ' AS '.$this->wrap($alias);
		return $sql;
	}

	/**
	 * @param array $bindings
	 * @return string
	 */
	protected function _compileWheres(&$bindings){
		if(!$this->_wheres)return '';
		$parts = array();
		foreach($this->_wheres as $where){
			$sql = $this->_compileWhere($bindings,$where);
			if($sql==='')continue;
			$parts[] = (empty($parts) ? '' : strtoupper($where['boolean']).' ').$sql;
		}
		return implode(' ',$parts);
	}

	/**
	 * @param array $bindings
	 * @param array $where
	 * @return string
	 */
	protected function _compileWhere(&$bindings,$where){
		switch($where['type']){
			case 'nested':
				$sub = array();
				$sql = $where['query']->_compileWheres($sub);
				$bindings = array_merge($bindings,$sub);
				return $sql==='' ? '' : '('.$sql.')';
			case 'basic':
				return $this->_compileBasic($bindings,$where['column'],$where['operator'],$where['value']);
			case 'in':
			case 'notin':
				$values = $where['value'];
				if(empty($values))return $this->wrap($where['column']).($where['type']==='in' ? ' IS NULL' : ' IS NOT NULL');
				$placeholders = array();
				foreach($values as $value){
					if($value instanceof Ddm_Db_Expression)$placeholders[] = (string)$value;
					else { $placeholders[] = '?'; $bindings[] = $value; }
				}
				return $this->wrap($where['column']).($where['type']==='in' ? ' IN ' : ' NOT IN ').'('.implode(', ',$placeholders).')';
			case 'null':
			case 'notnull':
				return $this->wrap($where['column']).($where['type']==='null' ? ' IS NULL' : ' IS NOT NULL');
		}
		return '';
	}

	/**
	 * @param array $bindings
	 * @param string|Ddm_Db_Expression $column
	 * @param string $operator
	 * @param mixed $value
	 * @return string
	 */
	protected function _compileBasic(&$bindings,$column,$operator,$value){
		$column = $this->wrap($column);
		if($value instanceof Ddm_Db_Expression)return $column.' '.$operator.' '.$value;
		if($value===NULL){
			if($operator==='=' || $operator==='==')return $column.' IS NULL';
			if($operator==='!=' || $operator==='<>')return $column.' IS NOT NULL';
		}
		$bindings[] = $value;
		return $column.' '.$operator.' ?';
	}

	/**
	 * @return string
	 */
	protected function _compileGroups(){
		$groups = array();
		foreach($this->_groups as $group){
			$groups[] = $group instanceof Ddm_Db_Expression ? (string)$group : $this->wrap($group);
		}
		return implode(', ',$groups);
	}

	/**
	 * @param array $bindings
	 * @return string
	 */
	protected function _compileHavings(&$bindings){
		$parts = array();
		foreach($this->_havings as $i=>$having){
			$column = $having['column'] instanceof Ddm_Db_Expression ? (string)$having['column'] : $this->wrap($having['column']);
			if($having['value'] instanceof Ddm_Db_Expression){
				$parts[] = ($i===0 ? '' : strtoupper($having['boolean']).' ').$column.' '.$having['operator'].' '.$having['value'];
			}else{
				$parts[] = ($i===0 ? '' : strtoupper($having['boolean']).' ').$column.' '.$having['operator'].' ?';
				$bindings[] = $having['value'];
			}
		}
		return implode(' ',$parts);
	}

	/**
	 * 设置标识符引用符(不同数据库不同, MySQL为反引号, SQLite/PostgreSQL为双引号)
	 * @param string $quote
	 * @return Ddm_Db_Builder
	 */
	public function setIdentifierQuote($quote){
		$this->_identifierQuote = (string)$quote;
		return $this;
	}

	/**
	 * 从表名中拆分出表名与别名, 支持 array(别名=>表名) 或 "表名 as 别名"
	 * @param string|array $name
     * @param bool $isPrefixIncluded 表名是否已包含前缀
	 * @return array array($table, $alias, $isPrefixIncluded)
	 */
	public static function splitTableAlias($name, $isPrefixIncluded = false){
		$alias = NULL;
		if(is_array($name)){
			$i = 0;
			foreach ($name as $k => $v) {
				if ($i === 0) {
					$name = $v;
					if (is_string($k)) $alias = $k;
				} else {
					$isPrefixIncluded = (bool)$v;
					break;
				}
				$i++;
			}
		}else{
			$name = trim((string)$name);
			$parts = preg_split('/\s+AS\s+/i',$name,2);
			if(count($parts)===2){
				$name = trim($parts[0]);
				$alias = trim($parts[1]);
			}
		}
		return array($name,$alias,$isPrefixIncluded);
	}

	/**
	 * 给字段名或表名加上引用符, 防止SQL注入
	 * @param string|Ddm_Db_Expression $value
	 * @return string
	 */
	public function wrap($value){
		if($value instanceof Ddm_Db_Expression)return (string)$value;
		$value = (string)$value;
		if($value==='')return $value;
		$quote = $this->_identifierQuote;
		if(strpos($value,'.')!==false){
			$parts = explode('.',$value);
			foreach($parts as &$part)$part = $this->_wrapPart($part,$quote);
			return implode('.',$parts);
		}
		return $this->_wrapPart($value,$quote);
	}

	/**
	 * @param string $part
	 * @param string $quote
	 * @return string
	 */
	protected function _wrapPart($part,$quote){
		if($part==='*')return '*';
		if($part!=='' && $part[0]===$quote && substr($part,-1)===$quote)return $part;
		return $quote.str_replace($quote,$quote.$quote,$part).$quote;
	}
}
