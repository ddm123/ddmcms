<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Cache extends Core_Block_Abstract {
	protected $_items = array();
	protected $_grid = NULL;

	/**
	 * @return Admin_Block_Cache
	 */
	public function init() {
		parent::init();
		//$this->template = 'cache.phtml';
		return $this;
	}

	/**
	 * @return Admin_Block_Grid
	 */
	public function getGrid(){
		if($this->_grid===NULL){
			$this->_grid = $this->createBlock('admin','grid');
			$this->_grid->defaultLimit = false;
			$this->_grid->primaryKey = 'tags';
			$this->_grid->idName = 'cache';
			$this->_grid->titleText = Ddm::getTranslate('admin')->translate('缓存管理');

			$this->_grid->removeButton('add');
			$this->_grid->addButton('clear_all',array(
				'label'=>Ddm::getTranslate('admin')->translate('删除所有缓存'),
				'href'=>Ddm::getUrl('*/*/clear'),
				'icon'=>'icon-trash'
			))
			->addAction('delete',array(
				'url'=>Ddm::getLanguage()->getUrl('*/*/delete'),
				'label'=>Ddm::getTranslate('admin')->translate('删除选中'),
				'icon'=>'icon-trash'
			));
		}
		return $this->_grid;
	}

	/**
	 * @param string $name
	 */
	public function getItem($name = NULL){
		return $name===NULL ? $this->_items : (isset($this->_items[$name]) ? $this->_items[$name] : NULL);
	}

	/**
	 * @param string $name
	 * @param array $itemOption
	 * @param int $index
	 * @return Admin_Block_Cache
	 */
	public function addItem($name, array $itemOption, $index = NULL){
		if(is_int($index)){
			if($index==0)$this->_items = array($name=>$itemOption) + $this->_items;
			else{
				$a = array_slice($this->_items,0,$index,true);
				$b = array_slice($this->_items,$index,NULL,true);
				$this->_items = array_merge($a,array($name=>$itemOption),$b);
			}
		}else{
			$this->_items[$name] = $itemOption;
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @param array $itemOption
	 * @return Admin_Block_Cache
	 */
	public function updateItem($name, array $itemOption){
		if(isset($this->_items[$name])){
			$this->_items[$name] = $itemOption;
		}
		return $this;
	}

	/**
	 * @return Admin_Block_Cache
	 */
	protected function _prepareColumns(){
		$this->getGrid()
			->addColumn('label',array(
				'label'=>Ddm::getTranslate('admin')->translate('名称'),
				'field_name'=>'label'
			))
			->addColumn('description',array(
				'label'=>Ddm::getTranslate('admin')->translate('描述'),
				'field_name'=>'description'
			))
			->addColumn('tags',array(
				'label'=>'Tags',
				'field_name'=>'tags',
			));
		return $this;
	}

	/**
	 * @return Admin_Block_Cache
	 */
	protected function _prepareItems(){
		$this
			->addItem('config',array(
				'tags'=>'config',
				'label'=>Ddm::getTranslate('admin')->translate('配置缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('后台设置, define.xml.php和主题设计config.xml的缓存')
			))
			->addItem('language',array(
				'tags'=>'language',
				'label'=>Ddm::getTranslate('admin')->translate('网站语言缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('一般不需要手动来清除该缓存')
			))
			->addItem('module',array(
				'tags'=>'module',
				'label'=>Ddm::getTranslate('admin')->translate('模块缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('如果新增或禁用了某模块, 或修改etc/config.xml, 需要清除该缓存')
			))
			->addItem('admin_user',array(
				'tags'=>'admin_user',
				'label'=>Ddm::getTranslate('admin')->translate('管理员缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('清除管理员的权限等缓存')
			))
			->addItem('html_block',array(
				'tags'=>'html_block',
				'label'=>Ddm::getTranslate('admin')->translate('Block的缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('所有Block的缓存, 包括单页面和自定义块的缓存')
			))
			->addItem('onepage',array(
				'tags'=>'onepage',
				'label'=>Ddm::getTranslate('admin')->translate('单页面缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('也包括首页缓存, 如果你选择了清除Block的缓存, 不需要再选择清除该缓存')
			))
			->addItem('widget',array(
				'tags'=>'widget',
				'label'=>Ddm::getTranslate('admin')->translate('自定义块缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('如果你选择了清除Block的缓存, 不需要再选择清除该缓存')
			));

		Ddm::dispatchEvent('cache_prepare_items_after', array('cache_block'=>$this));

		$itemTags = array();
		foreach($this->getItem() as $itemOption){
			$itemTags = array_merge($itemTags,explode(',',strtoupper($itemOption['tags'])));
		}
		$allCacheTags = Ddm_Cache::singleton()->getCache()->getAllTags();
		$tags = array_diff($allCacheTags,$itemTags);
		if($tags){
			$this->addItem('other',array(
				'tags'=>implode(',',$tags),
				'label'=>Ddm::getTranslate('admin')->translate('其它缓存'),
				'description'=>Ddm::getTranslate('admin')->translate('还有一些没有在上面列出来的其它缓存')
			));
		}

		return $this;
	}

	protected function _beforeToHtml(){
		$this->_prepareColumns()->_prepareItems();
		$this->getGrid()->setListData($this->_items);
		return parent::_beforeToHtml();
	}

	protected function _toHtml() {
		return $this->getGrid()->toHtml();
	}
}
