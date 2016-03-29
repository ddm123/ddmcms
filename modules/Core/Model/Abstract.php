<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Model_Abstract extends Ddm_Object {
	protected $_resource = NULL;
	protected $_isLoaded = NULL;
	protected $_isDeleted = false;

	/**
	 * @return Core_Model_Abstract
	 */
	protected function _beforeLoad($id, $field = NULL){
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	protected function _afterLoad(){
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	protected function _beforeSave(){
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	protected function _afterSave(){
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	protected function _beforeDelete(){
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	protected function _afterDelete(){
		return $this;
	}

	/**
	 * @return Core_Model_Resource_Abstract
	 */
	public function getResource(){
		if($this->_resource===NULL){
			$resourceClass = str_replace('_Model_', '_Model_Resource_', get_class($this));
			$this->_resource = new $resourceClass();
			$this->_resource->setModelObject($this);
		}
		return $this->_resource;
	}

	/**
	 * @return Ddm_Db_Select
	 */
	public function getSelect(){
		return $this->getResource()->getSelect();
	}

	/**
	 * @return Ddm_Db_Select
	 */
	public function getCountSelect(){
		$select = clone $this->getSelect();
		$select->resetColumns()
			->reset(Ddm_Db_Select::DISTINCT)
			->reset(Ddm_Db_Select::GROUP)
			->reset(Ddm_Db_Select::ORDER)
			->reset(Ddm_Db_Select::LIMIT)
			->columns(array('total'=>'COUNT(*)'));

		return $select;
	}

	/**
	 * @param int $rowCount 指定返回的行数的最大值
	 * @param int $offset 第几行开始
	 * @return Core_Model_Abstract
	 */
	public function setLimit($rowCount,$offset = 0){
		$this->getSelect()->limit((int)$offset, (int)$rowCount);
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getId(){
		return $this->getResource()->getPrimarykey() ? $this->getData($this->getResource()->getPrimarykey()) : NULL;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	public function setId($id){
		if($this->getResource()->getPrimarykey())$this->setData($this->getResource()->getPrimarykey(),$id);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(){
		return $this->_isDeleted;
	}

	/**
	 * @param int $id
	 * @param string|null $field
	 * @return Core_Model_Abstract
	 */
	public function load($id, $field = NULL){
		if($this->_isLoaded!="$id-$field"){
			Ddm::dispatchEvent('model_load_before',array('object'=>$this,'field'=>$field,'value'=>$id));
			$this->_beforeLoad($id, $field);
			$this->getResource()->load($this, $id, $field);
			Ddm::dispatchEvent('model_load_after', array('object'=>$this));
			$this->_afterLoad();
			$this->_isLoaded = "$id-$field";
		}
		return $this;
	}

	/**
	 * @return Core_Model_Abstract
	 */
	public function save(){
		if($this->isDeleted())return $this;

		Ddm_Db::$lockReadWiteType = Ddm_Db::WRITE;
		Ddm::dispatchEvent('model_save_before', array('object'=>$this));
		$this->_beforeSave();
		$this->getResource()->save($this);
		Ddm::dispatchEvent('model_save_after', array('object'=>$this));
		$this->_afterSave();
		Ddm_Db::unLockReadWite();
		return $this;
	}

	/**
	 * @param int|null $id
	 * @param string|null $field
	 * @return Core_Model_Abstract
	 */
	public function delete($id = NULL, $field = NULL){
		if($this->isDeleted())return $this;

		Ddm_Db::$lockReadWiteType = Ddm_Db::WRITE;
		Ddm::dispatchEvent('model_delete_before', array('object'=>$this));
		$this->_beforeDelete();
		$this->getResource()->delete($this, $id, $field);
		$this->_isDeleted = true;
		Ddm::dispatchEvent('model_delete_after', array('object'=>$this));
		$this->_afterDelete();
		Ddm_Db::unLockReadWite();
		return $this;
	}
}
