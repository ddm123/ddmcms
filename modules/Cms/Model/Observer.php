<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Model_Observer {
	protected static $_instance = NULL;

	/**
	 * 使用单例模式
	 * @return Cms_Model_Observer
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new self()) : self::$_instance;
    }

	/**
	 * @param array $params
	 * @return Cms_Model_Observer
	 */
	public function deleteConfigFromLanguage($params){
		if($languageId = (int)$params['object']->getId()){
			foreach($this->_getCmsDataValueTables() as $table){
				Ddm_Db::getWriteConn()->delete($table,array('language_id'=>$languageId));
			}
		}
		return $this;
	}

	/**
	 * @param array $params
	 * @return Cms_Model_Observer
	 */
	public function deleteConfigFromAttribute($params){
		if($attributeId = (int)$params['object']->getId()){
			foreach($this->_getCmsDataValueTables() as $table){
				Ddm_Db::getWriteConn()->delete($table,array('attribute_id'=>$attributeId));
			}
		}
		return $this;
	}

	/**
	 * @param array $params
	 * @return Cms_Model_Observer
	 */
	public function matchesCmsPage($params){
		if($params['controller']->runModule())return $this;

		$urlKey = $params['controller']->getUriPath();
		$onepageId = intval($urlKey=='' ? Ddm::getConfig()->getConfigValue('web/pages/home') : Ddm::getHelper('cms')->getOnepageIdFromUrlKey($urlKey))
			or (defined('IN_ADMIN') and IN_ADMIN)
			or $onepageId = intval(Ddm::getConfig()->getConfigValue('web/pages/not_found'));
		if($onepageId){
			if($onepageId==(int)Ddm::getConfig()->getConfigValue('web/pages/not_found'))Ddm_Controller::outputNoActionHeader();
			if($onepageId==Ddm::getConfig()->getConfigValue('web/pages/home'))Ddm_Controller::singleton()->setHomePage(true);

			$params['controller']
				->setModuleName('Cms')
				->setControllerName('index')
				->setActionName('view');
			$params['controller']->resetRunModule();
			Ddm_Request::singleton()->setParam('id',$onepageId);
		}
		return $this;
	}

	/**
	 * 获取CMS模块所有属性值表
	 * @return array
	 */
	protected function _getCmsDataValueTables(){
		$onepage = new Cms_Model_Onepage();
		$widget = new Cms_Model_Widget();
		return array_merge($onepage->getResource()->getAttributeTables($onepage),$widget->getResource()->getAttributeTables($widget));
	}
}

