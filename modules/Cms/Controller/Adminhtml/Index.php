<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Controller_Adminhtml_Index extends Admin_Controller_Abstract {
	protected $_languageId = false;

	/**
	 * @return int
	 */
	protected function _getLanguageId(){
		return $this->_languageId===false ? ($this->_languageId = (int)Ddm_Request::get('language','int','0')) : $this->_languageId;
	}

	public function indexAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_onepage_list'),'onepage_list')
			->setTitle(Ddm::getTranslate('cms')->translate('单页面'))
			->setActiveMemu('cms')
			->addWindowScript()
			->display();
	}

	public function addAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_onepage_edit'),'add_onepage')
			->setTitle(Ddm::getTranslate('cms')->translate('增加单页面'))
			->setActiveMemu('cms')
			->display();
	}

	public function editAction(){
		$onepageId = Ddm_Request::get('id','int');
		$onepage = new Cms_Model_Onepage();
		$onepage->setLanguageId($this->_getLanguageId());
		if(!$onepageId || !$onepage->load($onepageId)->getId()){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('cms')->translate('单页面')));
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
			return;
		}
		Ddm::register('onepage',$onepage);

		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_onepage_edit'),'edit_onepage')
			->setTitle(Ddm::getTranslate('cms')->translate('修改单页面'))
			->setActiveMemu('cms')
			->display();
	}

	public function getFieldValueAction(){
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		if($id && ($value = Cms_Model_Onepage::singleton()->setId($id)->getAttributeValue($field,$this->_getLanguageId()))){
			echo $value;
		}else{
			echo trim(Ddm_Request::post('v',false,''));
		}
	}

	public function saveFieldValueAction(){
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		$value = trim(Ddm_Request::post('v',false,''));
		$success = 'true';
		$message = '';

		$onepage = new Cms_Model_Onepage();
		$onepage->setLanguageId($this->_getLanguageId());
		if($this->isAllowed('edit') && $id && $onepage->load($id)->getId()==$id){
			Ddm_Db::beginTransaction();
			try{
				if($field=='is_enabled'){
					$value = (int)$value;
				}
				$onepage->setData($field,$value)->save();
				$success = 'true';
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$success = 'false';
				$message = $ex->getMessage();
			}
		}else{
			$success = 'false';
			$message = Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('cms')->translate('修改单页面'));
		}
		if($field=='url_key'){
			$value = '<a href="'.Ddm::getLanguage($this->_getLanguageId())->getBaseUrl().$value.'" target="_brank">'.$value.' ^</a>';
		}
		echo '{"success":'.$success.',"value":"'.addslashes($value).'","message":"'.addslashes($message).'"}';
	}

	public function activeAction(){
		if($ids = Ddm_Request::post('ids','int',NULL)){
			if($this->isAllowed('edit')){
				Ddm_Db::beginTransaction();
				try{
					$value = (int)Ddm_Request::get('value');
					$isEnabledAttribute = Ddm::getHelper('core')->getEntityAttribute('onepage','is_enabled');
					if($isEnabledAttribute){
						if($this->_getLanguageId()){
							Ddm_Db::getWriteConn()->save($isEnabledAttribute->getTable(),array('value'=>$value,'is_use_default'=>0),Ddm_Db_Interface::SAVE_UPDATE,array('entity_id'=>$ids,'attribute_id'=>$isEnabledAttribute->getId(),'language_id'=>$this->_getLanguageId()));
						}else{
							Ddm_Db::getWriteConn()->save($isEnabledAttribute->getTable(),array('value'=>$value),Ddm_Db_Interface::SAVE_UPDATE,array('entity_id'=>$ids,'attribute_id'=>$isEnabledAttribute->getId(),0=>new Ddm_Db_Expression("(language_id='0' OR is_use_default='1')")));
						}
						foreach($ids as $_id)Ddm::getHelper('Cms')->removeOnepageCache($_id);//刷新缓存
						$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('cms')->translate('单页面')));
					}else{
						throw new Exception(Ddm::getTranslate('core')->___('%s 属性不存在',"'is_enabled'"));
					}

					Ddm_Db::commit();
				}catch(Exception $ex){
					Ddm_Db::rollBack();
					$this->getNotice()->addError($ex->getMessage());
				}
			}else{
				$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('admin')->translate('修改管理员')));
			}
		}else{
			$this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
		}
		Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
	}

	public function saveAction(){
		$onepageId = (int)Ddm_Request::get('id');
		$languageId = (int)Ddm_Request::get('language');
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getUrl('*/*');

		if($onepageId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('cms')->translate('修改单页面')));
		}else if(!$onepageId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('cms')->translate('增加单页面')));
		}else{
			$onepage = new Cms_Model_Onepage();
			$onepage->setLanguageId($languageId);
			if($onepageId){
				$onepage->load($onepageId);
				if(!$onepage->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('cms')->translate('单页面')));
					Ddm_Request::redirect(Ddm::getUrl('*/*'));
					return;
				}
			}
			Ddm_Db::beginTransaction();
			try{
				$onepageData = Ddm_Request::post('onepage');
				$useDefault = Ddm_Request::post('use_default');
				if($useDefault && is_array($useDefault))$onepage->setUseDefaultAttribute($useDefault);
				$onepage->addData($onepageData)->save();

				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('cms')->translate('单页面')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getUrl('*/*/edit',array('id'=>$onepage->getId(),'language'=>$languageId)) : Ddm::getUrl('*/*');
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
					$onepage = new Cms_Model_Onepage();
					$onepage->setData('onepage_id',(int)$id)->delete();
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
		return $this->_isAllowed($actionName,'cms/onepage');
	}
}
