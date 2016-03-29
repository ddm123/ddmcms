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
class News_Block_News_List extends Core_Block_Frontend_List_Abstract {
	private $_categoryId = NULL;
	protected $_searchVarName = 'q';

	public function init(){
		$this->setTemplate('list.phtml');

		if($pageSize = (int)Ddm::getConfig()->getConfigValue('news/display/pagesize'))
			$this->setPageSize($pageSize);
		$this->setOrderBy((int)Ddm::getConfig()->getConfigValue('news/display/orderby')==1 ? 'main_table.update_at DESC' : 'main_table.news_id DESC');
		$this->getPageLinkBlock()->setPageVars('_use_alias',true);

		if($q = trim(Ddm_Request::get($this->getSearchVarName(),false,'')))
			$this->getTemplateObject()
				->addTitle(Ddm::getTranslate('core')->translate('搜索').': '.$q)
				->getBreadcrumb()
				->addCrumb(array('label'=>Ddm::getTranslate('core')->translate('搜索').': '.$q));

		return parent::init();
	}

	/**
	 * @return string
	 */
	public function getSearchVarName(){
		return $this->_searchVarName;
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
	 * @return News_Model_News
	 */
	public function getModelObject() {
		if($this->_modelObject===NULL){
			$this->_modelObject = new News_Model_News();
			$this->_modelObject->setLanguageId(Ddm::getLanguage()->language_id);
		}
		return $this->_modelObject;
	}

	protected function _beforeGetCount(){
		if($this->getCategoryId())$this->getModelObject()->addCategoryToFilter($this->getCategoryId());
		else $this->getModelObject()->addCategoryToSelect()->addCategoryUrlToSelect();

		$this->getModelObject()->addAttributeToSelect('title');
		if($q = trim(Ddm_Request::get($this->getSearchVarName(),false,''))){
			$this->getModelObject()->addAttributeToFilter('title',array('like'=>'%'.$q.'%'));
		}

		Ddm::dispatchEvent('parse_get_newscount_after',array('block'=>$this));
		return parent::_beforeGetCount();
	}

	protected function _beforeGetList(){
		$this->getModelObject()
			->addAttributeToSelect('url_key')
			->addAttributeToSelect('content');

		Ddm::dispatchEvent('parse_get_newslist_after',array('block'=>$this));
		return parent::_beforeGetList();
	}

	protected function _beforeToHtml(){
		//不缓存搜索结果
		if(trim(Ddm_Request::get($this->getSearchVarName(),false,''))==''){
			$cacheLifetime = Ddm::getConfig()->getConfigValue('news/cache/list');
			$cacheLifetime = $cacheLifetime===NULL ? 1800 : $cacheLifetime*60;
			//小于5分钟的缓存没任何意义, 所以这里只有设置大于5分钟以上才启用缓存
			if($cacheLifetime===0 || $cacheLifetime>=300){
				$this->setCache('news_list_'.$this->getCategoryId().'_'.$this->getPageLinkBlock()->getCurrentPage(),array('news_list'),$cacheLifetime);
			}
		}

		return parent::_beforeToHtml();
	}
}