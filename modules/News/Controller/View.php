<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Controller_View extends Core_Controller_Frontend_Abstract {
	protected $_currentNews = NULL;

	public function indexAction(){
		$news = $this->_initNews();
		if($news){
			$news->getResource()->addView($news->getId());

			$this->_getTemplate()
				->addBlock($this->_createBlock('view','news'),'view')
				->display('news');
		}else{
			Ddm_Controller::noAction();
		}
	}

	/**
	 * @return News_Model_News
	 */
	protected function _initNews(){
		if($this->_currentNews===NULL){
			$this->_currentNews = false;
			if($id = (int)Ddm_Request::get('id')){
				$news = new News_Model_News();
				$news->setLanguageId(Ddm::getLanguage()->language_id)->load($id);
				if($news->getId()){
					Ddm::register('current_news',$news);
					$this->_currentNews = $news;
				}
			}
		}
		return $this->_currentNews;
	}
}