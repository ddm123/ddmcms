<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Controller_Adminhtml_Category extends Admin_Controller_Abstract {
	protected $_languageId = false;

	/**
	 * @return int
	 */
	protected function _getLanguageId(){
		return $this->_languageId===false ? ($this->_languageId = (int)Ddm_Request::get('language','int','0')) : $this->_languageId;
	}

	public function indexAction(){
		$categoryId = Ddm_Request::get('id','int');
		$category = new News_Model_Category();
		$category->setLanguageId($this->_getLanguageId());
		if($categoryId){
			if($category->load($categoryId)->getId()){
				Ddm::register('category',$category);
			}else{
				$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('news')->translate('分类')));
				Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
				return;
			}
		}

		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_category_edit'),'edit_category')
			->setTitle(Ddm::getTranslate('news')->translate('分类'))
			->setActiveMemu('news')
			->addWindowScript()
			->display();
	}

	public function addAction(){
		$this->indexAction();
	}

	public function editAction(){
		$this->indexAction();
	}

	public function saveAction(){
		$categoryId = (int)Ddm_Request::get('id');
		$languageId = (int)Ddm_Request::get('language');
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getUrl('*/*',array('language'=>$languageId));

		if($categoryId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('news')->translate('修改分类')));
		}else if(!$categoryId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('news')->translate('增加分类')));
		}else{
			$category = new News_Model_Category();
			$category->setLanguageId($languageId);
			if($categoryId){
				$category->load($categoryId);
				if(!$category->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('news')->translate('分类')));
					Ddm_Request::redirect(Ddm::getUrl('*/*',array('language'=>$languageId)));
					return;
				}
			}
			Ddm_Db::beginTransaction();
			try{
				$categoryData = Ddm_Request::post('category');
				$useDefault = Ddm_Request::post('use_default');
				if($useDefault && is_array($useDefault))$category->setUseDefaultAttribute($useDefault);
				$category->addData($categoryData)->save();

				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('news')->translate('分类')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getUrl('*/*/edit',array('id'=>$category->getId(),'language'=>$languageId)) : Ddm::getUrl('*/*',array('language'=>$languageId));
			}catch (Exception $ex) {
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}
		Ddm_Request::redirect($gotoUrl);
	}

	public function deleteAction(){
		if($id = Ddm_Request::get('id','int',NULL)){
			Ddm_Db::beginTransaction();
			try{
				$category = new News_Model_Category();
				$category->load((int)$id)->delete();
				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除'));
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
	}

	public function refreshUrlIndexAction(){
		$limit = (int)Ddm_Request::get('limit') or $limit = 2500;
		$from = (int)Ddm_Request::get('limit') or $from = 1;
		$urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('news_category','url_key');
		$maxId = (int)Ddm_Db::getReadConn()->fetchOne("SELECT MAX(entity_id) AS `max_id` FROM ".$urlKeyAttribute->getTable()." WHERE entity_id>0 AND attribute_id='".$urlKeyAttribute->getId()."'",true);
		$result = array('limit'=>$limit,'from'=>$from,'maxId'=>$maxId,'error'=>'','url'=>'');
		$urlIndex = new News_Model_Category_Urlindex();

		if($from<=$maxId){
			Ddm_Db::beginTransaction();
			try{
				$urlIndex->clearUrlIndex();
				$urlIndex->saveUrlIndexFromCategoryId($from,$from+$limit);
				Ddm_Db::commit();
				if($from+$limit+1<=$maxId){
					$result['url'] = Ddm::getUrl('*/*/refresh-url-index',array('limit'=>$limit,'from'=>$from+$limit+1));
				}
			}catch(Exception $e){
				$result['error'] = $e->getMessage();
				Ddm_Db::rollBack();
			}
		}else{
			$urlIndex->removeClearUrlIndexCache();
		}

		echo json_encode($result);
	}

	public function isAllowed($actionName){
		return $this->_isAllowed($actionName,'news/category');
	}
}
