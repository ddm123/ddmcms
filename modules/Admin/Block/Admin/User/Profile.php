<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Admin_User_Profile extends Admin_Block_Widget_Form {
	private $_admin = NULL;

	/**
	 * @return Admin_Block_Admin_User_Profile
	 */
	public function init() {
		parent::init();
		$this->formAction = Ddm::getLanguage()->getUrl('*/*/save-profile');
		$this->method = 'post';
		$this->formId = 'admin-profile-form';
		$this->titleText = Ddm::getTranslate('admin')->___('我的资料');
		$this->template = 'user/profile_form.phtml';
		$this->removeButton('back');
		return $this;
	}

	/**
	 * @return Admin_Model_Admin
	 */
	public function getAdmin(){
		return $this->_admin===NULL ? ($this->_admin = Admin_Model_Admin::loggedInAdmin()) : $this->_admin;
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	public function getVarnameCallback($matches){
		return strtoupper($matches[1]);
	}

	/**
	 * @return string
	 */
	public function timezonesToJson(){
		$timezones = array(array('',Ddm::getTranslate('admin')->translate('使用配置的设定')));
		foreach(Core_Model_Date_Timezone::singleton()->getAllOptions(false) as $option){
			$timezones[] = array($option['value'],$option['label']);
		}
		return json_encode($timezones);
	}

	public function getUseDateTimezoneSelected(){
		$useTimezone = $this->getAdmin()->getExtra('use_timezone');
		if($useTimezone && ($timezone = Core_Model_Date_Timezone::singleton()->getTimezones($useTimezone))){
			$useDateTimezone = '["'.$useTimezone.'","'.addslashes($timezone).'"]';
		}else{
			$useDateTimezone = '["","'.Ddm::getTranslate('admin')->translate('使用配置的设定').'"]';
		}
		return $useDateTimezone;
	}

	protected function _prepareElements(){
		$js = '';
		if($this->getAdmin()->edit_pass){
			$js .= 'if(form.password.value!=form.password2.value){'
				. 'form.password2.focus();obj.validateResult = false;'
				. '$notice(form.password2,{"text":"'.Ddm::getTranslate('admin')->translate('两次输入的密码不一致').'","direction":"left","icon":"error","close":true});if(event)doane(event);'
				.'}else{obj.validateResult = true;$notice(form.password2);}'
				.'if(!empty(form.password.value) && empty(form.oldpassword.value)){'
				. 'form.oldpassword.focus();obj.validateResult = false;'
				. '$notice(form.oldpassword,{"text":"'.Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('admin')->translate('旧密码')).'","direction":"left","icon":"error","close":true});if(event)doane(event);'
				.'}else{obj.validateResult = true;$notice(form.oldpassword);}';
		}
		if($js)$this->addValidateFormSuccessJs($js);
		return parent::_prepareElements();
	}
}