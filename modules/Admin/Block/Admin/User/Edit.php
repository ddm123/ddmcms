<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Admin_User_Edit extends Admin_Block_Widget_Form {
	/**
	 * @return Admin_Block_Admin_User_Edit
	 */
	public function init() {
		parent::init();
		$this->getAdmin() and $id = $this->getAdmin()->getId() or $id = false;
		$this->formAction = Ddm::getLanguage()->getUrl('*/*/save',array('id'=>$id));
		$this->method = 'post';
		$this->formId = 'edit-admin-form';
		$this->titleText = $id ? Ddm::getTranslate('admin')->___('修改管理员') : Ddm::getTranslate('admin')->___('增加管理员');
		$this->addButton('save_continue_edit',array(
			'label'=>Ddm::getTranslate('admin')->translate('保存并继续编辑'),
			'onclick'=>'saveAndContinueEdit(editAdminForm)',
			'class'=>'btn-primary')
		);
		return $this;
	}

	/**
	 * @return Admin_Model_Admin
	 */
	public function getAdmin(){
		return Ddm::registry('current_admin');
	}

	protected function _prepareElements() {
		$admin = $this->getAdmin();/* @var $admin Admin_Model_Admin */

		$this->addElement('username', array(
			'label'=>Ddm::getTranslate('admin')->translate('用户名'),'align_top'=>true,
			'type'=>'text','name'=>'username','size'=>32,'verify'=>1,
			'value'=>$admin ? $admin->admin_name : ''
		))
		->addElement('password',array(
			'label'=>Ddm::getTranslate('admin')->translate('密码'),'align_top'=>true,
			'type'=>'password','name'=>'password','size'=>32,'verify'=>$admin ? NULL : 1,
			'notice'=>$admin ? Ddm::getTranslate('admin')->translate('如果不需要更改密码，请留空') : NULL
		))
		->addElement('password2',array(
			'label'=>Ddm::getTranslate('admin')->translate('确认密码'),'align_top'=>true,
			'type'=>'password','name'=>'password2','size'=>32,'verify'=>$admin ? NULL : 1,
			'notice'=>Ddm::getTranslate('admin')->translate('重复再输入一次密码')
		))
		->addElement('description', array(
			'label'=>Ddm::getTranslate('admin')->translate('描述'),
			'type'=>'text','name'=>'description','style'=>'width:99%;','maxlength'=>250,'verify'=>NULL,
			'value'=>$admin ? $admin->description : ''
		))
		->addElement('loginnum',array(
			'label'=>Ddm::getTranslate('admin')->translate('登录次数'),
			'type'=>'text','name'=>'loginnum','size'=>10,
			'value'=>$admin ? $admin->admin_loginnum : '0'
		))
		->addElement('groups',array(
			'label'=>Ddm::getTranslate('admin')->translate('隶属于'),
			'align_top'=>true,
			'type'=>'checkbox',
			'before_html'=>'<div id="group-list-box">',
			'after_html'=>'</div>',
			'list'=>$this->_getGroupsElement(),
			'verify'=>NULL
		))
		->addElement('options',array(
			'label'=>Ddm::getTranslate('core')->translate('选项'),
			'align_top'=>false,
			'type'=>'checkbox',
			'name'=>'is_active','value'=>'1',
			'id'=>'is_active',
			'checked'=>$admin&&$admin->is_active ? 'checked' : NULL,
			'before_html'=>'<p>','after_html'=>' <label for="is_active">'.Ddm::getTranslate('core')->translate('启用').'</label></p>'
		))
		->addElement('options',array(
			'type'=>'checkbox',
			'name'=>'edit_name','value'=>'1',
			'id'=>'edit_name',
			'checked'=>$admin&&$admin->edit_name ? 'checked' : NULL,
			'before_html'=>'<p>','after_html'=>' <label for="edit_name">'.Ddm::getTranslate('admin')->translate('允许修改用户名').'</label></p>',
		))
		->addElement('options',array(
			'type'=>'checkbox',
			'name'=>'edit_pass','value'=>'1',
			'id'=>'edit_pass',
			'checked'=>$admin&&$admin->edit_pass ? 'checked' : NULL,
			'before_html'=>'<p>','after_html'=>' <label for="edit_pass">'.Ddm::getTranslate('admin')->translate('允许修改密码').'</label></p>',
		));

		$this->addJs('DOMLoaded(function(){var adminGroup = new AdminGroup("group-list-box");adminGroup.setGroup();});');
		$this->addValidateFormSuccessJs('if(form.password.value!=form.password2.value){form.password2.focus();obj.validateResult = false;$notice(form.password2,{"text":"'.Ddm::getTranslate('admin')->translate('两次输入的密码不一致').'","direction":"left","icon":"error","close":true});if(event)doane(event);}else{obj.validateResult = true;$notice(form.password2);}');

		return parent::_prepareElements();
	}

	/**
	 * @return array
	 */
	protected function _getGroupsElement(){
		$allGroups = Admin_Model_Group::singleton()->getResource()->getAllGroups();
		$groups = $this->getAdmin() ? $this->getAdmin()->getGroups() : array();
		$position = array();
		$i = 0;
		foreach($groups as $groupId=>$groupName)$position[$groupId] = ++$i;
		$groupList = array();
		foreach($allGroups as $groupId=>$groupName){
			$groupList[] = array(
				'value'=>isset($position[$groupId]) ? $position[$groupId] : $groupId,
				'label'=>$groupName,
				'name'=>'groups['.$groupId.']',
				'id'=>'group-'.$groupId,
				'title'=>Ddm_String::singleton()->escapeHtml($groupName),
				'checked'=>isset($groups[$groupId]) ? 'checked' : NULL
			);
		}
		return $groupList;
	}
}