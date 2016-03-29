<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Template extends Core_Block_Template {
	public $activeMemu = NULL;

	/**
	 * 使用单例模式
	 * @return Admin_Block_Template
	 */
	public static function singleton(){
		if(self::$_instance===NULL || !(self::$_instance instanceof Admin_Block_Template)){
			self::$_instance = new Admin_Block_Template();
		}
		return self::$_instance;
    }

	/**
	 * @param string $string
	 * @return Admin_Block_Template
	 */
	public function setTitle($string) {
		$this->title = $string.' - '.Ddm::getTranslate('admin')->translate('后台管理中心');
		return $this;
	}

	/**
	 * @param string $memuKey
	 * @return Admin_Block_Template
	 */
	public function setActiveMemu($memuKey){
		$this->activeMemu = $memuKey;
		return $this;
	}

	/**
	 * @return Admin_Block_Template
	 */
	public function addComboboxScript(){
		$this->addJs('combobox.js','core')->addCss('combobox.js.css','core');
		return $this;
	}

	/**
	 * @return Admin_Block_Template
	 */
	public function addWindowScript(){
		$this->addJs('createwin.js','core');
		return $this;
	}

	/**
	 * @return Admin_Block_Template
	 */
	public function removeComboboxScript(){
		$this->removeJs('combobox.js')->removeCss('combobox.js.css');
		return $this;
	}

	/**
	 * @return Admin_Block_Template
	 */
	public function removeWindowScript(){
		$this->removeJs('createwin.js');
		return $this;
	}

	/**
	 * @return Admin_Block_Template
	 */
	protected function _beforeFetch(){
		$this->addBlock($this->createBlock('admin','footer','footer.phtml'),'footer');
		return $this;
	}

	protected function _construct() {
		parent::_construct();

		$this->addCss('admin.css','admin')
			->addJs('common.js','core')
			->addJs('admin.js','admin')
			->addComboboxScript();

		$this->addBlock(
				$this->createBlock('admin','header','header.phtml')
					->addBlock($this->createBlock('admin','memus','memus.phtml'),'memus')
					->addBlock($this->createBlock('core','notice')->setNotice(new Admin_Model_Notice(true)),'notice')
			,'header');

		return $this;
	}
}
