<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Admin_Login extends Core_Block_Abstract {
	protected $_loginNotice = '';

	public function init() {
		parent::init();
		$this->template = 'login.phtml';
		$this->_loginNotice = Ddm::getTranslate('admin')->translate('请填写您的用户名和密码登录');
	}

	public function getLoginUrl(){
		return Ddm::getLanguage()->getUrl('admin/admin/login');
	}

	/**
	 * @return string
	 */
	public function getLoginNotice(){
		return $this->_loginNotice;
	}

	/**
	 * @param string $string
	 * @return Admin_Block_Admin_Login
	 */
	public function setLoginNotice($string){
		$this->_loginNotice = $string;
		return $this;
	}
}
