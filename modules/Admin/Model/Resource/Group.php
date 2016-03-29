<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Model_Resource_Group extends Core_Model_Resource_Abstract {
	protected function _init(){
		$this->_setTable('admin_group','group_id');
		return $this;
	}

	/**
	 * 返回格式是array(group_id=>group_name[,...])的形式
	 * @param int $groupId
	 * @return array
	 */
	public function getAllGroups($groupId = NULL){
		$sql  = "SELECT a.group_id,a.group_name FROM ".$this->getMainTable()." AS a ";
		if($groupId!==NULL)$sql .= "WHERE a.group_id='".intval($groupId)."' ";
		$sql .= "ORDER BY a.`group_id` ASC";
		return Ddm_Db::getReadConn()->fetchPairs($sql);
	}

	/**
	 * @param int $adminId
	 * @return array
	 */
	public function getAllowsDataFromAdminId($adminId){
		$select = Ddm_Db::getReadConn()->getSelect();

		$select->from(array('gu'=>Ddm_Db::getTable('admin_group_user')),'group_id')
			->innerJoin(array('allow'=>Ddm_Db::getTable('admin_allow')),"`allow`.group_id=gu.group_id",'path')
			->leftJoin(array('v'=>Ddm_Db::getTable('admin_allow_value')),"v.allow_id=`allow`.allow_id",array('allow_type','allow_value'));
		$select->where('gu.admin_id',$adminId)->order('gu.`position` ASC');

		return $select->fetchAll();
	}

	/**
	 * @param int $groupId
	 * @return array|string
	 */
	public function getAllowsFromGroupId($groupId){
		$allows = array();
		$select = Ddm_Db::getReadConn()->getSelect();

		$select->from(array('allow'=>Ddm_Db::getTable('admin_allow')),'path')
			->leftJoin(array('v'=>Ddm_Db::getTable('admin_allow_value')),"v.allow_id=`allow`.allow_id",array('allow_type','allow_value'));
		$select->where('allow.group_id',$groupId);
		$result = $select->fetchAll();

		if($result && $result[0]['path']=='all')return $result[0]['path'];
		foreach($result as $row){
			isset($allows[$row['path']]) or $allows[$row['path']] = array();
			$allows[$row['path']][$row['allow_type']] = (bool)$row['allow_value'];
		}

		return $allows;
	}

	/**
	 * @param Admin_Model_Group $object
	 * @param string $groupName
	 * @return Admin_Model_Resource_Group
	 */
	public function loadFromGroupName(Admin_Model_Group $object,$groupName){
		$this->load($object, $groupName, 'group_name');
		return $this;
	}

	/**
	 * @param Admin_Model_Group $object
	 * @param array $resources
	 * @return Admin_Model_Resource_Group
	 */
	public function saveResources(Admin_Model_Group $object,$resources){
		if($groupId = $object->getId()){
			if($resources=='all'){
				Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_allow_value'),array('group_id'=>$groupId));
				Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_allow'),array('group_id'=>$groupId,'path'=>array('<>'=>'all')));
				Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('admin_allow'),array('group_id'=>$groupId,'path'=>'all'),Ddm_Db_Interface::SAVE_DUPLICATE,array('path'=>'all'));
			}else if(is_array($resources)){
				Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_allow'),array('group_id'=>$groupId,'path'=>'all'));
				foreach($resources as $path=>$allowValue){
					if(is_array($allowValue) && preg_match('/^\w[\w\/]*\w$/',$path)){
						$allowId = Ddm_Db::getWriteConn()->fetchOne("SELECT allow_id FROM ".Ddm_Db::getTable('admin_allow')." WHERE group_id='$groupId' AND `path`='$path'",true);
						if(!$allowId){
							Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('admin_allow'),array('group_id'=>$groupId,'path'=>strtolower($path)));
							$allowId = Ddm_Db::lastInsertId();
						}
						foreach($allowValue as $allowType=>$value){
							if(preg_match('/^\w+$/',$allowType)){
								if($value===''){
									Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_allow_value'),array('allow_id'=>$allowId,'allow_type'=>$allowType));
								}else{
									$value = (int)$value ? 1 : 0;
									Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('admin_allow_value'),array(
										'allow_id'=>$allowId,
										'group_id'=>$groupId,
										'allow_type'=>$allowType,
										'allow_value'=>$value
									),Ddm_Db_Interface::SAVE_DUPLICATE,array('allow_value'=>$value));
								}
							}
						}
					}
				}
			}
		}
		return $this;
	}

	/**
	 * @param int $groupId
	 * @return int
	 */
	public function getUserCount($groupId){
		if($groupId = (int)$groupId){
			return Ddm_Db::getReadConn()->count(Ddm_Db::getTable('admin_group_user'),array('group_id'=>$groupId));
		}
		return 0;
	}

	protected function _beforeDelete(Core_Model_Abstract $object){
		parent::_beforeDelete($object);
		if($this->getUserCount($object->getId())){
			throw new Exception(Ddm::getTranslate('admin')->translate('不允许删除，因为该管理员组下有管理员'));
		}
		return $this;
	}
}
