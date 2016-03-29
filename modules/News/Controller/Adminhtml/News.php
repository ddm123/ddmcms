<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Controller_Adminhtml_News extends Admin_Controller_Abstract {
	protected $_languageId = false;

	/**
	 * @return int
	 */
	protected function _getLanguageId(){
		return $this->_languageId===false ? ($this->_languageId = (int)Ddm_Request::get('language','int','0')) : $this->_languageId;
	}

	public function indexAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_news_list'),'news_list')
			->setTitle(Ddm::getTranslate('news')->translate('新闻'))
			->setActiveMemu('news')
			->addWindowScript()
			->display();
	}

	public function addAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_news_edit'),'add_news')
			->setTitle(Ddm::getTranslate('news')->translate('增加新闻'))
			->setActiveMemu('news')
			->display();
	}

	public function editAction(){
		$newsId = Ddm_Request::get('id','int');
		$news = new News_Model_News();
		$news->setLanguageId($this->_getLanguageId());
		if(!$newsId || !$news->load($newsId)->getId()){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('news')->translate('新闻')));
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
			return;
		}
		Ddm::register('news',$news);

		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_news_edit'),'edit_news')
			->setTitle(Ddm::getTranslate('news')->translate('修改新闻'))
			->setActiveMemu('news')
			->display();
	}

	public function saveAction(){
		$newsId = (int)Ddm_Request::get('id');
		$newsData = Ddm_Request::post('news');
		$useDefault = Ddm_Request::post('use_default');
		$languageId = (int)Ddm_Request::get('language');
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getUrl('*/*',array('language'=>$languageId));

		if($newsId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('news')->translate('修改新闻')));
		}else if(!$newsId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('news')->translate('增加新闻')));
		}else{
			$news = new News_Model_News();
			$news->setLanguageId($languageId);
			if($newsId){
				$news->load($newsId);
				if(!$news->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('news')->translate('新闻')));
					Ddm_Request::redirect(Ddm::getUrl('*/*'));
					return;
				}
			}

			Ddm_Db::beginTransaction();
			try{
				if($useDefault && is_array($useDefault))$news->setUseDefaultAttribute($useDefault);
				$news->addData($newsData)->save();

				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('news')->translate('新闻')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getUrl('*/*/edit',array('id'=>$news->getId(),'language'=>$languageId)) : Ddm::getUrl('*/*',array('language'=>$languageId));
			}catch (Exception $ex) {
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}
		Ddm_Request::redirect($gotoUrl);
	}

	public function saveFieldValueAction(){
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		$value = trim(Ddm_Request::post('v',false,''));
		$success = 'true';
		$message = '';

		$news = new News_Model_News();
		$news->setLanguageId($this->_getLanguageId());
		if($this->isAllowed('edit') && $id && $news->load($id)->getId()==$id){
			Ddm_Db::beginTransaction();
			try{
				if($field=='views')$value = (int)$value;
				$news->setData($field,$value)->save();
				$success = 'true';
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$success = 'false';
				$message = $ex->getMessage();
			}
		}else{
			$success = 'false';
			$message = Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('news')->translate('修改新闻'));
		}
		echo '{"success":'.$success.',"value":"'.addslashes($value).'","message":"'.addslashes($message).'"}';
	}

	public function saveCategoryAction(){
		if($ids = Ddm_Request::post('ids','int',NULL)){
			if(!$this->isAllowed('edit')){
				$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('news')->translate('修改新闻')));
			}else if($categoryId = (int)Ddm_Request::post('news_category')){
				Ddm_Db::beginTransaction();
				try{
					$news = new News_Model_News();
					$news->getResource()->changeCategory($categoryId,$ids);
					Ddm_Db::commit();
					$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('news')->translate('新闻')));
				}catch (Exception $ex){
					Ddm_Db::rollBack();
					$this->getNotice()->addError($ex->getMessage());
				}
			}else{
				$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('请选择分类'));
			}
		}else{
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
	}

	public function deleteAction(){
		if($ids = Ddm_Request::post('ids','int',NULL)){
			Ddm_Db::beginTransaction();
			try{
				$i = 0;
				foreach($ids as $id){
					$news = new News_Model_News();
					$news->setData('news_id',(int)$id)->delete();
					$i++;
				}
				if($i)$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除选择的记录'));
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}else{
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
	}

	public function refreshUrlIndexAction(){
		$limit = (int)Ddm_Request::get('limit') or $limit = 2500;
		$from = (int)Ddm_Request::get('limit') or $from = 1;
		$urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('news','url_key');
		$maxId = (int)Ddm_Db::getReadConn()->fetchOne("SELECT MAX(entity_id) AS `max_id` FROM ".$urlKeyAttribute->getTable()." WHERE entity_id>0 AND attribute_id='".$urlKeyAttribute->getId()."'",true);
		$result = array('limit'=>$limit,'from'=>$from,'maxId'=>$maxId,'error'=>'','url'=>'');
		$urlIndex = new News_Model_News_Urlindex();

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
		return $this->_isAllowed($actionName,'news/news');
	}
}
