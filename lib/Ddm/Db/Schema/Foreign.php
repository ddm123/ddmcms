<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Schema_Foreign {
	/** @var string[] */
	protected $_columns;

	/** @var string|null */
	protected $_name;

	/** @var string[] */
	protected $_references = array();

	/** @var string|null */
	protected $_onTable;

	/** @var string|null */
	protected $_onDelete;

	/** @var string|null */
	protected $_onUpdate;

	/**
	 * @param string[] $columns
	 * @param string|null $name
	 * @return void
	 */
	public function __construct(array $columns, $name = NULL){
		$this->_columns = $columns;
		$this->_name = $name;
	}

	/**
	 * @param string|array $columns
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function references($columns){
		$this->_references = (array)$columns;
		return $this;
	}

	/**
	 * @param string $table
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function on($table){
		$this->_onTable = $table;
		return $this;
	}

	/**
	 * @param string $action
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function onDelete($action){
		$this->_onDelete = $this->_normalizeAction($action);
		return $this;
	}

	/**
	 * @param string $action
	 * @return Ddm_Db_Schema_Foreign
	 */
	public function onUpdate($action){
		$this->_onUpdate = $this->_normalizeAction($action);
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getColumns(){
		return $this->_columns;
	}

	/**
	 * @return string|null
	 */
	public function getName(){
		return $this->_name;
	}

	/**
	 * @return string[]
	 */
	public function getReferences(){
		return $this->_references;
	}

	/**
	 * @return string|null
	 */
	public function getOnTable(){
		return $this->_onTable;
	}

	/**
	 * @return string|null
	 */
	public function getOnDelete(){
		return $this->_onDelete;
	}

	/**
	 * @return string|null
	 */
	public function getOnUpdate(){
		return $this->_onUpdate;
	}

	/**
	 * @param string $action
	 * @return string
	 */
	protected function _normalizeAction($action){
		$action = strtoupper(trim($action));
		$allowed = array('CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION', 'SET DEFAULT');
		if(!in_array($action, $allowed, true)){
			throw new InvalidArgumentException('Unsupported foreign key action: '.$action);
		}
		return $action;
	}
}
