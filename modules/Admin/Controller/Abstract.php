<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

defined('IN_ADMIN') or define('IN_ADMIN', true);

abstract class Admin_Controller_Abstract extends Core_Controller_Abstract {
	protected $_allow_types = array(
		Admin_Model_Group::ALLOW_TYPE_READ=>true,
		Admin_Model_Group::ALLOW_TYPE_ADD=>true,
		Admin_Model_Group::ALLOW_TYPE_EDIT=>true,
		Admin_Model_Group::ALLOW_TYPE_DELETE=>true
	);

	public function __construct(){
		parent::__construct();

		//如果未登录
		if(!Admin_Model_Admin::loggedInAdmin()->isLoggedIn())Ddm_Controller::singleton()->setActionName('login');
		else if($timezone = Admin_Model_Admin::loggedInAdmin()->getExtra('use_timezone')){
			Ddm::getHelper('core')->getDateTime()->setTimezone($timezone);
		}

		Ddm::addEventListener('controller_call_noaction_before',array($this,'noAction'));
	}

	/**
	 * @return Ddm_Session
	 */
	protected function _getSession(){
		return Ddm::getHelper('admin')->getSession();
	}

	/**
	 * @return Admin_Block_Template
	 */
	protected function _getTemplate(){
		return Admin_Block_Template::singleton();
	}

	/**
	 * 获取当前登录的管理员
	 * @return Admin_Model_Admin
	 */
	protected function _getAdminUser(){
		return Admin_Model_Admin::loggedInAdmin();
	}

	/**
	 * @param string $actionName
	 * @param string $path
	 * @return bool
	 */
	protected function _isAllowed($actionName,$path){
		if(Ddm_Controller::singleton()->getActionName()=='login' || Ddm_Controller::singleton()->getActionName()=='logout')return true;
		$type = preg_replace('/Action$/','',$actionName);
		if($type=='save'){
			return Admin_Model_Admin::loggedInAdmin()->isAllow($path,Admin_Model_Group::ALLOW_TYPE_ADD) || Admin_Model_Admin::loggedInAdmin()->isAllow($path,Admin_Model_Group::ALLOW_TYPE_EDIT);
		}
		isset($this->_allow_types[$type]) or $type = Admin_Model_Group::ALLOW_TYPE_READ;
		return Admin_Model_Admin::loggedInAdmin()->isAllow($path,$type);
	}

	/**
	 * @return Admin_Model_Notice
	 */
	public function getNotice(){
		return $this->_notice===NULL ? ($this->_notice = new Admin_Model_Notice(false)) : $this->_notice;
	}

	/**
	 * @param string $type
	 * @return Admin_Controller_Abstract
	 */
	public function addAllowType($type){
		if($type===NULL)
			unset($this->_allow_types[$type]);
		else
			$this->_allow_types[$type] = true;
		return $this;
	}

	/**
	 * @param Ddm_Controller $controller
	 * @return Admin_Controller_Abstract
	 */
	public function noPermission($controller) {
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->___('无权限，访问被禁止'))
			->addBlock($this->_createBlock('nopermission','admin'),'admin_nopermission')
			->display();
		return $this;
	}

	/**
	 * @param array $params
	 * @return Admin_Controller_Abstract
	 */
	public function noAction($params){
		$params['controller']->set404PageHtml('');
		$this->_getTemplate()
			->setTitle(Ddm::getTranslate('admin')->translate('你访问的页面不存在'))
			->addBlock($this->_createBlock('notfound','admin'),'admin_notfound')
			->display();
		return $this;
	}

	public function loginAction(){
		$block = $this->_createBlock('admin_login','admin');/* @var $block Admin_Block_Admin_Login */

		if(Admin_Model_Admin::loggedInAdmin()->verifyCookie()==2){
			$block->setLoginNotice('<span style="color:#FF0000;">'.Ddm::getTranslate('admin')->translate('您的帐号已被另一个人在其它地方登录，您被强制退出').'</span>');
		}

		$this->_getTemplate()
			->clear()
			->addBlock($this->_createBlock('head','core')->isFetch(false),'head')
			->addBlock($block,'login')
			->setBlocksLock(true)
			->setTitle(Ddm::getTranslate('admin')->translate('请填写您的用户名和密码登录'))
			->addCss('common.css','core')->addCss('admin.css','admin')->addJs('common.js','core')
			->display();
	}
}
