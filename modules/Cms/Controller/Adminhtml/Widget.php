<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Controller_Adminhtml_Widget extends Admin_Controller_Abstract {
	protected $_languageId = false;

	/**
	 * @return int
	 */
	protected function _getLanguageId(){
		return $this->_languageId===false ? ($this->_languageId = (int)Ddm_Request::get('language','int','0')) : $this->_languageId;
	}

	public function indexAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_widget_list'),'widget_list')
			->setTitle(Ddm::getTranslate('cms')->translate('自定义块'))
			->setActiveMemu('cms')
			->addWindowScript()
			->display();
	}

	public function addAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_widget_edit'),'add_widget')
			->setTitle(Ddm::getTranslate('cms')->translate('增加自定义块'))
			->setActiveMemu('cms')
			->display();
	}

	public function editAction(){
		$widgetId = Ddm_Request::get('id','int');
		$widget = new Cms_Model_Widget();
		$widget->setLanguageId($this->_getLanguageId());
		if(!$widgetId || !$widget->load($widgetId)->getId()){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('cms')->translate('自定义块')));
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
			return;
		}
		Ddm::register('widget',$widget);

		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_widget_edit'),'edit_widget')
			->setTitle(Ddm::getTranslate('cms')->translate('修改自定义块'))
			->setActiveMemu('cms')
			->display();
	}

	public function checkIdentifierAction(){
		$identifier = trim(Ddm_Request::get('identifier',false,''));
		$id = (int)Ddm_Request::get('id');
		$result = array('error'=>0,'message'=>'');
		if($identifier){
			if(preg_match('/^[\w\-\.\/]+$/',$identifier)){
				$expression = array('identifier'=>$identifier);
				if($id)$expression['widget_id'] = array('<>'=>$id);
				$exists = Ddm_Db::getReadConn()->count(Ddm_Db::getTable('widget'),$expression);
				if($exists){
					$result['error'] = 1;
					$result['message'] = Ddm::getTranslate('cms')->translate('您填写的标识符已经存在');
				}
			}else{
				$result['error'] = 1;
				$result['message'] = Ddm::getTranslate('cms')->translate('仅允许英文字母、数字、下划线或减号组合');
			}
		}
		echo '{"error":'.$result['error'].',"message":"'.addslashes($result['message']).'"}';
	}

	public function saveAction(){
		$widgetId = (int)Ddm_Request::get('id');
		$widgetData = Ddm_Request::post('widget');
		$useDefault = Ddm_Request::post('use_default');
		$languageId = (int)Ddm_Request::get('language');
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getUrl('*/*');
		$widgetData['identifier'] = isset($widgetData['identifier']) ? trim($widgetData['identifier']) : '';

		if($widgetId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('cms')->translate('修改自定义块')));
		}else if(!$widgetId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('cms')->translate('增加自定义块')));
		}else if($widgetData['identifier']==''){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('cms')->translate('标识符')));
		}else{
			$widget = new Cms_Model_Widget();
			$widget->setLanguageId($languageId);
			if($widgetId){
				$widget->load($widgetId);
				if(!$widget->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('cms')->translate('自定义块')));
					Ddm_Request::redirect(Ddm::getUrl('*/*'));
					return;
				}
			}
			$exists = Ddm_Db::getReadConn()->count($widget->getResource()->getMainTable(),array('identifier'=>$widgetData['identifier'],'widget_id'=>$widgetId ? array('<>'=>$widgetId) : array('>'=>0)));
			if($exists){
				$this->getNotice()->addError(Ddm::getTranslate('cms')->translate('您填写的标识符已经存在'));
				Ddm_Request::redirect($widgetId ? Ddm::getLanguage()->getUrl('*/*/edit',array('id'=>$widgetId,'language'=>$languageId)) : Ddm::getLanguage()->getUrl('*/*/add'));
				return;
			}
			Ddm_Db::beginTransaction();
			try{
				if($useDefault && is_array($useDefault))$widget->setUseDefaultAttribute($useDefault);
				$widget->addData($widgetData)->save();

				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('cms')->translate('自定义块')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getUrl('*/*/edit',array('id'=>$widget->getId(),'language'=>$languageId)) : Ddm::getUrl('*/*');
			}catch (Exception $ex) {
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}
		Ddm_Request::redirect($gotoUrl);
	}

	public function deleteAction(){
		if($ids = Ddm_Request::post('ids','int',NULL)){
			Ddm_Db::beginTransaction();
			try{
				$i = 0;
				foreach($ids as $id){
					$widget = new Cms_Model_Widget();
					$widget->setData('widget_id',(int)$id)->delete();
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

	public function isAllowed($actionName){
		return $this->_isAllowed($actionName,'cms/widget');
	}
}
