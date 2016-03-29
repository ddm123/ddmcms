<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Model_Attribute_Source_Yesno extends Core_Model_Attribute_Source_Abstract {
	protected static $_instance = NULL;
	protected $_defaultOptions = NULL;

	/**
	 * 使用单例模式
	 * @return Core_Model_Attribute_Source_Yesno
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Core_Model_Attribute_Source_Yesno()) : self::$_instance;
    }

	/**
	 * @param bool $withEmpty
	 * @return array
	 */
	public function getDefaultOptions($withEmpty = false){
		if($this->_defaultOptions===NULL){
			$this->_defaultOptions = array(
				array('value'=>'1','label'=>'是'),
				array('value'=>'0','label'=>'否')
			);
		}
		if($withEmpty){
			return array_merge(array(0=>array('value'=>'','label'=>'')), $this->_defaultOptions);
		}
		return $this->_defaultOptions;
	}

	/**
	 * @param bool $withEmpty
	 * @param bool $defaultValue
	 * @return array
	 */
	public function getAllOptions($withEmpty = false,$defaultValue = true){
		if($defaultValue)return $this->getDefaultOptions($withEmpty);
		if($this->_options===NULL){
			$this->_options = array();
			foreach($this->getDefaultOptions(false) as $option){
				$this->_options[] = array('value'=>$option['value'],'label'=>Ddm::getTranslate('core')->translate($option['label']));
			}
		}
		if($withEmpty){
			return array_merge(array(0=>array('value'=>'','label'=>'')), $this->_options);
		}
		return $this->_options;
	}
}