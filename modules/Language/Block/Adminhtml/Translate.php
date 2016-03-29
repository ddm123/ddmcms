<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Language_Block_Adminhtml_Translate extends Core_Block_Abstract {
	const PAGE_SIZE = 100;

	protected $_currentModule = NULL;

	/**
	 * @return Language_Block_Adminhtml_Translate
	 */
	public function init() {
		parent::init();
		$this->template = 'translate.phtml';
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrentModule(){
		if($this->_currentModule===NULL){
			$this->_currentModule = Ddm_Request::get('module',false,false);
			if(!$this->_currentModule || !Ddm::getEnabledModuleConfig($this->_currentModule))$this->_currentModule = 'Core';
			else $this->_currentModule = ucfirst($this->_currentModule);
		}
		return $this->_currentModule;
	}

	/**
	 * @return Language_Model_Language
	 */
	public function getLanguage(){
		return $this->getData('language');
	}

	/**
	 * @return array
	 */
	public function getTranslate(){
		$file = Ddm::getTranslate()->getCacheFile($this->getLanguage()->language_id, $this->getCurrentModule());
		$_data = is_file($file) ? unserialize(file_get_contents($file)) : array();
		$data = array();
		$index = 0;
		if($keyWords = trim(Ddm_Request::get('kw'))){
			foreach($_data as $t1=>$t2){
				if(strpos($t1,$keyWords)!==false || strpos($t2,$keyWords)!==false){
					$data[] = array($index,$t1,$t2);
				}
				$index++;
			}
		}else{
			foreach($_data as $t1=>$t2){
				$data[] = array($index,$t1,$t2);
				$index++;
			}
		}
		unset($_data);

		if($totalRows = count($data)){
			$pl = $this->createBlock('core','pagelink');/* @var $pageLink Core_Block_Pagelink */
			list($startRow, $totalPage) = $pl->parseVars(self::PAGE_SIZE, $totalRows);
			$pl->setPageVars(array('id'=>$this->getLanguage()->language_id,'module'=>$this->getCurrentModule(),'_current'=>true));
			$pl->setTotalPage($totalPage);
			$pl->beforeHtml = Ddm::getTranslate('language')->___('共 %s 条翻译',$totalRows);
			$this->addBlock($pl,'page_links');
			if($totalRows>self::PAGE_SIZE)$data = array_slice($data,$startRow,self::PAGE_SIZE);
		}
		return $data;
	}

	/**
	 * @return array
	 */
	public function getAllModules(){
		$allModules = array();
		foreach(Ddm::getModuleConfig() as $moduleName=>$moduleData){
			if(!empty($moduleData['active'])){
				$allModules[$moduleName] = $moduleName;
			}
		}
		return $allModules;
	}
}