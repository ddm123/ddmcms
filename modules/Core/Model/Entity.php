<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method Core_Model_Resource_Entity|Core_Model_Resource_Abstract getResource()
 */
abstract class Core_Model_Entity extends Core_Model_Abstract {
	protected static $_attributes = array();
	protected $_attributeSelect = NULL;
	protected $_attributesValue = array();
	protected $_entity = 0;
	protected $_entityPrimarykey = NULL;
	protected $_useDefaultValue = array();
	protected $_selectedAttributes = array();

	abstract protected function _initEntity();

	public function __construct(){
		parent::__construct();
		$this->_initEntity();
	}

	/**
	 * @param string $entity
	 * @return Core_Model_Entity
	 */
	public function setEntity($entity){
		$this->_entity = $entity;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEntity(){
		return $this->_entity;
	}

	/**
	 * @param string $entityPrimarykey
	 * @return Core_Model_Entity
	 */
	public function setEntityPrimarykey($entityPrimarykey){
		$this->_entityPrimarykey = $entityPrimarykey;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEntityPrimarykey(){
		return $this->_entityPrimarykey ? $this->_entityPrimarykey : $this->_entity.'_id';
	}

	/**
	 * @return array
	 */
	public function getAttributes(){
		if(!isset(self::$_attributes[$this->_entity])){
			if(!$this->_entity){
				throw new Exception('Undefined entity property value');
			}
			self::$_attributes[$this->_entity] = array();
			foreach(Ddm::getHelper('core')->getEntityAttributes($this->_entity) as $attribute){
				self::$_attributes[$this->_entity][$attribute['attribute_id']] = new Core_Model_Attribute();
				self::$_attributes[$this->_entity][$attribute['attribute_id']]->addData($attribute)->setOrigData($attribute,NULL,true);
			}
		}
		return self::$_attributes[$this->_entity];
	}

	/**
	 * @return Ddm_Db_Builder
	 * @throws Exception
	 */
	public function getAttributeSelect() {
		if($this->_attributeSelect===NULL){
			if(!$this->_entity){
				throw new Exception('Undefined entity property value');
			}
			$this->_attributeSelect = Ddm_Db::table(array('main_table'=>'attribute'))->where('main_table.entity_type','=',$this->_entity);
		}
		return $this->_attributeSelect;
	}

	/**
	 * @return array
	 */
	public function getVisibleAttributes(){
		$attributes = array();
		foreach($this->getAttributes() as $id=>$attribute){/* @var $attribute Core_Model_Attribute */
			if($attribute->is_visible)$attributes[$id] = $attribute;
		}
		return $attributes;
	}

	/**
	 * @param int $languageId
	 * @return Core_Model_Entity
	 */
	public function setLanguageId($languageId){
		$this->language_id = (int)$languageId;
		return $this;
	}

	/**
	 * @param string $attributeCode Attribute Code
	 * @return Core_Model_Entity
	 */
	public function addAttributeToSelect($attributeCode){
		if(isset($this->_selectedAttributes[$attributeCode]))return $this;

		$attribute = Ddm::getHelper('core')->getEntityAttribute($this->getEntity(),$attributeCode);
		if($attribute && $attribute->backend_type!='static'){
			$this->_selectedAttributes[$attributeCode] = array('attribute' => $attribute);
			$as = 'at_'.$attribute->attribute_code;
			$languageId = $attribute->is_global ? 0 : (int)$this->language_id;
			$primarykey = $this->getEntityPrimarykey();
			$builder = $this->getSelect();
			$builder->leftJoin(
				array("{$as}_d"=>$attribute->getTable(), true),
				function(Ddm_Db_JoinClause $join) use($as, $attribute, $primarykey){
					$join->on($as.'_d.entity_id','=','main_table.'.$primarykey);
					$join->where($as.'_d.attribute_id','=',(int)$attribute->attribute_id);
					$join->where($as.'_d.language_id','=',0);
				}
			);
			if($languageId){
				$builder->leftJoin(
					array($as=>$attribute->getTable(), true),
					function(Ddm_Db_JoinClause $join) use($as, $attribute, $primarykey, $languageId){
						$join->on($as.'.entity_id','=','main_table.'.$primarykey);
						$join->where($as.'.attribute_id','=',(int)$attribute->attribute_id);
						$join->where($as.'.language_id','=',$languageId);
					}
				);
				$this->_selectedAttributes[$attributeCode]['column'] = new Ddm_Db_Expression('IFNULL('.$builder->wrap($as.'.value').','.$builder->wrap($as.'_d.value').')'.($attribute->getDecimalMultiple()===1 ? '' : '/'.$attribute->getDecimalMultiple()));
				$builder->addSelect(array($attribute->attribute_code=>$this->_selectedAttributes[$attributeCode]['column']));
			}else{
				$this->_selectedAttributes[$attributeCode]['column'] = $attribute->getDecimalMultiple()===1 ? $as.'_d.value' : new Ddm_Db_Expression($builder->wrap($as.'_d.value').'/'.$attribute->getDecimalMultiple());
				$builder->addSelect(array($attribute->attribute_code=>$this->_selectedAttributes[$attributeCode]['column']));
			}
		}
		return $this;
	}

	/**
	 * @param string $attributeCode
	 * @param mixed $value
	 * @param string $operator
	 * @return Core_Model_Entity
	 */
	public function addAttributeToFilter($attributeCode,$value,$operator = NULL){
		$filter = $this->getAttributeFilter($attributeCode,$value);
		$this->getSelect()->where($filter['field'],$operator,$filter['value']);
		return $this;
	}

	/**
	 * @param string $attributeCode
	 * @param mixed $value
	 * @return array
	 */
	public function getAttributeFilter($attributeCode,$value){
		if(isset($this->_selectedAttributes[$attributeCode])){
			$attribute = $this->_selectedAttributes[$attributeCode]['attribute'];
			$fieldName = $this->_selectedAttributes[$attributeCode]['column'];
			if($attribute->getDecimalMultiple()>1){
				$value = $this->_getAttributeDataValue($value,$attribute);
			}
			return array('field'=>$fieldName,'value'=>$value);
		}

		$attribute = Ddm::getHelper('core')->getEntityAttribute($this->getEntity(),$attributeCode);
		if($attribute && $attribute->backend_type!='static'){
			return $this->addAttributeToSelect($attributeCode)->getAttributeFilter($attributeCode,$value);
		}

		return array('field'=>"main_table.$attributeCode",'value'=>$value);
	}

	/**
	 * @param string $attributeCode
	 * @param int $languageId
	 * @return mixed
	 */
	public function getAttributeValue($attributeCode,$languageId = NULL){
		if($id = $this->getId()){
			if(!isset($this->_attributesValue[$attributeCode]) && !array_key_exists($attributeCode,$this->_attributesValue)){
				if($this->$attributeCode!==NULL){
					$this->_attributesValue[$attributeCode] = $this->$attributeCode;
				}else{
					if($attribute = Ddm::getHelper('core')->getEntityAttribute($this->getEntity(),$attributeCode)){
						if($attribute->backend_type=='static'){
							if($this->issetData($attributeCode)) return $this->_attributesValue[$attributeCode] = $this->getData($attributeCode);

							$data = Ddm_Db::table($this->getResource()->getMainTableName())
								->where($this->getResource()->getPrimarykey(),'=',$id)
								->first();
							if($data){
								$this->addData($data);
								$this->_attributesValue[$attributeCode] = isset($data[$attributeCode]) ? $data[$attributeCode] : NULL;
							}else{
								$this->_attributesValue[$attributeCode] = NULL;
							}
						}else{
							if($attribute->is_global)$languageId = 0;
							else if($languageId===NULL)$languageId = (int)$this->language_id;
							$select = Ddm_Db::table(array('a'=>$attribute->getTable(), true));
							if($languageId){
								$select->leftJoin(array('b'=>$attribute->getTable(), true),function(Ddm_Db_JoinClause $join) use($languageId){
									$join->on('b.entity_id','=','a.entity_id');
									$join->on('b.attribute_id','=','a.attribute_id');
									$join->where('b.language_id','=',$languageId);
								});
								$select->addSelect(array('default_value'=>'a.value','language_value'=>'b.value'));
							}else{
								$select->addSelect(array('default_value'=>'a.value', 'language_value'=>new Ddm_Db_Expression('NULL')));
							}
							$data = $select->where('a.entity_id',$id)->where('a.attribute_id',$attribute->attribute_id)->where('a.language_id','0')->first();
							if($data){
								if($data['language_value']===NULL){
									$this->_attributesValue[$attributeCode] = $attribute->getValue($data['default_value']);
									$this->setUseDefaultAttribute($attributeCode,false);
								}else{
									$this->_attributesValue[$attributeCode] = $attribute->getValue($data['language_value']);
								}
							}else{
								$this->_attributesValue[$attributeCode] = NULL;
							}
						}
					}
				}
			}
			return $this->_attributesValue[$attributeCode];
		}
		return NULL;
	}

	/**
	 * @param string $attributeCode
	 * @return bool
	 */
	public function isUseDefaultValue($attributeCode){
		return isset($this->_useDefaultValue[$attributeCode]);
	}

	/**
	 * @param string $attributeCode
	 * @param bool $cover
	 * @return Core_Model_Entity
	 */
	public function setUseDefaultAttribute($attributeCode,$cover = true){
		if(is_array($attributeCode)){
			if($cover)$this->_useDefaultValue = array();
			foreach($attributeCode as $code){
				$this->_useDefaultValue[$code] = true;
			}
		}else{
			$this->_useDefaultValue[$attributeCode] = true;
		}
		return $this;
	}

	/**
	 * @param string|null|false $entity 如果为false, 则删除全部
	 * @return Core_Model_Entity
	 */
	public function removeAttributesCache($entity = NULL){
		if($entity===NULL)$entity = $this->_entity;
		if($entity===false){
			self::$_attributes = array();
			Ddm::getHelper('core')->removeAttributesCache(NULL);
		}else{
			if(isset(self::$_attributes[$entity]))unset(self::$_attributes[$entity]);
			Ddm::getHelper('core')->removeAttributesCache($entity);
		}
		return $this;
	}

	/**
	 * 返回传递过来的值在数据库中的值是多少(例如小数和货币属性的值显示出来和在数据库中保存的值是不相同的)
	 *
	 * @param mixed $value
	 * @param Core_Model_Attribute $attribute
	 * @return mixed
	 */
	protected function _getAttributeDataValue($value,Core_Model_Attribute $attribute){
		if($attribute->getDecimalMultiple()>1){
			if(is_array($value)){
				foreach($value as $k=>$val)$value[$k] = $this->_getAttributeDataValue($val,$attribute);
			}else{
				$value *= $attribute->getDecimalMultiple();
			}
		}
		return $value;
	}

	protected function _afterLoad() {
		if($this->getId() && ($this->getResource() instanceof Core_Model_Resource_Entity) && ($attributesValue = $this->getResource()->getAttributesValue($this))){
			$data = array();
			$languageId = (int)$this->language_id;
			foreach($this->getAttributes() as $attribute){
				$attributeCode = $attribute->attribute_code;
				if($languageId){
					if(isset($attributesValue[$languageId][$attributeCode])){
						$data[$attributeCode] = $attributesValue[$languageId][$attributeCode];
					}else if($attribute->backend_type!='static'){
						$data[$attributeCode] = isset($attributesValue[0][$attributeCode]) ? $attributesValue[0][$attributeCode] : $this->getData($attributeCode);
						$this->setUseDefaultAttribute($attributeCode,false);
					}
				}else if(isset($attributesValue[0][$attributeCode])){
					$data[$attributeCode] = $attributesValue[0][$attributeCode];
				}
			}
			$this->addData($data)->setOrigData(NULL,NULL,true);
		}
		return parent::_afterLoad();
	}

	protected function _beforeSave(){
		foreach($this->_useDefaultValue as $attributeCode=>$v){
			$this->setData($attributeCode,false);
		}

		return parent::_beforeSave();
	}
}
