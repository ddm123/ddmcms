<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Language_Model_Resource_Language extends Core_Model_Resource_Abstract {
	protected function _init(){
		$this->_setTable('language','language_id');
		return $this;
	}

	/**
	 * @param bool $isEnable
	 * @return array
	 */
	public function getAllLanguage($isEnable = true){
		$sql = "SELECT language_id,language_code,language_name,`position` FROM ".$this->getMainTable();
		if($isEnable)$sql .= " WHERE is_enable='1'";
		$sql .= " ORDER BY `position` ASC";
		return Ddm_Db::getReadConn()->fetchAll($sql, 'language_code');
	}

	/**
	 * 根据language_id或language_code查询一条记录
	 * @param int|string $languageId ID或Code
	 * @param bool $isEnable 是否只查询状态为启用的记录
	 * @return array|false
	 */
	public function loadById($languageId,$isEnable = true){
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from($this->getMainTable(),'*')->where(is_numeric($languageId) ? 'language_id' : 'language_code', $languageId)->limit(1);
		$isEnable and $select->where('is_enable','1');

		return Ddm_Db::getReadConn()->fetchOne($select->__toString());
	}

	/**
	 * @return int|null
	 */
	public function getIdFromHost(){
		$host = Ddm_Request::server()->HTTP_HOST or $host = Ddm_Request::server()->SERVER_NAME;
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>Ddm_Db::getTable('config_value')), 'language_id')
			->innerJoin(array('b'=>Ddm_Db::getTable('config')),"b.config_id=a.config_id AND b.`path`='web/base/web_url'")
			->where('a.config_value',array('like'=>"%/$host/%"))->limit(1);
		return Ddm_Db::getReadConn()->fetchOne($select->__toString(),true);
	}

	/**
	 * @return array
	 */
	public function getAllBaseUrl(){
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>Ddm_Db::getTable('config_value')), array('language_id','config_value'))
			->innerJoin(array('b'=>Ddm_Db::getTable('config')),"b.config_id=a.config_id AND b.`path`='web/base/web_url'");
		return $select->fetchPairs();
	}
}

