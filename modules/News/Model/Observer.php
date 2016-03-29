<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Model_Observer {
	protected static $_instance = NULL;
	protected $_newsDataValueTables = NULL;

	/**
	 * 使用单例模式
	 * @return News_Model_News
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new self()) : self::$_instance;
    }

	/**
	 * @param type $params
	 * @return News_Model_Observer
	 */
	public function addCacheItem($params){
		$params['cache_block']->addItem('news_cache',array(
			'tags'=>'news_list,news_view',
			'label'=>Ddm::getTranslate('news')->translate('新闻缓存'),
			'description'=>Ddm::getTranslate('news')->translate('新闻列表页和详细页的缓存')
		));
		return $this;
	}

	/**
	 * @param array $params
	 * @return Cms_Model_Observer
	 */
	public function deleteValueFromLanguage($params){
		if($languageId = (int)$params['object']->getId()){
			foreach($this->_getNewsDataValueTables() as $table){
				Ddm_Db::getWriteConn()->delete($table,array('language_id'=>$languageId));
			}
		}
		return $this;
	}

	/**
	 * @param array $params
	 * @return Cms_Model_Observer
	 */
	public function deleteValuegFromAttribute($params){
		if($attributeId = (int)$params['object']->getId()){
			foreach($this->_getNewsDataValueTables() as $table){
				Ddm_Db::getWriteConn()->delete($table,array('attribute_id'=>$attributeId));
			}
		}
		return $this;
	}

	/**
	 * 获取News模块所有属性值表
	 * @return array
	 */
	protected function _getNewsDataValueTables(){
		if($this->_newsDataValueTables===NULL){
			$category = new News_Model_Category();
			$news = new News_Model_News();
			$this->_newsDataValueTables = array_merge($category->getResource()->getAttributeTables($category),$news->getResource()->getAttributeTables($news));
		}
		return $this->_newsDataValueTables;
	}
}
