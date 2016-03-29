<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Controller_Cache extends Admin_Controller_Abstract {
	public function indexAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('cache','admin'),'cache')
			->setTitle(Ddm::getTranslate('admin')->translate('缓存管理'))
			->setActiveMemu('system')
			->display();
	}

	public function deleteAction(){
		$cache = Ddm_Request::post('cache','/^[\w,]+$/',NULL);
		if($cache && is_array($cache)){
			foreach($cache as $tags){
				if($tags=='config'){
					Ddm::getConfig()->clearCache();
				}
				Ddm_Cache::singleton()->removeByTags(explode(',',$tags));
			}
			$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已删除选中的缓存'));
		}else{
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('至少要选择一个'));
		}

		Ddm_Request::redirect(Ddm::getUrl('*/cache'));
	}

	public function clearAction(){
		Ddm::getConfig()->clearCache();
		Ddm_Cache::clear();
		$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已删除所有的缓存'));
		Ddm_Request::redirect(Ddm::getUrl('*/cache'));
	}

	public function isAllowed($actionName){
		return $this->_isAllowed($actionName,'core/cache');
	}
}
