<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Footer extends Core_Block_Abstract {

	public function getRunTime(){
		return $this->getTemplateObject()->getRunTime();
	}

	public function getQueryCount(){
		return $this->getTemplateObject()->getQueryCount();
	}

	public function getMemoryUsage(){
		return $this->getTemplateObject()->getMemoryUsage();
	}

	public function getCopyrightInfo(){
		return 'Ddmcms ver. '._VERSION_.' Copyright &copy; 2014 <a href="https://code.google.com/p/ddmcms/source/checkout" target="_brank">Open source projects</a>';
	}

	public function getTimezoneName(){
		$timezoneName = Ddm::getHelper('core')->getDateTime()->getTimezone()->getName();
		$timezoneNameLabel = Core_Model_Date_Timezone::singleton()->getTimezones($timezoneName);
		return $timezoneNameLabel ? $timezoneNameLabel : $timezoneName;
	}
}
