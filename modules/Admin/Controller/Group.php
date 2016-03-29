<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Controller_Group extends Admin_Controller_Abstract {
	public function indexAction(){
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('管理员组'))
			->addWindowScript()
			->addBlock($this->_createBlock('admin_group_list'),'admin_grouplist')
			->setActiveMemu('admin')
			->display();
	}

	public function addAction(){
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('增加管理员组'))
			->addBlock($this->_createBlock('admin_group_edit'),'add_admin_group')
			->setActiveMemu('admin')
			->display();
	}

	public function editAction(){
		$groupId = Ddm_Request::get('id','int');
		$group = new Admin_Model_Group();
		if(!$groupId || !$group->load($groupId)->getId()){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('admin')->translate('管理员组')));
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
			return;
		}
		Ddm::register('current_group',$group);

		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('修改管理员组'))
			->addBlock($this->_createBlock('admin_group_edit'),'edit_admin_group')
			->setActiveMemu('admin')
			->display();
	}

	public function saveFieldValueAction(){
		$fields = array('group_name','description');
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		$value = trim(Ddm_Request::post('v',false,''));
		$success = 'true';
		$message = '';
		$group = new Admin_Model_Group();
		if($this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT) && $id && $group->load($id)->getId()==$id){
			Ddm_Db::beginTransaction();
			try{
				if($field=='group_name'){
					if($value==''){
						$success = 'false';
						$message = Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('admin')->translate('组名称'));
					}else if($group->group_name!=$value){
						$exists = Ddm_Db::getReadConn()->count($group->getResource()->getMainTable(),array('group_name'=>$value,array('group_id'=>array('<>'=>$id))));
						if($exists){
							$success = 'false';
							$message = Ddm::getTranslate('admin')->translate('您填写的组名称已经存在了');
						}else{
							$group->setData($field,$value)->save();
							$success = 'true';
							$message = '';
						}
					}
				}else if(in_array($field,$fields)){
					$group->setData($field,$value)->save();
				}
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$success = 'false';
				$message = $ex->getMessage();
			}
		}else{
			$success = 'false';
			$message = Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('修改管理员组'));
		}
		echo '{"success":'.$success.',"value":"'.addslashes($value).'","message":"'.addslashes($message).'"}';
	}

	public function saveAction(){
		$groupId = Ddm_Request::get('id','int');
		$groupName = trim(Ddm_Request::post('group_name',false,''));
		$allowType = (int)Ddm_Request::post('allow_type');
		$resources = Ddm_Request::post('resources',false,'');
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getLanguage()->getUrl('*/*');

		if($groupName==''){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('admin')->translate('组名称')));
		}else if($allowType==0 && !$resources){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('管理员组不能没有任何的权限'));
		}else if($groupId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('修改管理员组')));
		}else if(!$groupId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('增加管理员组')));
		}else{
			$group = new Admin_Model_Group();
			if($groupId){
				$group->load($groupId);
				if(!$group->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('admin')->translate('管理员组')));
					Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
					return;
				}
			}
			$exists = Ddm_Db::getReadConn()->count($group->getResource()->getMainTable(),array('group_name'=>$groupName,array('group_id'=>$groupId ? array('<>'=>$groupId) : array('>'=>0))));
			if($exists){
				$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('您填写的组名称已经存在了'));
				Ddm_Request::redirect($groupId ? Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>$groupId)) : Ddm::getLanguage()->getUrl('*/*/add'));
				return;
			}

			Ddm_Db::beginTransaction();
			try{
				$group
					->addData(array('group_name'=>$groupName,'description'=>Ddm_Request::post('description',false,'')))
					->save()
					->saveResources($allowType=='1' ? 'all' : $resources);
				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('admin')->translate('管理员组')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>$group->getId())) : Ddm::getLanguage()->getUrl('*/*');
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}
		Ddm_Request::redirect($gotoUrl);
	}

	public function deleteAction(){
		if($ids = Ddm_Request::post('ids','int',NULL)){
			Ddm_Db::beginTransaction();
			try{
				$loggedInAdminGroups = Admin_Model_Admin::loggedInAdmin()->getGroups();
				$i = 0;
				foreach($ids as $id){
					if(isset($loggedInAdminGroups[$id])){
						$this->getNotice()->addNotice(Ddm::getTranslate('admin')->___('不能删除自己所隶属的管理员组：%s',$loggedInAdminGroups[$id]));
					}else{
						$group = new Admin_Model_Group();
						$group->setId($id)->delete();
						$i++;
					}
				}
				if($i)$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除选择的记录'));
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}else{
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
	}

	public function isAllowed($actionName){
		return $this->_isAllowed($actionName,'admin/admin_group');
	}
}
