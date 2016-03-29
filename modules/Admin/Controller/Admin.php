<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Controller_Admin extends Admin_Controller_Abstract {
	public function indexAction(){
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('管理员'))
			->addWindowScript()
			->addBlock($this->_createBlock('admin_user_list'),'admin_userlist')
			->setActiveMemu('admin')
			->display();
	}

	public function addAction(){
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('增加管理员'))
			->addBlock($this->_createBlock('admin_user_edit'),'add_admin_user')
			->addJs('ToolMan.js','core')
			->setActiveMemu('admin')
			->display();
	}

	public function editAction(){
		$adminId = Ddm_Request::get('id','int');
		$admin = new Admin_Model_Admin();
		if(!$adminId || !$admin->load($adminId)->getId()){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('admin')->translate('管理员')));
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
			return;
		}
		Ddm::register('current_admin',$admin);

		if($adminId==Admin_Model_Admin::loggedInAdmin()->getId()){
			$this->_getTemplate()->getBlock('header')->getBlock('notice')->getNotice()
				->addNotice(Ddm::getTranslate('admin')->___('注: 如果你需要更改用户名或密码, 建议到 %s 修改, 在这里更改密码你将会被强制退出登录','<a href="'.Ddm::getUrl('admin/admin/profile').'">'.Ddm::getTranslate('admin')->___('我的资料').'</a>'));
		}
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('修改管理员'))
			->addBlock($this->_createBlock('admin_user_edit'),'edit_admin_user')
			->addJs('ToolMan.js','core')
			->setActiveMemu('admin')
			->display();
	}

	public function profileAction(){
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('我的资料'))
			->addBlock($this->_createBlock('admin_user_profile'),'admin_user_profile')
			->setActiveMemu('admin')
			->display();
	}

	public function getFieldValueAction(){
		$fields = array('admin_name','admin_loginnum','is_active','edit_name','edit_pass');
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		if($id && in_array($field,$fields)){
			echo Ddm_Db::getReadConn()->getSelect()
				->from(Ddm_Db::getTable('admin_user'),$field)
				->where('admin_id',$id)->fetchOne(true);
		}else{
			echo trim(Ddm_Request::post('v',false,''));
		}
	}

	public function saveFieldValueAction(){
		$fields = array('admin_name','admin_loginnum','is_active','edit_name','edit_pass','description');
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		$value = trim(Ddm_Request::post('v',false,''));
		$success = 'true';
		$message = '';
		$admin = new Admin_Model_Admin();
		if($this->isAllowed('edit') && $id && $admin->load($id)->getId()==$id){
			Ddm_Db::beginTransaction();
			try{
				if($field=='admin_name'){
					if($value==''){
						$success = 'false';
						$message = Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('admin')->translate('用户名'));
					}else{
						$exists = Ddm_Db::getReadConn()->count($admin->getResource()->getMainTable(),array('admin_name'=>$value,array('admin_id'=>array('<>'=>$id))));
						if($exists){
							$success = 'false';
							$message = Ddm::getTranslate('admin')->translate('您填写的用户名已经存在了');
						}else{
							$admin->setData($field,$value)->save();
							$success = 'true';
						}
					}
				}else if(in_array($field,$fields)){
					$field=='description' or $value = (int)$value;
					$admin->setData($field,$value)->save();
					$success = 'true';
				}
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$success = 'false';
				$message = $ex->getMessage();
			}
		}else{
			$success = 'false';
			$message = Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('修改管理员'));
		}
		echo '{"success":'.$success.',"value":"'.addslashes($value).'","message":"'.addslashes($message).'"}';
	}

	public function activeAction(){
		if($ids = Ddm_Request::post('ids','int',NULL)){
			if($this->isAllowed('edit')){
				Ddm_Db::beginTransaction();
				try{
					$value = (int)Ddm_Request::get('value');
					Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('admin_user'),array('is_active'=>$value),Ddm_Db_Interface::SAVE_UPDATE,array('admin_id'=>$ids));
					$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('admin')->translate('管理员')));
					Ddm_Db::commit();
				}catch(Exception $ex){
					Ddm_Db::rollBack();
					$this->getNotice()->addError($ex->getMessage());
				}
			}else{
				$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('修改管理员')));
			}
		}else{
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
	}

	public function saveAction(){
		$adminId = (int)Ddm_Request::get('id',false,0);
		$adminName = trim(Ddm_Request::post('username',false,''));
		$passWord = trim(Ddm_Request::post('password',false,''));
		$passWord2 = trim(Ddm_Request::post('password2',false,''));
		$groups = Ddm_Request::post('groups',true);
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getLanguage()->getUrl('*/*');
		if($adminName==''){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('admin')->translate('用户名')));
		}else if(!$adminId && $passWord==''){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('admin')->translate('密码')));
		}else if($passWord!=$passWord2){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('两次输入的密码不一致'));
		}else if(empty($groups)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('至少要选择一个管理员组'));
		}else if($adminId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('修改管理员')));
		}else if(!$adminId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('增加管理员')));
		}else{
			$admin = new Admin_Model_Admin();
			if($adminId){
				$admin->load($adminId);
				if(!$admin->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('admin')->translate('管理员')));
					Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
					return;
				}
			}
			$exists = Ddm_Db::getReadConn()->count($admin->getResource()->getMainTable(),array('admin_name'=>$adminName,array('admin_id'=>$adminId ? array('<>'=>$adminId) : array('>'=>0))));
			if($exists){
				$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('您填写的用户名已经存在了'));
				Ddm_Request::redirect($adminId ? Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>$adminId)) : Ddm::getLanguage()->getUrl('*/*/add'));
				return;
			}

			Ddm_Db::beginTransaction();
			try{
				$admin->addData(array(
					'admin_name'=>$adminName,
					'admin_pass'=>$passWord,
					'description'=>Ddm_Request::post('description',false,''),
					'groups_position'=>$groups,
					'admin_loginnum'=>(int)Ddm_Request::post('loginnum'),
					'is_active'=>(int)Ddm_Request::post('is_active'),
					'edit_name'=>(int)Ddm_Request::post('edit_name'),
					'edit_pass'=>(int)Ddm_Request::post('edit_pass')
				))->save();
				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('admin')->translate('管理员')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>$admin->getId())) : Ddm::getLanguage()->getUrl('*/*');
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
				$adminId = Admin_Model_Admin::loggedInAdmin()->getId();
				$i = 0;
				foreach($ids as $id){
					if($id==$adminId){
						$this->getNotice()->addNotice(Ddm::getTranslate('admin')->___('不能删除自己：%s',Admin_Model_Admin::loggedInAdmin()->admin_name));
					}else{
						$admin = new Admin_Model_Admin();
						$admin->setId($id)->delete();
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

	public function saveProfileAction(){
		$admin = Admin_Model_Admin::loggedInAdmin();
		$saveAdmin = new Admin_Model_Admin();
		$saveAdmin->load($admin->getId());
		if($saveAdmin->getId()){
			$error = false;
			if($admin->edit_name){
				if($adminName = trim(Ddm_Request::post('username',false,''))){
					$exists = Ddm_Db::getReadConn()->count($saveAdmin->getResource()->getMainTable(),array('admin_name'=>$adminName,array('admin_id'=>array('<>'=>$saveAdmin->getId()))));
					if($exists){
						$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('您填写的用户名已经存在了'));
						$error = true;
					}else $saveAdmin->admin_name = $adminName;
				}
			}
			$oldPassword = trim(Ddm_Request::post('oldpassword',false,''));
			if(!$error && $admin->edit_pass && $oldPassword!=''){
				$password = trim(Ddm_Request::post('password',false,''));
				$password2 = trim(Ddm_Request::post('password2',false,''));
				if($password!=$password2){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('两次输入的密码不一致'));
					$error = true;
				}else if($oldPassword && $admin->getPasswordHash($oldPassword)==$admin->admin_pass){
					$saveAdmin->admin_pass = $password;
				}else{
					$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('旧密码错误'));
					$error = true;
				}
			}
			if(!$error){
				$saveAdmin->setExtra('use_timezone',Ddm_Request::post('use_timezone',false,''));
				$saveAdmin->save();
				if((isset($adminName) && $adminName!='' && $adminName!=$admin->admin_name)
					|| (isset($password) && $password!='')){
					$saveAdmin->loginById($admin->getId());
				}
				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('admin')->translate('管理员')));
			}
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('admin/admin/profile'));
	}

	public function loginAction(){
		if(Admin_Model_Admin::loggedInAdmin()->isLoggedIn()){
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('admin'));
		}
		if(isset($_POST['username']) && isset($_POST['password'])){
			if($result = Admin_Model_Admin::loggedInAdmin()->login($_POST['username'],$_POST['password'])){
				if($result===1){
					//删除缓存
					Ddm_Cache::singleton()->removeByTags(array('admin_user'));
					echo 'success';
				}else{
					echo Ddm::getTranslate('admin')->translate('该帐户已被禁用');
				}
			}else{
				echo Ddm::getTranslate('admin')->translate('用户名或密码错误');
			}
		}else{
			parent::loginAction();
		}
	}

	public function logoutAction(){
		Admin_Model_Admin::loggedInAdmin()->logOut();
		if($httpRefere = Ddm_Request::server()->HTTP_REFERER){
			if(stripos($httpRefere,Ddm::getLanguage()->getUrl('*/*'))!==0)$httpRefere = NULL;
		}
		Ddm_Request::redirect($httpRefere ? $httpRefere : Ddm::getLanguage()->getUrl('admin'));
	}

	public function isAllowed($actionName){
		if($actionName=='profileAction' || $actionName=='saveProfileAction')return true;
		return $this->_isAllowed($actionName,'admin/admin_user');
	}
}
