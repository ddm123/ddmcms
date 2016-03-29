<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Block_News_Hot extends Core_Block_Abstract {
	private $_list = NULL;
	private $_categoryId = NULL;

	public function init(){
		$this->setTemplate('hot.phtml');

		return parent::init();
	}

	/**
	 * @return News_Model_Category
	 */
	public function getCategory(){
		return $this->getParentBlock()->getCategory();
	}

	/**
	 * @return int
	 */
	public function getCategoryId(){
		if($this->_categoryId===NULL){
			$this->_categoryId = $this->getCategory() ? $this->getParentBlock()->getCategoryId() : 0;
		}
		return $this->_categoryId;
	}

	/**
	 * @param int $limit 查询多少条记录
	 * @param int $categoryToFilter 是否只查询当前分类的新闻
	 * @return array
	 */
	public function getList($limit = 10,$categoryToFilter = false){
		if($this->_list===NULL){
			if($limit<1)$limit = 10;

			$news = new News_Model_News();
			$news->setLanguageId(Ddm::getLanguage()->language_id)
				->addAttributeToSelect('title')
				->addAttributeToSelect('url_key');
			if($categoryToFilter)$news->getSelect()->where('main_table.category_id', $this->getCategoryId());
			$news->getSelect()
				->order(array('main_table.views DESC','main_table.news_id DESC'))
				->limit(0,$limit);

			Ddm::dispatchEvent('get_news_hotlist_after',array('block'=>$this,'news'=>$news));

			$this->_list = $news->getSelect()->fetchAll();
		}
		return $this->_list;
	}

	protected function _beforeToHtml(){
		$cacheLifetime = Ddm::getConfig()->getConfigValue('news/cache/list');
		$cacheLifetime = $cacheLifetime===NULL ? 1800 : $cacheLifetime*60;
		//小于5分钟的缓存没任何意义, 所以这里只有设置大于5分钟以上才启用缓存
		if($cacheLifetime===0 || $cacheLifetime>=300){
			$this->setCache('news_hot_list_'.$this->getCategoryId(),array('news_list'),$cacheLifetime);
		}

		return parent::_beforeToHtml();
	}
}