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
		$select = Ddm_Db::table(array('a'=>$this->getMainTableName()));
		$select->leftJoin(array('b'=>'config_value'),function(Ddm_Db_JoinClause $join){
			$join->on('b.config_id','=','a.config_id');
			$join->where('b.language_id','=',0);
		});
		if($languageId){
			$select->leftJoin(array('c'=>Ddm_Db::getTable('config_value')),function(Ddm_Db_JoinClause $join) use($languageId){
				$join->on('c.config_id','=','a.config_id');
				$join->where('c.language_id','=',$languageId);
			});
			$column = new Ddm_Db_Expression('IFNULL(c.config_value,b.config_value)');
		}else{
			$column = 'b.config_value';
		}
		$select->where('a.path','=',$path);
		$value = $select->value($column);
		return $value===false ? NULL : $value;
	}

	/**
	 * @param int $languageId
	 * @return array
	 */
	public function getConfigs($languageId){
		$languageId = (int)$languageId;
		$valueTable = Ddm_Db::getTable('config_value');
		$select = Ddm_Db::table(array('a'=>$this->getMainTableName()));
		$select->leftJoin(array('b'=>'config_value'),function(Ddm_Db_JoinClause $join){
			$join->on('b.config_id','=','a.config_id');
			$join->where('b.language_id','=',0);
		});
		if($languageId){
			$select->leftJoin(array('c'=>'config_value'),function(Ddm_Db_JoinClause $join) use($languageId){
				$join->on('c.config_id','=','a.config_id');
				$join->where('c.language_id','=',$languageId);
			});
			$column = new Ddm_Db_Expression('IFNULL(c.config_value,b.config_value)');
		}else{
			$column = 'b.config_value';
		}

		return $select->pluck($column,'a.path');
	}

	/**
	 * @param string $path
	 * @return Config_Model_Resource_Config
	 */
	public function removeConfigValue($path){
		$mainTable = $this->getMainTableName();
		Ddm_Db::transaction(function() use($mainTable, $path){
			$builder = Ddm_Db::table(array('a'=>$mainTable));

			$sql = 'DELETE FROM '.$builder->wrap(Ddm_Db::getTable('config_value'));
			$sql .= ' WHERE config_id IN(';
			$sql .=   'SELECT a.config_id FROM '.$builder->wrap($mainTable).' AS a WHERE a.path=?';
			$sql .= ')';
			$stmt = Ddm_Db::getWriteConn()->query($sql, array($path));
			if($stmt->rowCount()){
				$builder->where('path','=',$path);
				$builder->delete();
			}
		});
		return $this;
	}

	/**
	 * @param int $languageId
	 * @return array
	 */
	public function getConfigsFromLanguageId($languageId){
		$languageId = (int)$languageId;
		$valueTable = Ddm_Db::getTable('config_value');
		$select = Ddm_Db::table(array('a'=>$this->getMainTableName()));
		$select->leftJoin(array('b'=>'config_value'),function(Ddm_Db_JoinClause $join){
			$join->on('b.config_id','=','a.config_id');
			$join->where('b.language_id','=',0);
		});
		if($languageId){
			$select->leftJoin(array('c'=>'config_value'),function(Ddm_Db_JoinClause $join) use($languageId){
				$join->on('c.config_id','=','a.config_id');
				$join->where('c.language_id','=',$languageId);
			});
			$columns = array('a.path','default_value'=>'b.config_value','language_value'=>'c.config_value');
		}else{
			$columns = array('a.path','default_value'=>'b.config_value','language_value'=>'b.config_value');
		}
		return $select->get($columns, 'path');
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getConfigsFromPath($path){
		$select = Ddm_Db::table(array('a'=>'config_value'));
		$select->join(array('b'=>$this->getMainTableName()),'b.config_id','=','a.config_id');
		$select->where('b.path','=',$path);
		return $select->pluck('a.config_value','a.language_id');
	}

	/**
	 * @param int $configId
	 * @return array
	 */
	public function getConfigsFromConfigId($configId){
		$select = Ddm_Db::table('config_value');
		$select->where('config_id','=',(int)$configId);
		return $select->pluck('config_value','language_id');
	}

	/**
	 * @param Config_Model_Config $config
	 * @param string $path
	 * @return Config_Model_Resource_Config
	 */
	public function loadFromPath(Config_Model_Config $config,$path){
		$select = Ddm_Db::table(array('a'=>$this->getMainTableName()));
		$select->leftJoin(array('b'=>'config_value'),'b.config_id','=','a.config_id');
		$select->where('a.path','=',$path);
		if($data = $select->get(array('a.config_id','a.path','b.language_id','b.config_value'))){
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
