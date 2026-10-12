<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Schema {
	/** @var Ddm_Db_Interface */
	protected $_connection;

	/** @var string */
	protected $_tablePrefix;

	/** @var Ddm_Db_Schema_Grammar */
	protected $_grammar;

	/**
	 * @param Ddm_Db_Interface $connection
	 * @param string $tablePrefix
	 * @param Ddm_Db_Schema_Grammar $grammar
	 * @return void
	 */
	public function __construct(Ddm_Db_Interface $connection, $tablePrefix, Ddm_Db_Schema_Grammar $grammar){
		$this->_connection = $connection;
		$this->_tablePrefix = $tablePrefix;
		$this->_grammar = $grammar;
	}

	/**
	 * 创建数据表
	 * @param string $table
	 * @param callable(Ddm_Db_Schema_Table $table): void $callback
	 * @return PDOStatement|false
	 */
	public function create($table, $callback){
		if(!is_callable($callback)){
			throw new InvalidArgumentException('The table definition must be callable.');
		}

		$table = $this->_getTableName($table);
		$definition = new Ddm_Db_Schema_Table($this->_grammar, $this->_tablePrefix);
		call_user_func($callback, $definition);

		$statements = $this->_grammar->compileCreate($table, $definition, false);
		return $this->_runStatements($statements);
	}

	/**
	 * 创建数据表；表已存在时不执行操作
	 * @param string $table
	 * @param callable(Ddm_Db_Schema_Table $table): void $callback
	 * @return PDOStatement|false
	 */
	public function createIfNotExists($table, $callback){
		if(!is_callable($callback)){
			throw new InvalidArgumentException('The table definition must be callable.');
		}

		$table = $this->_getTableName($table);
		$definition = new Ddm_Db_Schema_Table($this->_grammar, $this->_tablePrefix);
		call_user_func($callback, $definition);
		return $this->_runStatements($this->_grammar->compileCreate($table, $definition, true));
	}

	/**
	 * 删除数据表
	 * @param string $table
	 * @return PDOStatement|false
	 */
	public function drop($table){
		return $this->_connection->query($this->_grammar->compileDrop($this->_getTableName($table), false));
	}

	/**
	 * 删除数据表；表不存在时不报错
	 * @param string $table
	 * @return PDOStatement|false
	 */
	public function dropIfExists($table){
		return $this->_connection->query($this->_grammar->compileDrop($this->_getTableName($table), true));
	}

	/**
	 * 重命名数据表
	 * @param string $from
	 * @param string $to
	 * @return PDOStatement|false
	 */
	public function rename($from, $to){
		return $this->_connection->query($this->_grammar->compileRename(
			$this->_getTableName($from),
			$this->_getTableName($to)
		));
	}

	/**
	 * 判断数据表是否存在
	 * @param string $table
	 * @return bool
	 */
	public function hasTable($table){
		$statement = $this->_grammar->compileHasTable($this->_getTableName($table));
		$result = $this->_connection->query($statement['sql'], $statement['params']);
		return (bool)$result->fetch(PDO::FETCH_NUM);
	}

	/**
	 * 判断数据表是否包含指定字段
	 * @param string $table
	 * @param string|array $columns
	 * @return bool
	 */
	public function hasColumn($table, $columns){
		$actual = $this->getColumnListing($table);
		foreach((array)$columns as $column){
			if(!in_array($column, $actual, true))return false;
		}
		return true;
	}

	/**
	 * 判断数据表是否包含所有指定字段
	 * @param string $table
	 * @param string[] $columns
	 * @return bool
	 */
	public function hasColumns($table, array $columns){
		return $this->hasColumn($table, $columns);
	}

	/**
	 * 获取数据表字段列表
	 * @param string $table
	 * @return string[]
	 */
	public function getColumnListing($table){
		$statement = $this->_grammar->compileColumnListing($this->_getTableName($table));
		$result = $this->_connection->query($statement['sql'], $statement['params']);
		$columns = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			$columns[] = $row['name'];
		}
		return $columns;
	}

	/**
	 * 获取当前数据库的数据表名称
	 * @return string[]
	 */
	public function getTableListing(){
		$statement = $this->_grammar->compileTableListing();
		$result = $this->_connection->query($statement['sql'], $statement['params']);
		$tables = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			$tables[] = $row['name'];
		}
		return $tables;
	}

	/**
	 * 删除当前数据库中的所有数据表
	 * @return void
	 */
	public function dropAllTables(){
		$tables = $this->getTableListing();
		$connection = $this->_connection;
		$grammar = $this->_grammar;
		$this->withoutForeignKeyConstraints(function() use ($tables, $connection, $grammar){
			foreach($tables as $table){
				$connection->query($grammar->compileDrop($table, true));
			}
		});
	}

	/**
	 * 修改数据表
	 * @param string $table
	 * @param callable(Ddm_Db_Schema_Table $table): void $callback
	 * @return PDOStatement|false
	 */
	public function table($table, $callback){
		if(!is_callable($callback)){
			throw new InvalidArgumentException('The table definition must be callable.');
		}

		$table = $this->_getTableName($table);
		$definition = new Ddm_Db_Schema_Table($this->_grammar, $this->_tablePrefix);
		call_user_func($callback, $definition);
		return $this->_runStatements($this->_grammar->compileAlter($table, $definition));
	}

	/**
	 * 禁用外键约束检查
	 * @return PDOStatement|false
	 */
	public function disableForeignKeyConstraints(){
		return $this->_connection->query($this->_grammar->compileForeignKeyChecks(false));
	}

	/**
	 * 启用外键约束检查
	 * @return PDOStatement|false
	 */
	public function enableForeignKeyConstraints(){
		return $this->_connection->query($this->_grammar->compileForeignKeyChecks(true));
	}

	/**
	 * 在回调执行期间禁用外键约束，并在结束后恢复
	 * @param callable $callback
	 * @return mixed
	 */
	public function withoutForeignKeyConstraints($callback){
		if(!is_callable($callback)){
			throw new InvalidArgumentException('The foreign key callback must be callable.');
		}

		$this->disableForeignKeyConstraints();
		try{
			$result = call_user_func($callback);
		}catch(Exception $e){
			$this->enableForeignKeyConstraints();
			throw $e;
		}
		$this->enableForeignKeyConstraints();
		return $result;
	}

	/**
	 * @param string $table
	 * @return string
	 */
	protected function _getTableName($table){
		return $this->_tablePrefix.$table;
	}

	/**
	 * @param string[] $statements
	 * @return PDOStatement|false
	 */
	protected function _runStatements(array $statements){
		$result = false;
		foreach($statements as $sql){
			$result = $this->_connection->query($sql);
		}
		return $result;
	}
}
