<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Config_Model_Observer {
	/**
	 * @param array $params
	 * @return Config_Model_Observer
	 */
	public function deleteConfigFromLanguage($params){
		if($languageId = (int)$params['object']->getId()){
			Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('config_value'),array('language_id'=>$languageId));
		}
		return $this;
	}
}

