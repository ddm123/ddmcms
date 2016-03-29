<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Block_Widget_View extends Core_Block_Abstract {
	protected $_identifier = NULL;

	public function init() {
		$this->template = '';
		return parent::init();
	}

	/**
	 * @param string $identifier
	 * @return Cms_Block_Widget_View
	 */
	public function setIdentifier($identifier){
		$this->_identifier = $identifier;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIdentifier(){
		return $this->_identifier;
	}

	/**
	 * @param string $identifier
	 * @return Cms_Model_Widget
	 */
	public function getWidget($identifier){
		$identifier or $identifier = $this->_identifier;
		$data = Ddm::getHelper('cms')->getWidgetCache($identifier);
		$widget = new Cms_Model_Widget();
		$widget->setLanguageId(Ddm::getLanguage()->language_id);
		if($data===false){
			$widget->loadFromIdentifier($identifier);
			if($widget->getId()){
				$template = new Cms_Model_Processed_Template($widget->content,array('this'=>$this,'widget'=>$widget,'template_object'=>$this->getTemplateObject()));
				$widget->content = $template->processed();
				Ddm::getHelper('cms')->saveWidgetCache($widget);
			}
		}else{
			$widget->addData($data)->setOrigData($data,NULL,true);
		}
		return $widget;
	}

	protected function _toHtml() {
		return $this->_identifier ? $this->getWidget($this->_identifier)->content : '';
	}
}