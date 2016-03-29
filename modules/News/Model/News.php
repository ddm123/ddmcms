<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method News_Model_Resource_News getResource()
 */
class News_Model_News extends Core_Model_Entity {
	protected static $_instance = NULL;
	protected $_url = NULL;

	protected function _initEntity(){
		$this->setEntity('news')->setEntityPrimarykey('news_id');
	}

	/**
	 * 使用单例模式
	 * @return News_Model_News
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new self()) : self::$_instance;
    }

	/**
	 * @return News_Model_News
	 */
	public function addCategoryToSelect(){
		$attribute = Ddm::getHelper('core')->getEntityAttribute('news_category','name');
		if($attribute){
			$languageId = intval($this->language_id===NULL ? Ddm::getLanguage()->language_id : $this->language_id);
			$this->getSelect()
				->leftJoin(array('category_name_d'=>$attribute->getTable()),
					"category_name_d.entity_id=main_table.`category_id` AND category_name_d.attribute_id='".$attribute->attribute_id."' AND category_name_d.language_id='0'",
					$languageId ? NULL : array('category_name'=>'value'));
			if($languageId){
				$this->getSelect()
					->leftJoin(array('category_name_l'=>$attribute->getTable()),
						"category_name_l.entity_id=main_table.`category_id` AND category_name_l.attribute_id='".$attribute->attribute_id."' AND category_name_l.language_id='$languageId'",
					array('category_name'=>new Ddm_Db_Expression('IFNULL(category_name_l.`value`,category_name_d.`value`)')));
			}
		}
		return $this;
	}

	/**
	 * @return News_Model_News
	 */
	public function addCategoryUrlToSelect(){
		$attribute = Ddm::getHelper('core')->getEntityAttribute('news_category','url_key');
		if($attribute){
			$languageId = intval($this->language_id===NULL ? Ddm::getLanguage()->language_id : $this->language_id);
			$this->getSelect()
				->leftJoin(array('category_url_d'=>$attribute->getTable()),
					"category_url_d.entity_id=main_table.`category_id` AND category_url_d.attribute_id='".$attribute->attribute_id."' AND category_url_d.language_id='0'",
					$languageId ? NULL : array('category_url'=>'value'));
			if($languageId){
				$this->getSelect()
					->leftJoin(array('category_url_l'=>$attribute->getTable()),
						"category_url_l.entity_id=main_table.`category_id` AND category_url_l.attribute_id='".$attribute->attribute_id."' AND category_url_l.language_id='$languageId'",
					array('category_url'=>new Ddm_Db_Expression('IFNULL(category_url_l.`value`,category_url_d.`value`)')));
			}
		}
		return $this;
	}

	/**
	 * 根据url_key获取一条数据
	 * @param string $urlKey
	 * @return News_Model_News
	 */
	public function loadFromUrlKey($urlKey){
		if($urlKey){
			$this->getResource()->loadFromUrlKey($this,$urlKey);
			$this->_afterLoad();
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getNewsUrl(){
		if($this->news_id){
			return $this->_url===NULL ? ($this->_url = Ddm::getHelper('news')->getNewsUrlFromUrlKey($this->news_id,$this->url_key)) : $this->_url;
		}
		return NULL;
	}

	/**
	 * @param int $categoryId
	 * @return News_Model_News
	 */
	public function addCategoryToFilter($categoryId){
		$this->getSelect()->where('main_table.category_id',(int)$categoryId);
		return $this;
	}

	protected function _beforeSave() {
		if($this->create_at===NULL)$this->create_at = Ddm_Request::server()->REQUEST_TIME;
		$this->update_at = Ddm_Request::server()->REQUEST_TIME;
		if($this->url_key){
			$this->url_key = preg_replace('/[^\w\-\.\/]/','-',$this->url_key);
			if(!preg_match('/(?:\/|\.html?)$/i',$this->url_key))$this->url_key .= '.html';
		}
		if($this->category_id = (int)$this->category_id){
			//验证是否真的有该分类
			News_Model_Category::singleton()->categoryExists($this->category_id) or $this->category_id = 0;
		}
		return parent::_beforeSave();
	}
}
