<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Block_Adminhtml_News_Edit extends Admin_Block_Widget_Form {
	protected $_languageId = false;

	/**
	 * @return News_Block_Adminhtml_News_Edit
	 */
	public function init() {
		parent::init();
		$this->getNews() and $id = $this->getNews()->getId() or $id = false;
		$this->formAction = Ddm::getUrl('*/*/save',array('id'=>$id,'language'=>$this->getLanguageId()));
		$this->method = 'post';
		$this->formId = 'edit-news-form';
		$this->titleText = $id ? Ddm::getTranslate('news')->translate('修改新闻') : Ddm::getTranslate('news')->translate('增加新闻');
		$this->addButton('save_continue_edit',array(
			'label'=>Ddm::getTranslate('admin')->translate('保存并继续编辑'),
			'onclick'=>'saveAndContinueEdit(editNewsForm)',
			'class'=>'btn-primary')
		);
		if($id)$this->addLanguageSwitchBlock();
		return $this;
	}

	/**
	 * @return int
	 */
	public function getLanguageId(){
		return $this->_languageId===false ? ($this->_languageId = (int)Ddm_Request::get('language','int','0')) : $this->_languageId;
	}

	/**
	 * @return News_Model_News
	 */
	public function getNews(){
		return Ddm::registry('news');
	}

	protected function _prepareElements(){
		$news = $this->getNews();
		foreach(News_Model_News::singleton()->getAttributes() as $attribute){
			if($attribute->is_visible)$this->addElement($attribute->attribute_code,$this->_getElementConfig($attribute,$news));
		}

		return parent::_prepareElements();
	}

	/**
	 * @param array $attribute
	 * @param News_Model_News $news
	 * @return array
	 */
	protected function _getElementConfig($attribute,$news){
		$config = array(
			'label'=>Ddm::getTranslate('news')->translate($attribute->frontend_label),
			'id'=>'news-'.$attribute->attribute_code,
			'type'=>$attribute->frontend_type,
			'name'=>'news['.$attribute->attribute_code.']',
			'verify'=>$attribute->is_required ? 1 : NULL,
			'value'=>$news ? $news->{$attribute->attribute_code} : '',
			'notice'=>$attribute->note ? Ddm::getTranslate('news')->translate($attribute->note) : NULL,
			'style'=>$attribute->frontend_type=='text'||$attribute->frontend_type=='textarea' ? 'width:99%;' : NULL
		);
		if($attribute->frontend_type=='radio'){
			$config['list'] = $attribute->source_model ? call_user_func(array($attribute->source_model, 'singleton'))->getAllOptions(false,false) : array();
			$config['type'] = 'radiogroup';
		}else if($attribute->frontend_type=='select'){
			$config['list'] = $attribute->source_model ? call_user_func(array($attribute->source_model, 'singleton'))->getAllOptions(false,false) : array();
			$config['errormsg'] = Ddm::getTranslate('admin')->translate('请选择分类');
			if(!$this->getNews())$config['selected'] = array('value'=>'','label'=>Ddm::getTranslate('admin')->translate('请选择分类'));
		}
		if($attribute->attribute_code=='author'){
			unset($config['style']);
		}else if($attribute->attribute_code=='content'){
			$config['rows'] = '20';
			$config['align_top'] = true;
			$this->addValidateFormSuccessJs('if(typeof newsContent != "undefined"){if(newsContent.getData()==""){doane(event);window.alert("'.Ddm::getTranslate('news')->translate('新闻内容不能为空').'");newsContent.focus();}}');
		}
		if(!$attribute->is_global && $this->getLanguageId() && $news){
			isset($config['after_html']) or $config['after_html'] = '';
			$config['after_html'] .= ' &nbsp; <input type="checkbox" name="use_default[]" value="'.$attribute->attribute_code.'"';
			if($news->isUseDefaultValue($attribute->attribute_code)){
				$config['after_html'] .= ' checked="checked"';
				$config['disabled'] = 'disabled';
			}
			$config['after_html'] .= ' id="'.$config['id'].'-usedefault" onclick="setUseDefaultValue(this,\''.$config['id'].'\',\''.$config['type'].'\')" />';
			$config['after_html'] .= '<label for="'.$config['id'].'-usedefault">'.Ddm::getTranslate('core')->translate('使用默认值').'</label>';
		}
		return $config;
	}
}