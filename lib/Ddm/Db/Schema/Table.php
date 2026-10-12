<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Schema_Table {
	/** @var Ddm_Db_Schema_Grammar */
	protected $_grammar;

	/** @var Ddm_Db_Schema_Column[] */
	protected $_columns = array();

	/** @var array */
	protected $_primary = array();

	/** @var array */
	protected $_indexes = array();

	/** @var Ddm_Db_Schema_Foreign[] */
	protected $_foreignKeys = array();

	/** @var array */
	protected $_commands = array();

	/** @var array */
	protected $_options = array();

	/** @var string */
	protected $_tablePrefix;

	/**
	 * @param Ddm_Db_Schema_Grammar $grammar
	 * @param string $tablePrefix
	 * @return void
	 */
	public function __construct(Ddm_Db_Schema_Grammar $grammar, $tablePrefix = ''){
		$this->_grammar = $grammar;
		$this->_tablePrefix = $tablePrefix;
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return void
	 */
	public function morphs($name, $indexName = NULL){
		$this->string($name.'_type');
		$this->foreignId($name.'_id');
		$this->index(array($name.'_type', $name.'_id'), $indexName);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return void
	 */
	public function nullableMorphs($name, $indexName = NULL){
		$this->string($name.'_type')->nullable();
		$this->foreignId($name.'_id')->nullable();
		$this->index(array($name.'_type', $name.'_id'), $indexName);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return void
	 */
	public function uuidMorphs($name, $indexName = NULL){
		$this->string($name.'_type');
		$this->uuid($name.'_id');
		$this->index(array($name.'_type', $name.'_id'), $indexName);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return void
	 */
	public function nullableUuidMorphs($name, $indexName = NULL){
		$this->string($name.'_type')->nullable();
		$this->uuid($name.'_id')->nullable();
		$this->index(array($name.'_type', $name.'_id'), $indexName);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return void
	 */
	public function ulidMorphs($name, $indexName = NULL){
		$this->string($name.'_type');
		$this->ulid($name.'_id');
		$this->index(array($name.'_type', $name.'_id'), $indexName);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return void
	 */
	public function nullableUlidMorphs($name, $indexName = NULL){
		$this->string($name.'_type')->nullable();
		$this->ulid($name.'_id')->nullable();
		$this->index(array($name.'_type', $name.'_id'), $indexName);
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function increments($name){
		return $this->_addColumn($name, 'increments')->unsigned()->autoIncrement()->primary();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function tinyIncrements($name){
		return $this->_addColumn($name, 'tinyIncrements')->unsigned()->autoIncrement()->primary();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function smallIncrements($name){
		return $this->_addColumn($name, 'smallIncrements')->unsigned()->autoIncrement()->primary();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function mediumIncrements($name){
		return $this->_addColumn($name, 'mediumIncrements')->unsigned()->autoIncrement()->primary();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function bigIncrements($name){
		return $this->_addColumn($name, 'bigIncrements')->unsigned()->autoIncrement()->primary();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function id($name = 'id'){
		return $this->increments($name);
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function integer($name){
		return $this->_addColumn($name, 'integer');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function bigInteger($name){
		return $this->_addColumn($name, 'bigInteger');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function smallInteger($name){
		return $this->_addColumn($name, 'smallInteger');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function mediumInteger($name){
		return $this->_addColumn($name, 'mediumInteger');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function tinyInteger($name){
		return $this->_addColumn($name, 'tinyInteger');
	}

	/**
	 * @param string $name
	 * @param int $length
	 * @return Ddm_Db_Schema_Column
	 */
	public function string($name, $length = 255){
		return $this->_addColumn($name, 'string', array('length' => $length));
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function text($name){
		return $this->_addColumn($name, 'text');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function tinyText($name){
		return $this->_addColumn($name, 'tinyText');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function mediumText($name){
		return $this->_addColumn($name, 'mediumText');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function longText($name){
		return $this->_addColumn($name, 'longText');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function boolean($name){
		return $this->_addColumn($name, 'boolean');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function date($name){
		return $this->_addColumn($name, 'date');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function time($name){
		return $this->_addColumn($name, 'time');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function dateTime($name){
		return $this->_addColumn($name, 'dateTime');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function timestamp($name){
		return $this->_addColumn($name, 'timestamp');
	}

	/**
	 * @param string $name
	 * @param int $precision
	 * @return Ddm_Db_Schema_Column
	 */
	public function timestampTz($name, $precision = 0){
		return $this->_addColumn($name, 'timestampTz', array('precision' => $precision));
	}

	/**
	 * @param string $name
	 * @param int $precision
	 * @return Ddm_Db_Schema_Column
	 */
	public function dateTimeTz($name, $precision = 0){
		return $this->_addColumn($name, 'dateTimeTz', array('precision' => $precision));
	}

	/**
	 * @param string $name
	 * @param int $precision
	 * @return Ddm_Db_Schema_Column
	 */
	public function timeTz($name, $precision = 0){
		return $this->_addColumn($name, 'timeTz', array('precision' => $precision));
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function foreignUuid($name){
		return $this->uuid($name);
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function foreignUlid($name){
		return $this->ulid($name);
	}

	/**
	 * @param string $name
	 * @param int $precision
	 * @param int $scale
	 * @return Ddm_Db_Schema_Column
	 */
	public function float($name, $precision = 8, $scale = 2){
		return $this->_addColumn($name, 'float', array('precision' => $precision, 'scale' => $scale));
	}

	/**
	 * @param string $name
	 * @param int $precision
	 * @param int $scale
	 * @return Ddm_Db_Schema_Column
	 */
	public function double($name, $precision = 15, $scale = 2){
		return $this->_addColumn($name, 'double', array('precision' => $precision, 'scale' => $scale));
	}

	/**
	 * @param string $name
	 * @param int $precision
	 * @param int $scale
	 * @return Ddm_Db_Schema_Column
	 */
	public function decimal($name, $precision = 8, $scale = 2){
		return $this->_addColumn($name, 'decimal', array('precision' => $precision, 'scale' => $scale));
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function binary($name){
		return $this->_addColumn($name, 'binary');
	}

	/**
	 * @param string $name
	 * @param int $length
	 * @return Ddm_Db_Schema_Column
	 */
	public function char($name, $length = 255){
		return $this->_addColumn($name, 'char', array('length' => $length));
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function uuid($name){
		return $this->_addColumn($name, 'uuid');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function ulid($name){
		return $this->_addColumn($name, 'ulid');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function json($name){
		return $this->_addColumn($name, 'json');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function jsonb($name){
		return $this->_addColumn($name, 'jsonb');
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function year($name){
		return $this->_addColumn($name, 'year');
	}

	/**
	 * @param string $name
	 * @param string[] $allowed
	 * @return Ddm_Db_Schema_Column
	 */
	public function enumeration($name, array $allowed){
		return $this->_addColumn($name, 'enum', array('allowed' => $allowed));
	}

	/**
	 * @param string $name
	 * @param string[] $allowed
	 * @return Ddm_Db_Schema_Column
	 */
	public function enum($name, array $allowed){
		return $this->enumeration($name, $allowed);
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function foreignId($name){
		return $this->unsignedBigInteger($name);
	}

	/**
	 * @param string $name
	 * @param string $table
	 * @param string $column
	 * @param string|null $indexName
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function constrainedForeignId($name, $table, $column = 'id', $indexName = NULL){
		return $this->foreignId($name)->constrained($table, $column, $indexName);
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function unsignedBigInteger($name){
		return $this->bigInteger($name)->unsigned();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function unsignedInteger($name){
		return $this->integer($name)->unsigned();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function unsignedTinyInteger($name){
		return $this->tinyInteger($name)->unsigned();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function unsignedSmallInteger($name){
		return $this->smallInteger($name)->unsigned();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function unsignedMediumInteger($name){
		return $this->mediumInteger($name)->unsigned();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function softDeletes($name = 'deleted_at'){
		return $this->timestamp($name)->nullable();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function softDeletesTz($name = 'deleted_at'){
		return $this->timestampTz($name)->nullable();
	}

	/**
	 * @param string $name
	 * @return Ddm_Db_Schema_Column
	 */
	public function rememberToken($name = 'remember_token'){
		return $this->string($name, 100)->nullable();
	}

	/**
	 * 添加Laravel风格的created_at和updated_at字段
	 * @return void
	 */
	public function timestamps(){
		$this->timestamp('created_at')->nullable();
		$this->timestamp('updated_at')->nullable();
	}

	/**
	 * @return void
	 */
	public function timestampsTz(){
		$this->timestampTz('created_at')->nullable();
		$this->timestampTz('updated_at')->nullable();
	}

	/**
	 * @return void
	 */
	public function nullableTimestamps(){
		$this->timestamps();
	}

	/**
	 * @return void
	 */
	public function nullableTimestampsTz(){
		$this->timestampsTz();
	}

	/**
	 * @param string|array $columns
	 * @return Ddm_Db_Schema_Table
	 */
	public function primary($columns){
		$this->_primary = array_merge($this->_primary, (array)$columns);
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function unique($columns, $name = NULL){
		$this->_indexes[] = array('columns' => (array)$columns, 'unique' => true, 'name' => $name, 'type' => 'unique');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function index($columns, $name = NULL){
		$this->_indexes[] = array('columns' => (array)$columns, 'unique' => false, 'name' => $name, 'type' => 'index');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function fullText($columns, $name = NULL){
		$this->_indexes[] = array('columns' => (array)$columns, 'unique' => false, 'name' => $name, 'type' => 'fulltext');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function spatialIndex($columns, $name = NULL){
		$this->_indexes[] = array('columns' => (array)$columns, 'unique' => false, 'name' => $name, 'type' => 'spatial');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function foreign($columns, $name = NULL){
		$foreign = new Ddm_Db_Schema_Foreign((array)$columns, $name);
		$this->_foreignKeys[] = $foreign;
		return $foreign;
	}

	/**
	 * @param string $engine
	 * @return Ddm_Db_Schema_Table
	 */
	public function engine($engine){
		$this->_options['engine'] = $engine;
		return $this;
	}

	/**
	 * @param string $charset
	 * @return Ddm_Db_Schema_Table
	 */
	public function charset($charset){
		$this->_options['charset'] = $charset;
		return $this;
	}

	/**
	 * @param string $collation
	 * @return Ddm_Db_Schema_Table
	 */
	public function collation($collation){
		$this->_options['collation'] = $collation;
		return $this;
	}

	/**
	 * @param string $comment
	 * @return Ddm_Db_Schema_Table
	 */
	public function comment($comment){
		$this->_options['comment'] = $comment;
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropColumn($columns){
		$this->_commands[] = array('name' => 'dropColumn', 'columns' => (array)$columns);
		return $this;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @return Ddm_Db_Schema_Table
	 */
	public function renameColumn($from, $to){
		$this->_commands[] = array('name' => 'renameColumn', 'from' => $from, 'to' => $to);
		return $this;
	}

	/**
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropTimestamps(){
		return $this->dropColumn(array('created_at', 'updated_at'));
	}

	/**
	 * @param string $column
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropSoftDeletes($column = 'deleted_at'){
		return $this->dropColumn($column);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropMorphs($name, $indexName = NULL){
		$columns = array($name.'_type', $name.'_id');
		$this->dropIndex($columns, $indexName);
		return $this->dropColumn($columns);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropUuidMorphs($name, $indexName = NULL){
		return $this->dropMorphs($name, $indexName);
	}

	/**
	 * @param string $name
	 * @param string|null $indexName
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropUlidMorphs($name, $indexName = NULL){
		return $this->dropMorphs($name, $indexName);
	}

	/**
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropPrimary($name = NULL){
		$this->_commands[] = array('name' => 'dropPrimary', 'index' => $name);
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropUnique($columns, $name = NULL){
		$this->_commands[] = array('name' => 'dropUnique', 'columns' => $name===NULL && is_string($columns) ? array() : (array)$columns, 'index' => $name===NULL && is_string($columns) ? $columns : $name, 'indexType' => 'unique');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropIndex($columns, $name = NULL){
		$this->_commands[] = array('name' => 'dropIndex', 'columns' => $name===NULL && is_string($columns) ? array() : (array)$columns, 'index' => $name===NULL && is_string($columns) ? $columns : $name, 'indexType' => 'index');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropFullText($columns, $name = NULL){
		$this->_commands[] = array('name' => 'dropIndex', 'columns' => $name===NULL && is_string($columns) ? array() : (array)$columns, 'index' => $name===NULL && is_string($columns) ? $columns : $name, 'indexType' => 'fulltext');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropSpatialIndex($columns, $name = NULL){
		$this->_commands[] = array('name' => 'dropIndex', 'columns' => $name===NULL && is_string($columns) ? array() : (array)$columns, 'index' => $name===NULL && is_string($columns) ? $columns : $name, 'indexType' => 'spatial');
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @param string|null $name
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropForeign($columns, $name = NULL){
		$this->_commands[] = array('name' => 'dropForeign', 'columns' => $name===NULL && is_string($columns) ? array() : (array)$columns, 'index' => $name===NULL && is_string($columns) ? $columns : $name, 'indexType' => 'foreign');
		return $this;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @return Ddm_Db_Schema_Table
	 */
	public function renameIndex($from, $to){
		$this->_commands[] = array('name' => 'renameIndex', 'from' => $from, 'to' => $to);
		return $this;
	}

	/**
	 * @param string|array $columns
	 * @return Ddm_Db_Schema_Table
	 */
	public function dropConstrainedForeignId($columns){
		$columns = (array)$columns;
		$this->dropForeign($columns);
		return $this->dropColumn($columns);
	}

	/**
	 * @return array
	 */
	public function compileColumns(){
		$columns = array();
		foreach($this->_columns as $column){
			$columns[] = $this->_grammar->compileColumn($column);
			if($column->isPrimary() && !in_array($column->getName(), $this->_primary, true)){
				$this->_primary[] = $column->getName();
			}
		}

		if($this->_primary){
			$primary = $this->_grammar->compilePrimary($this->_primary, $this->_hasAutoIncrementPrimary());
			if($primary!==NULL)$columns[] = $primary;
		}
		return $columns;
	}

	/**
	 * @return Ddm_Db_Schema_Column[]
	 */
	public function getColumns(){
		return $this->_columns;
	}

	/**
	 * @return Ddm_Db_Schema_Foreign[]
	 */
	public function getForeignKeys(){
		return $this->_foreignKeys;
	}

	/**
	 * @return array
	 */
	public function getCommands(){
		return $this->_commands;
	}

	/**
	 * @return array
	 */
	public function getOptions(){
		return $this->_options;
	}

	/**
	 * @return string
	 */
	public function getTablePrefix(){
		return $this->_tablePrefix;
	}

	/**
	 * @param string $table
	 * @return array
	 */
	public function compileIndexes($table){
		$queries = array();
		foreach($this->getIndexDefinitions($table) as $index){
			$name = $index['name'];
			$queries[] = $this->_grammar->compileIndex($table, $name, $index['columns'], $index['unique'], $index['type']);
		}
		return $queries;
	}

	/**
	 * @param string $table
	 * @return array
	 */
	public function getIndexDefinitions($table){
		$indexes = $this->_indexes;
		foreach($this->_columns as $column){
			if($column->isUnique() || $column->hasIndex()){
				$indexes[] = array('columns' => array($column->getName()), 'unique' => $column->isUnique(), 'name' => NULL, 'type' => $column->isUnique() ? 'unique' : 'index');
			}
		}

		foreach($indexes as &$index){
			if($index['name']===NULL){
				$suffix = $index['type']==='fulltext' ? '_fulltext' : ($index['type']==='spatial' ? '_spatialindex' : ($index['unique'] ? '_unique' : '_index'));
				$index['name'] = $table.'_'.implode('_', $index['columns']).$suffix;
			}
		}
		unset($index);
		return $indexes;
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @param array $options
	 * @return Ddm_Db_Schema_Column
	 */
	protected function _addColumn($name, $type, array $options = array()){
		$column = new Ddm_Db_Schema_Column($name, $type, $options, $this);
		$this->_columns[] = $column;
		return $column;
	}

	/**
	 * @return bool
	 */
	protected function _hasAutoIncrementPrimary(){
		foreach($this->_columns as $column){
			if($column->isAutoIncrement() && $column->isPrimary())return true;
		}
		return false;
	}
}
