<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Language_Controller_Adminhtml_Index extends Admin_Controller_Abstract {
	public function indexAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_list'),'language_list')
			->setTitle(Ddm::getTranslate('language')->translate('网站语言'))
			->setActiveMemu('system')
			->addWindowScript()
			->display();
	}

	public function addAction(){
		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_edit'),'add_language')
			->setTitle(Ddm::getTranslate('language')->translate('增加网站语言'))
			->setActiveMemu('system')
			->display();
	}

	public function editAction(){
		$languageId = Ddm_Request::get('id','int');
		$language = new Language_Model_Language();
		if(!$languageId || !$language->load($languageId)->getId()){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('language')->translate('网站语言')));
			Ddm_Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
			return;
		}
		Ddm::register('language',$language);

		$this->_getTemplate()
			->addBlock($this->_createBlock('adminhtml_edit'),'edit_language')
			->setTitle(Ddm::getTranslate('language')->translate('修改网站语言'))
			->setActiveMemu('system')
			->display();
	}

	public function checkCodeAction(){
		$output = '1';
		$languageId = (int)Ddm_Request::get('id');
		if($languageCode = Ddm_Request::get('code','/^\w+$/','')){
			if(is_numeric($languageCode)){
				$output = Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字');
			}else{
				$output = $this->_codeIsExist($languageCode,$languageId) ? Ddm::getTranslate('language')->translate('你填写的语言代码已经存在') : '0';
			}
		}
		echo $output;
	}

	public function saveFieldValueAction(){
		$fields = array(
			'language_code'=>Ddm::getTranslate('language')->translate('语言代码'),
			'language_name'=>Ddm::getTranslate('core')->translate('名称'),
			'is_enable'=>Ddm::getTranslate('core')->translate('启用'),
			'position'=>Ddm::getTranslate('core')->translate('位置')
		);
		$field = Ddm_Request::post('f');
		$id = (int)Ddm_Request::post('id');
		$value = trim(Ddm_Request::post('v',false,''));
		$success = 'true';
		$message = '';

		$language = new Language_Model_Language();
		if($this->isAllowed('edit') && $id && $language->load($id)->getId()==$id){
			Ddm_Db::beginTransaction();
			try{
				if($field=='language_code' || $field=='language_name'){
					if($value==''){
						$success = 'false';
						$message = Ddm::getTranslate('admin')->___('%s不能为空',$fields[$field]);
					}else{
						if($field=='language_code' && (is_numeric($value) || !preg_match('/^\w+$/',$value))){
							$success = 'false';
							$message = Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字');
						}else if($field=='language_code' && $this->_codeIsExist($value,$id)){
							$success = 'false';
							$message = Ddm::getTranslate('language')->translate('你填写的语言代码已经存在');
						}else{
							$language->setData($field,$value)->save();
							$success = 'true';
						}
					}
				}else if(isset($fields[$field])){
					$value = (int)$value;
					$language->setData($field,$value)->save();
					$success = 'true';
				}
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$success = 'false';
				$message = $ex->getMessage();
			}
		}else{
			$success = 'false';
			$message = Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('language')->translate('修改网站语言'));
		}
		echo '{"success":'.$success.',"value":"'.addslashes($value).'","message":"'.addslashes($message).'"}';
	}

	public function saveAction(){
		$languageId = (int)Ddm_Request::get('id');
		$languageCode = trim(Ddm_Request::post('code',false,''));
		$languageName = trim(Ddm_Request::post('language_name',false,''));
		$gotoUrl = Ddm_Request::server()->HTTP_REFERER ? Ddm_Request::server()->HTTP_REFERER : Ddm::getUrl('*/*');

		if($languageCode==''){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('language')->translate('语言代码')));
		}else if($languageName==''){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空',Ddm::getTranslate('core')->translate('名称')));
		}else if(is_numeric($languageCode) || !preg_match('/^\w+$/',$languageCode)){
			$this->getNotice()->addError(Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字'));
		}else if($this->_codeIsExist($languageCode,$languageId)){
			$this->getNotice()->addError(Ddm::getTranslate('language')->translate('你填写的语言代码已经存在'));
		}else if($languageId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_EDIT)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('language')->translate('修改网站语言')));
		}else if(!$languageId && !$this->isAllowed(Admin_Model_Group::ALLOW_TYPE_ADD)){
			$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('language')->translate('增加网站语言')));
		}else{
			$language = new Language_Model_Language();
			if($languageId){
				$language->load($languageId);
				if(!$language->getId()){
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('language')->translate('网站语言')));
					Ddm_Request::redirect(Ddm::getUrl('*/*'));
					return;
				}
			}
			Ddm_Db::beginTransaction();
			try{
				$language->addData(array(
					'language_code'=>$languageCode,
					'language_name'=>$languageName,
					'is_enable'=>(int)Ddm_Request::post('enable'),
					'position'=>(int)Ddm_Request::post('position')
				))->save();

				Ddm_Db::commit();

				$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功',Ddm::getTranslate('language')->translate('网站语言')));
				$gotoUrl = Ddm_Request::get('back')=='edit' ? Ddm::getUrl('*/*/edit',array('id'=>$language->getId())) : Ddm::getUrl('*/*');
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
				$language = new Language_Model_Language();
				$language->load($id);
				if($language->getId()){
					$language->delete();
					$this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除'));
				}
				Ddm_Db::commit();
			}catch(Exception $ex){
				Ddm_Db::rollBack();
				$this->getNotice()->addError($ex->getMessage());
			}
		}
		Ddm_Request::redirect(Ddm::getUrl('*/*'));
	}

	public function saveTranslateAction(){
		if($languageId = (int)Ddm_Request::get('id')){
			if($this->isAllowed('translate')){
				$language = new Language_Model_Language();
				$language->load($languageId);
				if($language->getId()==$languageId){
					echo 'Save success';
					return;
				}else{
					$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在',Ddm::getTranslate('language')->translate('网站语言')));
				}
			}else{
				$this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限',Ddm::getTranslate('language')->translate('修改语言翻译')));
			}
		}
		Ddm_Request::redirect(Ddm::getUrl('*/*'));
	}

	/**
	 * @param string $languageCode
	 * @param int $languageId
	 * @return bool
	 */
	protected function _codeIsExist($languageCode,$languageId = NULL){
		$result = false;
		$defaultLanguage = Ddm::getLanguage()->getDefaultLanguage(false);
		if(strtolower($defaultLanguage['language_code'])==strtolower($languageCode)){
			$result = true;
		}else{
			$where = array('language_code'=>$languageCode);
			if($languageId)$where['language_id'] = array('<>'=>$languageId);
			$result = (bool)Ddm_Db::getReadConn()->getSelect()
				->from(Ddm_Db::getTable('language'),'language_id')
				->where($where)->limit(1)
				->fetchOne(true);
		}
		return $result;
	}

	public function isAllowed($actionName){
		return $this->_isAllowed($actionName,'language/language');
	}
}
