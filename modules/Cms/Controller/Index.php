<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Controller_Index extends Core_Controller_Frontend_Abstract {
	public function indexAction(){
		Ddm_Request::singleton()->setParam('id',intval(Ddm::getConfig()->getConfigValue('web/pages/not_found')));
		$this->viewAction();
	}

	public function viewAction(){
		$block = $this->_createBlock('onepage_view','cms');/* @var $block Cms_Block_Onepage_View */
		$block->setTemplateObject($this->_getTemplate());
		if($onepageData = $block->getOnepageData()){
			Ddm_Controller::singleton()->setUrlAlias($onepageData['url_key']);
			if($onepageData['onepage_id']==Ddm::getConfig()->getConfigValue('web/pages/home'))Ddm_Controller::singleton()->setHomePage(true);
			$this->_getTemplate()
				->addBlock($block,'view_onepage')
				->display('cms');
		}else{
			Ddm_Controller::noAction();
		}
	}
}