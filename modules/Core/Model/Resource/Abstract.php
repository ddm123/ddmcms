<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Model_Resource_Abstract {
	private $_mainTableName = NULL;
	private $_primarykey = 'id';
	protected $_fields = array();
	protected $_select = NULL;
	protected $_modelObject = NULL;

	/**
	 * 必须使用该方法调用$this->_setTable($tableName,$primarykey);
	 * @return Core_Model_Abstract
	 */
	abstract protected function _init();

	public function __construct() {
		$this->_init();
	}

	/**
	 * @param string $mainTable
	 * @param string $field
	 * @param mixed $value
	 * @return Ddm_Db_Builder
	 */
	protected function _getLoadSelect($mainTable, $field, $value){
		$select = Ddm_Db::table(array('main_table'=>$mainTable));
		$select->where($field, '=', $value);
		return $select;
	}

	/**
	 * @return Ddm_Db_Builder
	 */
	protected function _getSelect(){
		$this->_select = Ddm_Db::table(array('main_table'=>$this->getMainTableName()));
		return $this->_select;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @return array
	 */
	protected function _getSaveData(Core_Model_Abstract $object){
		$dataValue = array();
		foreach($this->getFields($this->_mainTableName) as $key=>$row){
			if($object->issetData($key) && $object->$key!=$object->getOrigData($key))$dataValue[$key] = $object->$key;
		}
		return $dataValue;
	}

	/**
	 * 设置表名和主键字段名
	 * @param string $tableName
	 * @param string $primarykey
	 * @return Core_Model_Resource_Abstract
	 */
	protected function _setTable($tableName,$primarykey = 'id'){
		$this->_mainTableName = (string)$tableName;
		$this->_primarykey = (string)$primarykey;
		return $this;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @return Core_Model_Resource_Abstract
	 */
	protected function _beforeSave(Core_Model_Abstract $object){
		return $this;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @return Core_Model_Resource_Abstract
	 */
	protected function _afterSave(Core_Model_Abstract $object){
		return $this;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @return Core_Model_Resource_Abstract
	 */
	protected function _beforeDelete(Core_Model_Abstract $object){
		return $this;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @return Core_Model_Resource_Abstract
	 */
	protected function _afterDelete(Core_Model_Abstract $object){
		return $this;
	}

	/**
	 * 获取主表完整的表名(包含表前缀,如果有的话)
	 * @return string
	 */
	public function getMainTable(){
		return Ddm_Db::getTable($this->_mainTableName);
	}

	/**
	 * @return string
	 */
	public function getMainTableName(){
		return $this->_mainTableName;
	}

	/**
	 * 获取主表的主键字段名称
	 * @return string
	 */
	public function getPrimarykey(){
		return $this->_primarykey;
	}

	/**
	 * 获取字段列表
	 * @param string $table
	 * @return array
	 */
	public function getFields($table){
		if($table==''){
			throw new InvalidArgumentException('$table parameter is empty');
		}
		if(!isset($this->_fields[$table])){
			$this->_fields[$table] = array();
			$stmt = Ddm_Db::getReadConn()->query("SHOW COLUMNS FROM ".Ddm_Db::getTable($table));
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$this->_fields[$table][$row['Field']] = $row;
			}
		}
		return $this->_fields[$table];
	}

	/**
	 * @param Core_Model_Abstract $modelObject
	 * @return Core_Model_Resource_Abstract
	 */
	public function setModelObject(Core_Model_Abstract $modelObject){
		$this->_modelObject = $modelObject;
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	public function getModelObject(){
		return $this->_modelObject;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @param mixed $value
	 * @param string $field
	 * @return Core_Model_Resource_Abstract
	 */
	public function load(Core_Model_Abstract $object, $value, $field = NULL){
		if($field===NULL)$field = "`main_table`.`$this->_primarykey`";
		$select = $this->_getLoadSelect($this->_mainTableName,$field, $value);
		if($result = $select->first()){
			$object->addData($result);
			$object->setOrigData(NULL,NULL,true);
		}
		return $this;
	}

	/**
	 * @return Ddm_Db_Builder
	 */
	public function getSelect(){
		return $this->_select===NULL ? $this->_getSelect() : $this->_select;
	}

	/**
	 * @return Core_Model_Resource_Abstract
	 */
	public function save(Core_Model_Abstract $object){
		Ddm_Db::lockWriteConn();
		$this->_beforeSave($object);
		$dataValue = $this->_getSaveData($object);

		if($object->getId()){
			unset($dataValue[$this->_primarykey]);
			if($dataValue){
				Ddm_Db::table($this->getMainTableName())
					->where($this->_primarykey, '=', $object->getId())
					->update($dataValue);
			}
		}else{
			$object->setId(Ddm_Db::table($this->getMainTableName())->insertGetId($dataValue));
		}

		$this->_afterSave($object);
		$object->setOrigData(NULL,NULL,true);
		Ddm_Db::unLockReadWite();

		return $this;
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @param mixed $value
	 * @param string $field
	 * @return Core_Model_Resource_Abstract
	 */
	public function delete(Core_Model_Abstract $object, $value, $field = NULL){
		if($field===NULL)$field = $this->_primarykey;
		if(!$value)$value = $object->getId();
		if(!$value || !$field){
			throw new Exception('Can not find the records to be deleted.');
		}
		if($field==$this->_primarykey && !$object->getId())$object->setId($value);

		Ddm_Db::lockWriteConn();
		$this->_beforeDelete($object);
		Ddm_Db::table($this->getMainTableName())
			->where($field, '=', $value)
			->delete();
		$this->_afterDelete($object);
		Ddm_Db::unLockReadWite();

		return $this;
	}
}
