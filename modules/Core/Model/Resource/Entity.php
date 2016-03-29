<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Model_Resource_Entity extends Core_Model_Resource_Abstract {
	protected $_attributeTables = NULL;

	/**
	 * @param Core_Model_Abstract $model
	 * @return Core_Model_Abstract
	 */
	protected function _afterSave(Core_Model_Abstract $model) {
		$this->_saveAttributes($model);
		return parent::_afterSave($model);
	}

	/**
	 * @param Core_Model_Entity $model
	 * @return Core_Model_Resource_Entity
	 */
	protected function _saveAttributes(Core_Model_Entity $model){
		if($model->getId()){
			foreach($model->getAttributes() as $attribute){
				if($attribute->backend_type!='static' && $model->getData($attribute->attribute_code)!=$model->getOrigData($attribute->attribute_code)){
					$this->saveAttributeValue($model,$attribute);
				}
			}
		}
		return $this;
	}

	protected function _afterDelete(Core_Model_Abstract $object) {
		if($id = (int)$object->getId()){
			foreach($this->getAttributeTables($object) as $table){
				Ddm_Db::getWriteConn()->delete($table,array('entity_id'=>$id));
			}
		}
		return parent::_afterDelete($object);
	}

	/**
	 * @param Core_Model_Entity $model
	 * @return array
	 */
	public function getAttributeTables($model){
		if($this->_attributeTables===NULL){
			$this->_attributeTables = array();
			if($model instanceof Core_Model_Entity){
				foreach($model->getAttributes() as $attribute){
					/* @var $attribute Core_Model_Attribute */
					if($attribute->backend_type!='static' && ($table = $attribute->getTable())){
						$this->_attributeTables[$table] = $table;
					}
				}
			}
		}
		return $this->_attributeTables;
	}

	/**
	 * @param Core_Model_Entity $model
	 * @param Core_Model_Attribute $attribute
	 * @param int $languageId
	 * @return Core_Model_Resource_Entity
	 */
	public function saveAttributeValue(Core_Model_Entity $model,Core_Model_Attribute $attribute,$languageId = NULL){
		$table = $attribute->getTable();
		$entityId = (int)$model->getId();
		$attributeCode = $attribute->attribute_code;
		$attributeId = (int)$attribute->attribute_id;
		if(!$attributeCode || !$attributeId)return $this;

		if($model->$attributeCode==='' && $attribute->backend_type!='varchar')$model->$attributeCode = NULL;
		$languageId = $languageId===NULL ? (int)$model->language_id : (int)$languageId;
		if($languageId){
			if($model->$attributeCode===false || $model->$attributeCode===NULL){
				if(!$attribute->is_global){
					Ddm_Db::getWriteConn()->query("DELETE FROM `$table` WHERE `entity_id`='$entityId' AND `attribute_id`='".$attributeId."' AND `language_id`='$languageId'");
				}else if($model->$attributeCode===NULL){
					Ddm_Db::getWriteConn()->save($table,array(
						'entity_id'=>$entityId,
						'attribute_id'=>$attributeId,
						'language_id'=>0,
						'value'=>NULL
					),Ddm_Db_Interface::SAVE_DUPLICATE,array('value'=>NULL));
				}
			}else{
				Ddm_Db::getWriteConn()->save($table,array(
					'entity_id'=>$entityId,
					'attribute_id'=>$attributeId,
					'language_id'=>$attribute->is_global ? 0 : $languageId,
					'value'=>$attribute->setValue($model->$attributeCode)
				),Ddm_Db_Interface::SAVE_DUPLICATE,array('value'=>$attribute->setValue($model->$attributeCode)));
			}
		}else{
			Ddm_Db::getWriteConn()->save($table,array(
				'entity_id'=>$entityId,
				'attribute_id'=>$attributeId,
				'language_id'=>0,
				'value'=>$attribute->setValue($model->$attributeCode)
			),Ddm_Db_Interface::SAVE_DUPLICATE,array('value'=>$attribute->setValue($model->$attributeCode)));
		}
		return $this;
	}

	/**
	 * @param Core_Model_Entity $model
	 * @param int $languageId
	 * @return array
	 */
	public function getAttributesValue(Core_Model_Entity $model,$languageId = NULL){
		$sql = '';
		$languageId = $languageId===NULL ? (int)$model->language_id : (int)$languageId;
		$id = (int)$model->getId();
		$attributesValue = array();
		if($attributes = $model->getAttributes()){
			foreach($this->getAttributeTables($model) as $table){
				$sql=='' or $sql .= ' UNION ALL ';
				$sql .= "SELECT b.attribute_id,b.attribute_code,b.backend_type,a.language_id,a.`value` FROM `$table` AS a ".
						"LEFT JOIN ".Ddm_Db::getTable('attribute')." AS b ON(b.attribute_id=a.attribute_id) ".
						"WHERE a.entity_id='$id' AND a.language_id".($languageId ? " IN(0,$languageId)" : '=0')." AND a.attribute_id IN(".implode(',',array_keys($attributes)).")";
			}
			foreach(Ddm_Db::getReadConn()->fetchAll($sql) as $row){
				if($row['attribute_id']){
					isset($attributesValue[$row['language_id']]) or $attributesValue[$row['language_id']] = array();
					$attributesValue[$row['language_id']][$row['attribute_code']] = $attributes[$row['attribute_id']]->getValue($row['value'],$row['backend_type']);
				}
			}
		}
		return $attributesValue;
	}
}

