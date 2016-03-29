<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Controller_Category extends Core_Controller_Frontend_Abstract {
	protected $_currentCategory = NULL;

	public function indexAction(){
		Ddm_Request::redirect(Ddm::getUrl('news'),302);
	}

	public function viewAction(){
		if($this->_initCategory()){
			$this->_getTemplate()
				->addBlock($this->_createBlock('news','news'),'news')
				->display('news');
		}else{
			Ddm_Controller::noAction();
		}
	}

	/**
	 * @return News_Model_Category
	 */
	protected function _initCategory(){
		if($this->_currentCategory===NULL){
			$this->_currentCategory = false;
			if($id = (int)Ddm_Request::get('id')){
				$category = new News_Model_Category();
				$category->setLanguageId(Ddm::getLanguage()->language_id)->load($id);
				if($category->getId()){
					Ddm::register('current_category',$category);
					$this->_currentCategory = $category;
				}
			}
		}
		return $this->_currentCategory;
	}
}
