<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Model_Category_Option extends Core_Model_Attribute_Source_Abstract {
	protected static $_instance = NULL;
	protected $_options = array();
	protected $_optionsFromLanguage = array();

	/**
	 * 使用单例模式
	 * @return News_Model_Category_Option
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new News_Model_Category_Option()) : self::$_instance;
    }

	/**
	 * @param bool $withEmpty
	 * @param bool $defaultValue
	 * @return array
	 */
	public function getAllOptions($withEmpty = false,$defaultValue = true){
		return $this->getAllOptionsFromLanguage($withEmpty, $defaultValue ? 0 : NULL);
	}

	/**
	 * @param bool $withEmpty
	 * @param int $languageId
	 * @return array
	 */
	public function getAllOptionsFromLanguage($withEmpty = false,$languageId = NULL){
		$languageId = $languageId===NULL ? Ddm::getLanguage()->language_id : (int)$languageId;
		if(!isset($this->_optionsFromLanguage[$languageId])){
			$this->_optionsFromLanguage[$languageId] = array();
			$category = new News_Model_Category();
			$category->setLanguageId($languageId)->getSelect()
				->resetColumns()
				->columns(array('category_id','position'),'main_table')
				->order("main_table.position ASC");
			$category->addAttributeToSelect('name');

			foreach($category->getSelect()->fetchAll() as $item){
				$this->_optionsFromLanguage[$languageId][] = array('value'=>$item['category_id'],'label'=>$item['name']);
			}
		}
		if($withEmpty){
			return array_merge(array(0=>array('value'=>'','label'=>'')), $this->_optionsFromLanguage[$languageId]);
		}

		return $this->_optionsFromLanguage[$languageId];
	}
}