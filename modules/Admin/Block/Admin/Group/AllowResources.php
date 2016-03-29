<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Admin_Group_AllowResources extends Core_Block_Abstract {
	private $_memusBlock = NULL;

	/**
	 * @return Admin_Block_Admin_Group_AllowResources
	 */
	public function init() {
		parent::init();
		$this->template = 'group/allowResources.phtml';
		return $this;
	}

	/**
	 * @return Admin_Model_Group
	 */
	public function getGroup(){
		return Ddm::registry('current_group');
	}

	/**
	 * @return Admin_Block_Memus
	 */
	public function getMemusBlock(){
		return $this->_memusBlock===NULL ? ($this->_memusBlock = new Admin_Block_Memus()) : $this->_memusBlock;
	}

	/**
	 * @return array
	 */
	public function getMemusData(){
		return $this->getMemusBlock()->getMemusData();
	}

	protected function _toHtml() {
		ob_start();
		include $this->getTemplateFile();
		$html = ob_get_clean();
		return $html;
	}

	protected function _beforeToHtml() {
		parent::_beforeToHtml();
		$this->_cacheId = NULL;
		return $this;
	}
}