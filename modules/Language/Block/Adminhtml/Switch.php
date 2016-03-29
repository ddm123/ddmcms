<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Language_Block_Adminhtml_Switch extends Core_Block_Abstract {
	protected $_url = NULL;
	protected $_queryVarName = 'language';

	/**
	 * @return Language_Block_Adminhtml_Switch
	 */
	public function init() {
		parent::init();
		$this->template = 'switch.phtml';
		return $this;
	}

	/**
	 * @param int $languageId
	 * @return string
	 */
	public function getUrl($languageId){
		if($this->_url===NULL){
			return Ddm::getUrl('*/*/*',array('_current'=>true,$this->getQueryVarName()=>$languageId));
		}
		return $this->_url.(strpos($this->_url,'?') ? '&' : '?').$this->getQueryVarName()."=$languageId";
	}

	/**
	 * @return string
	 */
	public function getQueryVarName(){
		return $this->_queryVarName;
	}

	/**
	 * @param string $url
	 * @return Language_Block_Adminhtml_Switch
	 */
	public function setUrl($url){
		$this->_url = $url;
		return $this;
	}

	/**
	 * @param string $name
	 * @return Language_Block_Adminhtml_Switch
	 */
	public function setQueryVarName($name){
		$this->_queryVarName = $name;
		return $this;
	}
}