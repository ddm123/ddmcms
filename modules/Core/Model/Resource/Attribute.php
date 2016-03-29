<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Model_Resource_Attribute extends Core_Model_Resource_Abstract {
	protected $_attributes = NULL;
	protected function _init(){
		$this->_setTable('attribute','attribute_id');
		return $this;
	}

	/**
	 * @param string $entityTyp
	 * @return Core_Model_Resource_Attribute
	 */
	public function addEntityTypeToFilter($entityTyp){
		$this->getSelect()->where('main_table.entity_type',$entityTyp);
		return $this;
	}

	/**
	 * @param string $o
	 * @return Core_Model_Resource_Attribute
	 */
	public function addOrder($o = NULL){
		if($o===NULL)$o = 'main_table.`position` ASC';
		$this->getSelect()->order($o);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttributes(){
		if($this->_attributes===NULL){
			$this->_attributes = array();
			foreach($this->getSelect()->fetchAll($this->getPrimarykey()) as $attributeId=>$_attribute){
				$this->_attributes[$attributeId] = new Core_Model_Attribute();
				$this->_attributes[$attributeId]->addData($_attribute);
				$this->_attributes[$attributeId]->setOrigData($_attribute,NULL,true);
			}
		}
		return $this->_attributes;
	}

	/**
	 * @param array $attributes
	 * @return Core_Model_Resource_Attribute
	 */
	public function setAttributes(array $attributes = NULL){
		$this->_attributes = $attributes;
		return $this;
	}
}
