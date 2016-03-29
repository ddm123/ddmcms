<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Config_Model_Resource_Config extends Core_Model_Resource_Abstract {
	protected function _init(){
		$this->_setTable('config','config_id');
		return $this;
	}

	/**
	 * @param string $path
	 * @param int $languageId
	 * @return string
	 */
	public function getConfigValue($path,$languageId){
		$languageId = (int)$languageId;
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>$this->getMainTable()), array('value'=>new Ddm_Db_Expression($languageId ? 'IFNULL(c.config_value,b.config_value)' : 'b.config_value')))
			->leftJoin(array('b'=>Ddm_Db::getTable('config_value')),"b.config_id=a.config_id AND b.language_id='0'");
		if($languageId){
			$select->leftJoin(array('c'=>Ddm_Db::getTable('config_value')),"c.config_id=a.config_id AND c.language_id='$languageId'");
		}
		$select->where('a.path',$path);
		return $select->fetchOne(true);
	}

	/**
	 * @param int $languageId
	 * @return array
	 */
	public function getConfigs($languageId){
		$languageId = (int)$languageId;
		$valueTable = Ddm_Db::getTable('config_value');
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>$this->getMainTable()),array('path'=>'path','value'=>new Ddm_Db_Expression($languageId ? 'IFNULL(c.config_value,b.config_value)' : 'b.config_value')))
			->leftJoin(array('b'=>$valueTable),"b.config_id=a.config_id AND b.language_id='0'");
		if($languageId)$select->leftJoin (array('c'=>$valueTable),"c.config_id=a.config_id AND c.language_id='$languageId'");
		return $select->fetchPairs();
	}

	/**
	 * @param string $path
	 * @return Config_Model_Resource_Config
	 */
	public function removeConfigValue($path){
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from($this->getMainTable(),'config_id')->where('path',$path);
		if(Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('config_value'),array('config_id'=>array('IN'=>new Ddm_Db_Expression("($select)"))))){
			Ddm_Db::getWriteConn()->delete($this->getMainTable(),array('path'=>$path));
		}
		return $this;
	}

	/**
	 * @param int $languageId
	 * @return array
	 */
	public function getConfigsFromLanguageId($languageId){
		$languageId = (int)$languageId;
		$valueTable = Ddm_Db::getTable('config_value');
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>$this->getMainTable()),array('path'))
			->leftJoin(array('b'=>$valueTable),"b.config_id=a.config_id AND b.language_id='0'",array('default_value'=>'config_value'));
		if($languageId){
			$select->leftJoin(array('c'=>$valueTable),"c.config_id=a.config_id AND c.language_id='$languageId'",array('language_value'=>'config_value'));
		}else{
			$select->columns(array('language_value'=>'config_value'),'b');
		}
		return $select->fetchAll('path');
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getConfigsFromPath($path){
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>Ddm_Db::getTable('config_value')),array('language_id','config_value'))
			->innerJoin(array('b'=>$this->getMainTable()),"b.config_id=a.config_id AND b.`path`='".addslashes($path)."'");
		return $select->fetchPairs();
	}

	/**
	 * @param int $configId
	 * @return array
	 */
	public function getConfigsFromConfigId($configId){
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>Ddm_Db::getTable('config_value')),array('language_id','config_value'))
			->where('a.config_id',(int)$configId);
		return $select->fetchPairs();
	}

	/**
	 * @param Config_Model_Config $config
	 * @param string $path
	 * @return Config_Model_Resource_Config
	 */
	public function loadFromPath(Config_Model_Config $config,$path){
		$select = Ddm_Db::getReadConn()->getSelect();
		$select->from(array('a'=>$this->getMainTable()),array('config_id','path'))
			->leftJoin(array('b'=>Ddm_Db::getTable('config_value')),"b.config_id=a.config_id",array('language_id','config_value'))
			->where('a.path',$path);
		if($data = $select->fetchAll()){
			$_data = array('values'=>array());
			foreach($data as $row){
				isset($_data['config_id']) or $_data['config_id'] = $row['config_id'];
				isset($_data['path']) or $_data['path'] = $row['path'];
				if(''!==(string)$row['language_id'])$_data['values'][$row['language_id']] = $row['config_value'];
			}
			if(!$_data['values'])unset($_data['values']);
			$config->addData($_data);
		}
		return $this;
	}

	protected function _afterSave(Core_Model_Abstract $object){
		parent::_afterSave($object);
		if(($id = (int)$object->getId()) && is_array($values = $object->getData('values'))){
			foreach($values as $languageId=>$value){
				if($value===false){
					Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('config_value'),array('config_id'=>$id,'language_id'=>$languageId));
				}else{
					Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('config_value'),array(
						'config_id'=>$id,
						'language_id'=>$languageId,
						'config_value'=>$value
					),Ddm_Db_Interface::SAVE_DUPLICATE,array('config_value'=>$value));
				}
			}
		}
		return $this;
	}

	protected function _afterDelete(Core_Model_Abstract $object){
		Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('config_value'),array('config_id'=>(int)$object->getId()));
		return $this;
	}
}
