<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Step_Abstract {
	protected $_steps = array();
	protected $_title = '安装界面';
	protected $_template = NULL;
	protected $_headerTemplate = 'header.phtml';
	protected $_footerTemplate = 'footer.phtml';

	/**
	 * @param string $title
	 * @return Step_Abstract
	 */
	public function addTitle($title){
		$this->_title = "$title - $this->_title";
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle(){
		return $this->_title;
	}

	/**
	 * @param array $steps
	 * @return Step_Abstract
	 */
	public function setSteps(array $steps){
		$this->_steps = $steps;
		return $this;
	}

	/**
	 * @param string $template
	 * @return Step_Abstract
	 */
	public function setTemplate($template){
		$this->_template = $template;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplate(){
		return $this->_template;
	}

	/**
	 * @return Step_Abstract
	 */
	public function clearCache(){
		$cacheDevice = Ddm_Cache::singleton()->getCache();/** @var Ddm_Cache_File|Ddm_Cache_Sqlite $cacheDevice */
		if($cacheDevice instanceof Ddm_Cache_File){
			if(!is_dir($cacheDevice->getCacheDir()))return $this;
		}else if($cacheDevice instanceof Ddm_Cache_Sqlite){
			if(!file_exists($cacheDevice->getCacheDir()))return $this;
		}
		Ddm::getConfig()->clearCache();
		Ddm_Cache::clear();
		Ddm_Db::clearCache();
		return $this;
	}

	/**
	 * @return Step_Abstract
	 */
	public function run(){
		try {
			$this->_beforeRun();
			if($this->getTemplate()){
				include SITE_ROOT.'/design/install/template/'.$this->_headerTemplate;
				include SITE_ROOT.'/design/install/template/'.$this->getTemplate();
				include SITE_ROOT.'/design/install/template/'.$this->_footerTemplate;
			}
			$this->_afterRun();
		} catch (Exception $e) {
			echo '<pre>'.$e->getMessage().'<br/>'.$e->getTraceAsString().'</pre>';
		}
		return $this;
	}

	/**
	 * @return Step_Abstract
	 */
	protected function _beforeRun(){
		return $this;
	}

	/**
	 * @return Step_Abstract
	 */
	protected function _afterRun(){
		return $this;
	}
}
