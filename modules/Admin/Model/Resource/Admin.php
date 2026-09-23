<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Model_Resource_Admin extends Core_Model_Resource_Abstract {
	protected function _init(){
		$this->_setTable('admin_user','admin_id');
		return $this;
	}

	/**
	 * @param Admin_Model_Admin $object
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public function login(Admin_Model_Admin $object,$username,$password){
		if($username!='' && $password!=''){
			$select = Ddm_Db::table($this->getMainTableName())->where('admin_name','=',$username)->where('admin_pass','=',$object->getPasswordHash($password))->limit(2);
			$result = $select->get();
			if(count($result)==1){
				$object->addData($result[0]);
				$object->setOrigData($result[0],NULL,true);
				return true;
			}
		}
		return false;
	}

	/**
	 * 返回格式是array(group_id=>group_name[,...])的形式
	 * @param int $adminId
	 * @return array
	 */
	public function getGroupsFromAdminId($adminId){
		return Ddm_Db::table(array('a' => 'admin_group_user'))
			->join(array('b' => 'admin_group'),'b.group_id','=','a.group_id')
			->where('a.admin_id','=',$adminId)
			->orderBy('a.position', 'ASC')
			->pluck('b.group_name', 'a.group_id');
	}

	/**
	 * @param Core_Model_Abstract $object
	 * @return array
	 */
	protected function _getSaveData(Core_Model_Abstract $object){
		$dataValue = parent::_getSaveData($object);
		if(isset($dataValue['extra']) && is_array($dataValue['extra'])){
			$dataValue['extra'] = serialize($dataValue['extra']);
		}
		return $dataValue;
	}
}

