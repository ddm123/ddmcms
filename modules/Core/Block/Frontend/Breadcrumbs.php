<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Block_Frontend_Breadcrumbs extends Core_Block_Abstract {
	protected $_crumbs = array();

	public function init(){
		$this->setTemplate('breadcrumbs.phtml');
		return parent::init();
	}

	/**
	 * @param array $crumbInfo array('label'=>?[,'link'=>?]);
	 * @return Core_Block_Frontend_Breadcrumbs
	 */
	function addCrumb(array $crumbInfo){
        if(!empty($crumbInfo['label'])){
           $this->_crumbs[] = $crumbInfo;
        }
        return $this;
    }

	/**
	 * @return array
	 */
	public function getCrumbs(){
		return $this->_crumbs;
	}

	/**
	 * @param string $delimiter
	 * @return string
	 */
	public function getCrumbsHtml($delimiter = ' &gt; '){
		$html = '';
		foreach($this->getCrumbs() as $crumbInfo){
			$html=='' or $html .= $delimiter;
			$html .= empty($crumbInfo['link']) ? Ddm_String::singleton()->escapeHtml($crumbInfo['label'])
				: '<a href="'.$crumbInfo['link'].'">'.Ddm_String::singleton()->escapeHtml($crumbInfo['label']).'</a>';
		}
		return $html;
	}
}
