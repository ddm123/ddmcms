<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Notfound extends Core_Block_Abstract {
	/**
	 * @return Admin_Block_Nopermission
	 */
	public function init() {
		parent::init();
		$this->template = 'notfound.phtml';
		return $this;
	}
}