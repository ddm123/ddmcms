<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method Cms_Model_Resource_Widget getResource()
 */
class Cms_Model_Widget extends Core_Model_Entity {
	protected static $_instance = NULL;

	protected function _initEntity(){
		$this->setEntity('widget')->setEntityPrimarykey('widget_id');
	}

	/**
	 * 使用单例模式
	 * @return Cms_Model_Widget
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Cms_Model_Widget()) : self::$_instance;
    }

	/**
	 * @param type $identifier
	 * @return Cms_Model_Widget
	 */
	public function loadFromIdentifier($identifier){
		if($identifier){
			$this->load($identifier,'identifier');
		}
		return $this;
	}

	protected function _beforeSave() {
		if($this->create_at===NULL)$this->create_at = Ddm_Request::server()->REQUEST_TIME;
		$this->update_at = Ddm_Request::server()->REQUEST_TIME;
		if($this->identifier){
			$this->identifier = preg_replace('/[^\w\-\.\/]/','-',$this->identifier);
		}else{
			throw new Exception('Identifier can not be empty');
		}
		return parent::_beforeSave();
	}

	protected function _afterSave() {
		Ddm::getHelper('Cms')->removeWidgetCache($this->identifier);
		return parent::_afterSave();
	}

	protected function _afterDelete() {
		Ddm::getHelper('Cms')->removeWidgetCache($this->identifier);
		return parent::_afterDelete();
	}
}
