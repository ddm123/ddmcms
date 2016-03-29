<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

defined('IN_ADMIN') or define('IN_ADMIN', false);

abstract class Core_Controller_Frontend_Abstract extends Core_Controller_Abstract {
	public function __construct() {
		parent::__construct();
		Ddm::addEventListener('controller_call_noaction_before',array($this,'noAction'));
	}

	/**
	 * @return Core_Block_Frontend_Template
	 */
	protected function _getTemplate(){
		return Core_Block_Frontend_Template::singleton();
	}

	/**
	 * @return int
	 */
	protected function _getNotFoundPageId(){
		return (int)Ddm::getConfig()->getConfigValue('web/pages/not_found');
	}

	/**
	 * @param array $params
	 * @return Core_Controller_Frontend_Abstract
	 */
	public function noAction($params = NULL){
		if($onepageId = $this->_getNotFoundPageId()){
			Ddm::addEventListener('get_onepage_data_before',array($this,'_getOnepageDataBeforeCallbakc'));

			$block = $this->_createBlock('onepage_view','cms');/* @var $block Cms_Block_Onepage_View */
			if($onepageData = $block->getOnepageData()){
				Ddm_Controller::singleton()->setUrlAlias($onepageData['url_key']);
				if($onepageData['onepage_id']==Ddm::getConfig()->getConfigValue('web/pages/home'))
					Ddm_Controller::singleton()->setHomePage(true);

				if($params && isset($params['controller']))$params['controller']->set404PageHtml('');
				else Ddm_Controller::outputNoActionHeader();

				$this->_getTemplate()
					->addBlock($block,'view_onepage')
					->display('cms');

				return $this;
			}
		}
		if(!$params || !isset($params['controller']))Ddm_Controller::noAction();

		return $this;
	}

	/**
	 * @param array $params
	 * @return Core_Controller_Frontend_Abstract
	 */
	public function _getOnepageDataBeforeCallbakc($params){
		$params['onepage']
			->setOnepageId($this->_getNotFoundPageId())
			->setTemplateObject($this->_getTemplate());
		return $this;
	}
}
