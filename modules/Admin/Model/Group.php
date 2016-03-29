<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method Admin_Model_Resource_Group getResource()
 * @property string $group_name
 */
class Admin_Model_Group extends Core_Model_Abstract {
	const ALLOW_TYPE_READ = 'read';
	const ALLOW_TYPE_ADD = 'add';
	const ALLOW_TYPE_EDIT = 'edit';
	const ALLOW_TYPE_DELETE = 'delete';

	protected static $_instance = NULL;
	protected $_adminAllows = array();
	protected $_allows = NULL;

	/**
	 * 使用单例模式
	 * @return Admin_Model_Group
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Admin_Model_Group()) : self::$_instance;
    }

	/**
	 * @param int $adminId
	 * @return array
	 */
	public function getAllowsFromAdminId($adminId){
		if($adminId = (int)$adminId){
			if(!isset($this->_adminAllows[$adminId])){
				$this->_adminAllows[$adminId] = array();
				$allows = $this->getResource()->getAllowsDataFromAdminId($adminId);
				foreach($allows as $row){
					isset($this->_adminAllows[$adminId][$row['group_id']]) or $this->_adminAllows[$adminId][$row['group_id']] = array();
					if($row['path']=='all'){
						$this->_adminAllows[$adminId][$row['group_id']] = $row['path'];
					}else{
						isset($this->_adminAllows[$adminId][$row['group_id']][$row['path']]) or $this->_adminAllows[$adminId][$row['group_id']][$row['path']] = array();
						$this->_adminAllows[$adminId][$row['group_id']][$row['path']][$row['allow_type']] = (bool)$row['allow_value'];
					}
				}
			}
			return $this->_adminAllows[$adminId];
		}
		return array();
	}

	/**
	 * @return array
	 */
	public function getAllows(){
		if($this->_allows===NULL){
			$this->_allows = $this->getId() ? $this->getResource()->getAllowsFromGroupId($this->getId()) : array();
		}
		return $this->_allows;
	}

	/**
	 * @param string $groupName
	 * @return Admin_Model_Group
	 */
	public function loadFromGroupName($groupName){
		$this->getResource()->loadFromGroupName($this,$groupName);
		return $this;
	}

	/**
	 * @param array $resources
	 * @return Admin_Model_Group
	 */
	public function saveResources($resources){
		$this->getResource()->saveResources($this,$resources);
		return $this;
	}

	protected function _afterDelete() {
		Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_allow'),array('group_id'=>$this->getId()));
		Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_allow_value'),array('group_id'=>$this->getId()));
		return parent::_afterDelete();
	}
}

