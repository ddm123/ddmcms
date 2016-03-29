<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method Cms_Model_Resource_Onepage getResource()
 */
class Cms_Model_Onepage extends Core_Model_Entity {
	protected static $_instance = NULL;

	protected function _initEntity(){
		$this->setEntity('onepage')->setEntityPrimarykey('onepage_id');
	}

	/**
	 * 使用单例模式
	 * @return Cms_Model_Onepage
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Cms_Model_Onepage()) : self::$_instance;
    }

	/**
	 * @param bool $withEmpty
	 * @return array
	 */
	public function getOnepageToOption($withEmpty = false){
		return Ddm::getHelper('cms')->getOnepageToOption($withEmpty);
	}

	protected function _beforeSave() {
		if($this->create_at===NULL)$this->create_at = Ddm_Request::server()->REQUEST_TIME;
		$this->update_at = Ddm_Request::server()->REQUEST_TIME;
		if($this->url_key){
			$this->url_key = preg_replace('/[^\w\-\.\/]/','-',$this->url_key);
		}else if(!$this->language_id || $this->language_id==='0'){
			throw new Exception('"url_key" field can not be empty');
		}
		return parent::_beforeSave();
	}

	protected function _afterSave() {
		Ddm::getHelper('Cms')->removeOnepageCache($this->getId());
		Ddm_Cache::remove(Cms_Model_Helper::ONEPAGE_URLS_CACHE_KEY);
		return parent::_afterSave();
	}

	protected function _afterDelete() {
		Ddm::getHelper('Cms')->removeOnepageCache($this->getId());
		Ddm_Cache::remove(Cms_Model_Helper::ONEPAGE_URLS_CACHE_KEY);
		return parent::_afterDelete();
	}
}
