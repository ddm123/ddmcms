<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

defined('IN_ADMIN') or define('IN_ADMIN', false);

abstract class Core_Controller_Abstract {
	protected $_notice = NULL;

	public function __construct() {
	}

	/**
	 * @return Core_Block_Template
	 */
	protected function _getTemplate(){
		return Core_Block_Template::singleton();
	}

	/**
	 * @return Ddm_Notice
	 */
	public function getNotice(){
		return $this->_notice===NULL ? ($this->_notice = new Ddm_Notice(false)) : $this->_notice;
	}

	/**
	 * @param string $blockClass
	 * @param string $moduleName
	 * @param string $templateName
	 * @return Core_Block_Abstract
	 */
	protected function _createBlock($blockClass,$moduleName = NULL,$templateName = NULL){
		return $this->_getTemplate()->createBlock($moduleName===NULL ? Ddm_Controller::singleton()->getModuleName() : $moduleName,$blockClass,$templateName);
	}

	public function indexAction(){
		//如果是空Action
	}

	/**
	 * @param string $actionName
	 * @return boolean
	 */
	public function isAllowed($actionName){
		return true;
	}

	/**
	 * @param Ddm_Controller $controller
	 * @return Core_Controller_Abstract
	 */
	public function noPermission($controller){
		Ddm_Controller::noPermissionAction();
		return $this;
	}

	/**
	 * @param Ddm_Controller $controller
	 * @return Core_Controller_Abstract
	 */
	public function callActionBefore($controller){
		return $this;
	}

	/**
	 * @param Ddm_Controller $controller
	 * @param string $actionName
	 * @return Core_Controller_Abstract
	 */
	public function callActionAfter($controller,$actionName){
		if($this->_notice!==NULL){
			$this->_notice->save();
		}
		return $this;
	}
}
