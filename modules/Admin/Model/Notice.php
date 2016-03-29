<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Model_Notice extends Ddm_Notice {
	/**
	 * @return array
	 */
	protected function _getNoticesFromCookie(){
		$notices = Ddm::getHelper('admin')->getSession()->getData($this->_cookieVarname);
		if($notices){
			Ddm::getHelper('admin')->getSession()->unsetData((string)$this->_cookieVarname);
			$this->_notices = $notices;
		}
		return $this->_notices;
	}

	/**
	 * @param string $str
	 * @return Ddm_Notice
	 */
	public function save(){
		if($this->_notices){
			Ddm::getHelper('admin')->getSession()->setData((string)$this->_cookieVarname,$this->_notices);
			$this->_notices = array();
		}
		return $this;
	}
}
