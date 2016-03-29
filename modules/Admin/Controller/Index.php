<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Controller_Index extends Admin_Controller_Abstract {
	public function indexAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('home_dashboard','admin','home/dashboard.phtml'),'home_dashboard')
			->setTitle(Ddm::getTranslate('admin')->translate('首页'))
			->setActiveMemu('dashboard')
			->display();
	}

	public function isAllowed($actionName) {
		if(Ddm_Controller::singleton()->getActionName()=='login')return true;
		return $this->_isAllowed('read','core/dashboard');
	}
}
