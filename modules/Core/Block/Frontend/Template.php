<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Block_Frontend_Template extends Core_Block_Template {
	/**
	 * 使用单例模式
	 * @return Core_Block_Frontend_Template
	 */
	public static function singleton(){
		if(self::$_instance===NULL || !(self::$_instance instanceof Core_Block_Frontend_Template)){
			self::$_instance = new Core_Block_Frontend_Template();
		}
		return self::$_instance;
    }

	/**
	 * @return Core_Block_Frontend_Breadcrumbs
	 */
	public function getBreadcrumb(){
		return $this->getBlock('breadcrumbs');
	}

	/**
	 * @param string $string
	 * @return Core_Block_Frontend_Template
	 */
	public function setTitle($string) {
		$this->title = $string.' - '.Ddm::getConfig()->getConfigValue('web/base/web_name');
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_Template
	 */
	public function addComboboxScript(){
		$this->addJs('combobox.js','core')->addCss('combobox.js.css','core');
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_Template
	 */
	public function addWindowScript(){
		$this->addJs('createwin.js','core');
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_Template
	 */
	public function removeComboboxScript(){
		$this->removeJs('combobox.js')->removeCss('combobox.js.css');
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_Template
	 */
	public function removeWindowScript(){
		$this->removeJs('createwin.js');
		return $this;
	}

	/**
	 * @return Core_Block_Frontend_Template
	 */
	protected function _construct(){
		parent::_construct();

		$this->title = Ddm::getConfig()->getConfigValue('web/base/web_name');
		$this->addCss('common.css','core')
			->addCss('style.css')
		    ->addJs('common.js','core');

		$breadcrumb = $this->createBlock('core','frontend_breadcrumbs')
			->addCrumb(array('label'=>Ddm::getTranslate('core')->translate('首页'),'link'=>Ddm::getHomeUrl()))
			->isFetch(false);
		$this->addBlock($breadcrumb,'breadcrumbs');
		$this->addBlock($this->createBlock('core','notice')->isFetch(false)->setNotice(new Ddm_Notice(true)),'notice');

		return $this;
	}
}
