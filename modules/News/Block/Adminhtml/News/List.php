<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Block_Adminhtml_News_List extends Admin_Block_List_Abstract {
	protected $_languageId = false;
	protected $_news = NULL;

	/**
	 * @return News_Block_Adminhtml_News_List
	 */
	protected function _initGrid(){
		$this->_grid = $this->_createGridBlock();
		$this->_grid->titleText = Ddm::getTranslate('news')->translate('新闻');
		$this->_grid->defaultSort = 'news_id';//是$columnId, 而不是字段名
		$this->_grid->defaultDir = 'DESC';
		$this->_grid->primaryKey = 'news_id';
		$this->_grid->saveFieldValueUrl = Ddm::getUrl('*/*/save-field-value');

		$this->_news = new News_Model_News();
		$this->_news->setLanguageId($this->getLanguageId())
			->addAttributeToSelect('title')
			->addAttributeToSelect('author');

		if($categoryAttribute = Ddm::getHelper('core')->getEntityAttribute('news_category','name')){
			$this->_news->getSelect()
				->leftJoin(array('category'=>$categoryAttribute->getTable()),"`category`.entity_id=main_table.category_id AND `category`.attribute_id='".$categoryAttribute->getId()."' AND `category`.language_id='0'",$this->getLanguageId() ? NULL : array('category_name'=>new Ddm_Db_Expression('IFNULL(`category`.`value`,\'-\')')));
			if($this->getLanguageId()){
				$this->_news->getSelect()
					->leftJoin(array('category2'=>$categoryAttribute->getTable()),"`category2`.entity_id=main_table.category_id AND `category2`.attribute_id='".$categoryAttribute->getId()."' AND `category2`.language_id='".$this->getLanguageId()."'",array('category_name'=>new Ddm_Db_Expression("IF(main_table.category_id='0','-',IFNULL(category2.`value`,`category`.`value`))")));
			}
		}
		$this->_grid->setSelect($this->_news->getSelect())
			->setResetGridSearchUrl(Ddm::getLanguage()->getUrl('*/*',array('language'=>$this->getLanguageId())));

		if(Ddm::getHelper('core')->getEntityAttribute('news','url_key')){
			$this->_grid->addButton('refresh_urlindex',array(
				'label'=>Ddm::getTranslate()->___('刷新%sURL索引',Ddm::getTranslate()->translate('新闻')),
				'href'=>Ddm::getLanguage()->getUrl('*/*/refresh-url-index'),
				'onclick'=>'return refreshUrlIndex(this)'
			));
			$javascrip = 'function refreshUrlIndex(e){'
				.'if(!window.refreshUrlIndexWindow)window.refreshUrlIndexWindow = msg_box("'.Ddm::getTranslate('core')->translate('正在刷新URL索引, <br />请勿关闭或刷新浏览器').'... <span id=\\"refresh-url-index-status\\"></span>","'.Ddm::getTranslate('core')->___('刷新%sURL索引',Ddm::getTranslate('admin')->translate('新闻')).'",false);'
				.'Ajax().post(typeof e == "string" ? e : e.getAttribute("href"),null,function(xhr,result){'
					.'if(result && result.url!=""){$id("refresh-url-index-status").innerHTML = Math.floor((result.from+result.limit)/result.maxId*100)+"%";refreshUrlIndex(result.url);}'
					.'else{window.refreshUrlIndexWindow.close();window.refreshUrlIndexWindow = null;msg_box("'.Ddm::getTranslate('core')->translate('已完成').'","'.Ddm::getTranslate('core')->___('刷新%sURL索引',Ddm::getTranslate('admin')->translate('新闻')).'");}'
				.'},"json");'
			.'return false;}';
			$this->_grid->addJs($javascrip);
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLanguageId(){
		return $this->_languageId===false ? ($this->_languageId = (int)Ddm_Request::get('language','int','0')) : $this->_languageId;
	}

	/**
	 * @return News_Block_Adminhtml_Onepage_List
	 */
	protected function _prepareColumns(){
		$this->getGrid()
			->addColumn('news_id',array(
				'label'=>'ID',
				'width'=>80,
				'type'=>'number',
				'field_name'=>'news_id',
				'column'=>'main_table.news_id',
				'sort'=>true
			));
		if(Ddm::getHelper('core')->getEntityAttribute('news_category','name')){
			$this->getGrid()->addColumn('category',array(
				'label'=>Ddm::getTranslate('news')->translate('分类'),
				'type'=>'text',
				'field_name'=>'category_name',
				'search'=>true,
				'edit'=>false,
				'sort'=>false
			));
		}
		$this->getGrid()
			->addColumn('title',array(
				'label'=>Ddm::getTranslate('news')->translate('标题'),
				'type'=>'text',
				'field_name'=>'title',
				'search'=>true,
				'edit'=>true,
				'sort'=>false
			))
			->addColumn('author',array(
				'label'=>Ddm::getTranslate('news')->translate('作者'),
				'type'=>'text',
				'field_name'=>'author',
				'column'=>'author',
				'search'=>true,
				'edit'=>true,
				'sort'=>false
			))
			->addColumn('views',array(
				'label'=>Ddm::getTranslate('news')->translate('浏览次数'),
				'width'=>80,
				'type'=>'number',
				'field_name'=>'views',
				'column'=>'main_table.views',
				'sort'=>true,
				'edit'=>true
			))
			->addColumn('create_at',array(
				'label'=>Ddm::getTranslate('admin')->translate('增加时间'),
				'type'=>'datetime',
				'field_name'=>'create_at',
				'column'=>'main_table.create_at',
				'search'=>true,
				'sort'=>true
			))
			->addColumn('update_at',array(
				'label'=>Ddm::getTranslate('admin')->translate('最后修改'),
				'type'=>'datetime',
				'field_name'=>'update_at',
				'column'=>'main_table.update_at',
				'search'=>true,
				'sort'=>true
			))
			->addColumn('action',array(
				'label'=>Ddm::getTranslate('admin')->translate('操作'),
				'width'=>100,
				'type'=>'action',
				'actions'=>array(
					array('label'=>Ddm::getTranslate('admin')->translate('修改'),'url'=>Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>'{news_id}')))
				)
			));
		return parent::_prepareColumns();
	}

	protected function _prepareMassaction(){
		$this->getGrid()
			->addAction('move_category',array(
				'url'=>Ddm::getLanguage()->getUrl('*/*/save-category'),
				'label'=>Ddm::getTranslate('core')->translate('更改分类')
			));
		$this->_addShowNewsCategoriesJs();

		return parent::_prepareMassaction();
	}

	public function applyFilter(array $columnOption,$fieldName,$value) {
		if(strpos($fieldName,'.')){
			parent::applyFilter($columnOption,$fieldName,$value);
		}else{
			if($fieldName=='category_name')
				parent::applyFilter($columnOption,$this->getLanguageId()
					? new Ddm_Db_Expression("IF(main_table.category_id='0','-',IFNULL(category2.`value`,`category`.`value`))")
					: new Ddm_Db_Expression('IFNULL(`category`.`value`,\'-\')'),$value);
			else
				$this->_news->addAttributeToFilter($fieldName,$this->_grid->getCondition($columnOption,$value));
		}
		return $this;
	}

	/**
	 * @return News_Block_Adminhtml_News_List
	 */
	protected function _addShowNewsCategoriesJs(){
		$js = 'var $combobox = false;';
		$js .= 'DOMLoaded(function(){actions.opt.change = function(cb,selected){';
		$js .= 'if(selected[0]=="'.Ddm::getLanguage()->getUrl('*/*/save-category').'"){$combobox = new combobox({"edit":false,"apply":$id("selectboxinput"+actions.getHash()).parentNode.parentNode,"name":"news_category","zIndex":9999,"data":'.News_Model_Category_Option::singleton()->toComboboxData().'});$combobox.init();}';
		$js .= 'else if($combobox){$e_($id("selectboxinput"+$combobox.getHash()).parentNode);$combobox = false;}';
		$js .= '}});';
		$this->getGrid()->addJs($js);
		return $this;
	}
}