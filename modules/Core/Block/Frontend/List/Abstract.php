<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Block_Frontend_List_Abstract extends Core_Block_Abstract {
	protected $_pageVarName = 'p';
	protected $_pageSize = 20;
	protected $_orderBy = NULL;
	protected $_totalRows = NULL;
	protected $_list = NULL;
	protected $_modelObject = NULL;
	protected $_pageLinkBlock = NULL;
	protected $_startRowNumber = 1;

	/**
	 * @return Core_Model_Abstract
	 */
	abstract public function getModelObject();

	/**
	 * @param string $pageVarName
	 * @return Core_Block_Frontend_List_Abstract
	 */
	public function setPageVarName($pageVarName){
		$this->_pageVarName = $pageVarName;
		return $this;
	}

	/**
	 * @param int $pageSize
	 * @return Core_Block_Frontend_List_Abstract
	 */
	public function setPageSize($pageSize){
		$this->_pageSize = (int)$pageSize;
		if($this->_pageSize<1)$this->_pageSize = 20;
		return $this;
	}

	/**
	 * @param string $orderBy
	 * @return Core_Block_Frontend_List_Abstract
	 */
	public function setOrderBy($orderBy){
		$this->_orderBy = $orderBy;
		return $this;
	}

	/**
	 * @param Core_Model_Abstract $model
	 * @return Core_Block_Frontend_List_Abstract
	 */
	public function setModelObject(Core_Model_Abstract $model){
		$this->_modelObject = $model;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPageVarName(){
		return $this->_pageVarName;
	}

	/**
	 * @return int
	 */
	public function getPageSize(){
		return $this->_pageSize;
	}

	/**
	 * @return string
	 */
	public function getOrderBy(){
		return $this->_orderBy;
	}

	/**
	 * @return Core_Block_Pagelink
	 */
	public function getPageLinkBlock(){
		return $this->_pageLinkBlock===NULL ? ($this->_pageLinkBlock = $this->createBlock('Core','Pagelink')->setPageVarName($this->getPageVarName())) : $this->_pageLinkBlock;
	}

	/**
	 * 获取总记录数
	 * @return int
	 */
	public function getCount(){
		if($this->_totalRows===NULL){
			$this->_beforeGetCount();
			$this->_totalRows = (int)$this->getModelObject()->getCountSelect()->fetchOne(true);
			$this->_afterGetCount();
		}
		return $this->_totalRows;
	}

	public function getList(){
		if($this->_list===NULL){
			if($this->getCount()>0){
				$this->_beforeGetList();

				$vars = $this->getPageLinkBlock()->parseVars($this->getPageSize(),$this->getCount());
				$this->_startRowNumber = $vars[0] + 1;
				$this->_list = $this->getModelObject()
					->setLimit($this->getPageSize(),$vars[0])
					->getSelect()->order($this->getOrderBy())
					->fetchAll();

				$this->_afterGetList();
				$this->addBlock($this->getPageLinkBlock(),'page');
			}else{
				$this->_list = array();
			}
		}
		return $this->_list;
	}

	/**
	 * @return int
	 */
	public function getStartRowNumber(){
		return $this->_startRowNumber;
	}

	/**
	 * @return string
	 */
	public function getPagesHtml(){
		return $this->getBlockHtml('page');
	}

	/**
	 * @return Core_Block_Frontend_List_Abstract
	 */
	public function clear(){
		$this->_totalRows = NULL;
		$this->_list = NULL;
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_List_Abstract
	 */
	protected function _beforeGetCount(){
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_List_Abstract
	 */
	protected function _beforeGetList(){
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_List_Abstract
	 */
	protected function _afterGetCount(){
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_List_Abstract
	 */
	protected function _afterGetList(){
		return $this;
	}
}