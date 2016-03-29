<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method News_Block_View getParentBlock()
 */
class News_Block_News_Detail extends Core_Block_Abstract {
	protected $_prvNext = NULL;

	public function init(){
		$this->setTemplate('detail.phtml');

		return parent::init();
	}

	/**
	 * @return News_Model_Category
	 */
	public function getCategory(){
		return $this->getParentBlock()->getCategory();
	}

	/**
	 * @return News_Model_News
	 */
	public function getNews(){
		return $this->getParentBlock()->getNews();
	}

	/**
	 * @return array()
	 */
	public function getPrveNext(){
		if($this->_prvNext===NULL){
			$news = new News_Model_News();
			$select = $news->setLanguageId(Ddm::getLanguage()->language_id)
				->addCategoryToSelect()
				->addCategoryUrlToSelect()
				->addAttributeToSelect('title')
				->addAttributeToSelect('url_key')
				->getSelect()->order("main_table.news_id DESC")->where("main_table.news_id",array('<'=>$this->getNews()->getId()))->limit(0,1);
			$prvSql = $select->getSql();
			$select->reset(Ddm_Db_Select::ORDER)->reset(Ddm_Db_Select::WHERE)
				->order("main_table.news_id ASC")->where("main_table.news_id",array('>'=>$this->getNews()->getId()));
			$this->_prvNext = Ddm_Db::getReadConn()->fetchAll("($prvSql) UNION ALL ($select)");
		}
		return $this->_prvNext;
	}

	protected function _beforeToHtml(){
		$cacheLifetime = Ddm::getConfig()->getConfigValue('news/cache/view');
		$cacheLifetime = $cacheLifetime===NULL ? 1800 : $cacheLifetime*60;
		//小于5分钟的缓存没任何意义, 所以这里只有设置大于5分钟以上才启用缓存
		if($cacheLifetime===0 || $cacheLifetime>=300){
			$this->setCache('news_view_'.$this->getNews()->getId(),array('news_view'),$cacheLifetime);
		}

		return parent::_beforeToHtml();
	}
}