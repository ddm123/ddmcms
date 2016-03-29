<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Block_Notice extends Core_Block_Abstract {
	protected $_notice = NULL;

	/**
	 * @param Ddm_Notice $notice
	 * @return Core_Block_Notice
	 */
	public function setNotice(Ddm_Notice $notice){
		$this->_notice = $notice;
		return $this;
	}

	/**
	 * @return Ddm_Notice
	 */
	public function getNotice(){
		return $this->_notice;
	}

	/**
	 * @return string
	 */
	protected function _toHtml() {
		return $this->getNotice()->toHtml();
	}
}
