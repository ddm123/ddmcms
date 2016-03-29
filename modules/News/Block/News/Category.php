<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method News_Block_News getParentBlock()
 */
class News_Block_News_Category extends Core_Block_Abstract {
	protected $_categories = NULL;

	public function init(){
		$this->setTemplate('category.phtml');

		return parent::init();
	}

	/**
	 * @return News_Model_Category
	 */
	public function getCategory(){
		return $this->getParentBlock()->getCategory();
	}

	/**
	 * @return array
	 */
	public function getCategories(){
		if($this->_categories===NULL){
			$category = new News_Model_Category();
			$this->_categories = $category->setLanguageId(Ddm::getLanguage()->language_id)
				->addAttributeToSelect('name')
				->addAttributeToSelect('url_key')
				->getSelect()->order("main_table.position ASC")
				->fetchAll();
		}
		return $this->_categories;
	}

	protected function _beforeToHtml(){
		$categoryId = $this->getCategory() ? $this->getParentBlock()->getCategoryId() : 0;
		$this->setCache('news_category_list_'.$categoryId,array('news_list'),43200);//缓存12个小时, 其实可以更长

		return parent::_beforeToHtml();
	}
}