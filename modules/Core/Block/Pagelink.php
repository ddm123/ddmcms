<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Block_Pagelink extends Core_Block_Abstract {
	public $pageVarName = 'p';
	public $beforeHtml = '';
	public $afterHtml = '';
	public $showinput = true;

	private $_totalPage = 0;
	private $_currentPage = NULL;
	private $_currentUrl = NULL;
	private $_pageVars = array();

	/**
	 * @return Core_Block_Pagelink
	 */
	public function init() {
		parent::init();
		$this->_pageVars = array('_current'=>true,$this->pageVarName=>'{page}');
		$this->template = 'pagelink.phtml';
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage(){
		if($this->_currentPage===NULL){
			$this->_currentPage = (int)Ddm_Request::get($this->pageVarName);
			if(!$this->_currentPage || $this->_currentPage<1)$this->_currentPage = 1;
			if($this->_totalPage && $this->_currentPage>$this->_totalPage)$this->_currentPage = $this->_totalPage;
		}
		return $this->_currentPage;
	}

	/**
	 * 计算起始序号
	 * @return int
	 */
	public function getStartNum(){
		$page = $this->getCurrentPage();
		if($page<=5 || $this->_totalPage<=10){
			$startNum = 1;
		}else{
			//$startNum = ($page%5==0) ? $page-4 : $page-($page%5+4);
			$startNum = $page-5;
			if($this->_totalPage-$startNum<10){
				$startNum = $this->_totalPage-9;
			}
		}
		return $startNum;
	}

	/**
	 * @param array|string $vars
	 * @param mixed $value
	 * @return Core_Block_Pagelink
	 */
	public function setPageVars($vars,$value = NULL){
		if(is_array($vars))$this->_pageVars = array_merge($this->_pageVars,$vars);
		else $this->_pageVars[$vars] = $value;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getPageVars(){
		return $this->_pageVars;
	}

	/**
	 * @param string $url
	 * @return Core_Block_Pagelink
	 */
	public function setCurrentUrl($url){
		$this->_currentUrl = $url;
		return $this;
	}

	public function getCurrentUrl(){
		return $this->_currentUrl===NULL ? ($this->_currentUrl = Ddm::getLanguage()->getUrl('*/*/*',$this->getPageVars())) : $this->_currentUrl;
	}

	public function getUrlFromPage($page){
		return str_replace('{page}',$page,$this->getCurrentUrl());
	}

	/**
	 * @param int $num
	 * @return Core_Block_Pagelink
	 */
	public function setTotalPage($num){
		$this->_totalPage = abs((int)$num);
		$this->_currentPage = NULL;//重新获取当前页数
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTotalPage(){
		return $this->_totalPage;
	}

	/**
	 * @param string $varName
	 * @return Core_Block_Pagelink
	 */
	public function setPageVarName($varName){
		$this->pageVarName = $varName;
		$this->_currentPage = NULL;//重新获取当前页数
		return $this;
	}

	/**
	 * 获取分页所需的变量
	 * 返回一个数组，array(0=>起始行数,1=>总页数,2=>当前第几页)
	 * @param int $pageSize
	 * @param int $totalRows
	 * @return array
	 */
	public function parseVars($pageSize,$totalRows){
		$page = $this->getCurrentPage();
		$totalPage = ceil($totalRows/$pageSize);

		if($page>$totalPage && $totalPage>0)$page = $totalPage;
		$this->_currentPage = $page;
		$this->_totalPage = $totalPage;
		$startRow = ($page-1)*$pageSize;
		return array($startRow, $totalPage, $page);
	}
}
