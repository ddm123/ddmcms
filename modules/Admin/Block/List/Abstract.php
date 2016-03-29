<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Admin_Block_List_Abstract extends Core_Block_Abstract {
	protected $_grid = NULL;
	protected $_gridBlockName = 'grid_list';

	/**
	 * @return Admin_Block_List_Abstract
	 */
	abstract protected function _initGrid();

	/**
	 * @return Admin_Block_Grid
	 */
	public function getGrid(){
		if($this->_grid===NULL)$this->_initGrid();
		return $this->_grid;
	}

	/**
	 * @return Admin_Block_Grid
	 */
	protected function _createGridBlock(){
		return $this->_grid===NULL ? ($this->_grid = $this->createBlock('admin','grid')->setListBlock($this)) : $this->_grid;
	}

	/**
	 * @param Ddm_Db_Select $select
	 * @return Admin_Block_Grid
	 */
	public function setSelect(Ddm_Db_Select $select){
		$this->_grid->setSelect($select);
		return $this;
	}

	/**
	 * @return Ddm_Db_Select
	 */
	public function getSelect(){
		return $this->_grid->getSelect();
	}

	/**
	 * @param array $columnOption
	 * @param string $fieldName
	 * @param mixed $value
	 * @return Admin_Block_List_Abstract
	 */
	public function applyFilter(array $columnOption,$fieldName,$value){
		$this->_grid->getSelect()->where($fieldName,$this->_grid->getCondition($columnOption,$value));
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @param string $dir
	 * @return Admin_Block_List_Abstract
	 */
	public function applySort($fieldName,$dir){
		$this->_grid->getSelect()->order("$fieldName $dir");
		return $this;
	}

	/**
	 * @return Admin_Block_List_Abstract
	 */
	protected function _prepareColumns(){
		return $this;
	}

	/**
	 * @return Admin_Block_List_Abstract
	 */
	protected function _prepareMassaction(){
		$this->getGrid()
			->addAction('delete',array(
				'url'=>Ddm::getLanguage()->getUrl('*/*/delete'),
				'label'=>Ddm::getTranslate('admin')->translate('删除'),
				'confirm'=>Ddm::getTranslate('admin')->translate('该操作不可撤消，确定要删除选择的记录？')
			));
		return $this;
	}

	protected function _beforeToHtml() {
		parent::_beforeToHtml();

		$grid = $this->getGrid();
		$this->_prepareColumns();
		Ddm::dispatchEvent('gridlist_prepare_columns_after',array('gridlist'=>$this,'grid'=>$grid));
		$this->_prepareMassaction();
		Ddm::dispatchEvent('gridlist_prepare_massaction_after',array('gridlist'=>$this,'grid'=>$grid));

		$this->addBlock($grid,$this->_gridBlockName);

		return $this;
	}

	protected function _toHtml() {
		return $this->getBlockHtml($this->_gridBlockName);
	}
}