<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Widget_Form extends Core_Block_Abstract {
	protected $_buttons = array();
	protected $_hasFile = false;
	protected $_elements = array();
	protected $_hiddenElements = array();
	//protected $_elementsType = array('text','select','radio','checkbox','textarea');
	protected $_js = '';
	protected $_validateFormSuccess = '';
	protected $_excludeProperties = array('type'=>true,'label'=>true,'align_top'=>true,'before_html'=>true,'after_html'=>true,'notice'=>true);
	private $_selectBoxIdIndex = 0;
	private $_hasEditor = false;

	public $titleText = NULL;
	public $titleRightHtml = '';
	public $formAction = '';
	public $method = 'get';
	public $formId = 'edit-form';

	/**
	 * @return Admin_Block_Widget_Form
	 */
	public function init() {
		parent::init();
		$this->titleText = Ddm::getTranslate('core')->translate('无标题');
		$this->template = 'widget/form.phtml';
		$this->addButton('back',array('label'=>Ddm::getTranslate('core')->translate('返回'),'onclick'=>'window.location=\''.Ddm::getLanguage()->getUrl('*/*/index').'\'','icon'=>'icon-arrow-left'))
			->addButton('save',array('label'=>Ddm::getTranslate('admin')->translate('保存'),'type'=>'submit','class'=>'btn-primary'));
		return $this;
	}

	/**
	 * @param string $id
	 * @param array $property
	 * @return Admin_Block_Widget_Form
	 */
	public function addButton($id,array $property){
		$this->_buttons[$id] = $property;
		return $this;
	}

	/**
	 * @param string $id
	 * @param array $property
	 * @return Admin_Block_Widget_Form
	 */
	public function updateButton($id,array $property){
		if(isset($this->_buttons[$id])){
			foreach($property as $key=>$value){
				if($value===NULL)unset($this->_buttons[$id][$key]);
				else $this->_buttons[$id][$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * @param string $id
	 * @return array
	 */
	public function getButton($id = NULL){
		if($id===NULL)return $this->_buttons;
		return isset($this->_buttons[$id]) ? $this->_buttons[$id] : NULL;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function getButtonHtml($id){
		$html = '';
		if(isset($this->_buttons[$id])){
			$html = '<button type="'.(isset($this->_buttons[$id]['type']) ? $this->_buttons[$id]['type'] : 'button').'" class="btn';
			isset($this->_buttons[$id]['class']) and $html .= ' '.$this->_buttons[$id]['class'];
			$html .= '"';
			isset($this->_buttons[$id]['onclick']) and $html .= ' onclick="'.$this->_buttons[$id]['onclick'].'"';
			isset($this->_buttons[$id]['style']) and $html .= ' style="'.$this->_buttons[$id]['style'].'"';
			$html .= '>';
			isset($this->_buttons[$id]['icon']) and $html .= '<i class="icon '.$this->_buttons[$id]['icon'].'"></i> ';
			$html .= $this->_buttons[$id]['label'].'</button>';
		}
		return $html;
	}

	/**
	 * @param string $id
	 * @return Admin_Block_Widget_Form
	 */
	public function removeButton($id = NULL){
		if($id===NULL)$this->_buttons = array();
		else if(isset($this->_buttons[$id]))unset($this->_buttons[$id]);
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return Admin_Block_Widget_Form
	 */
	public function hasFile($flag = NULL){
		if($flag===NULL)return $this->_hasFile;
		$this->_hasFile = (bool)$flag;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasEditor(){
		return $this->_hasEditor && is_file(SITE_ROOT.'/ckeditor/ckeditor.js');
	}

	/**
	 * 显示语言切换Tab
	 *
	 * @param string $queryVarName URL参数变量名
	 * @return Admin_Block_Widget_Form
	 */
	public function addLanguageSwitchBlock($queryVarName = NULL){
		if(empty($queryVarName))$queryVarName = 'language';
		$this->addBlock($this->createBlock('language','adminhtml_switch')->setQueryVarName($queryVarName),'language_switch');
		return $this;
	}

	/**
	 * @param string $name
	 * @param array $property
	 * @return Admin_Block_Widget_Form
	 */
	public function addElement($name,array $property){
		isset($property['type']) or $property['type'] = 'text';
		if($property['type']=='editor' || !empty($property['use_editor']))$this->_hasEditor = true;
		if($property['type']=='file')$this->_hasFile = true;
		if(isset($this->_elements[$name])){
			$this->_elements[$name]['_elements'][] = $property;
		}else{
			$this->_elements[$name] = $property;
			$this->_elements[$name]['_elements'] = array(0=>$property);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $htmlId
	 * @return Admin_Block_Widget_Form
	 */
	public function addHiddenElement($name,$value = '',$htmlId = NULL){
		if(is_array($name))$this->_hiddenElements = $name;
		$this->_hiddenElements[] = array('name'=>$name,'value'=>$value,'html_id'=>$htmlId);
		return $this;
	}

	/**
	 * @param string $name
	 * @return Admin_Block_Widget_Form
	 */
	public function addExcludeProperty($name){
		$this->_excludeProperties[$name] = true;
		return $this;
	}

	/**
	 * @param string $name
	 * @param array $property
	 * @return Admin_Block_Widget_Form
	 */
	public function updateElement($name,array $property){
		if(isset($this->_elements[$name])){
			if(isset($property['type'])){
				if($property['type']=='editor' || !empty($property['use_editor']))$this->_hasEditor = true;
				if($property['type']=='file')$this->_hasFile = true;
			}
			foreach($property as $key=>$value){
				if($value===NULL)unset($this->_elements[$name][$key]);
				else $this->_elements[$name][$key] = $value;
				foreach($this->_elements[$name]['_elements'] as $k=>$element){
					if($value===NULL)unset($this->_elements[$name]['_elements'][$k][$key]);
					else $this->_elements[$name]['_elements'][$k][$key] = $value;
				}
			}
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	public function getElement($name = NULL){
		if($name===NULL)return $this->_elements;
		return isset($this->_elements[$name]) ? $this->_elements[$name] : NULL;
	}

	/**
	 * @return array
	 */
	public function getHiddenElements(){
		return $this->_hiddenElements;
	}

	/**
	 * @param array $element
	 * @return string
	 */
	public function getElementHtml(array $element){
		$html = '<tr><th'.(isset($element['align_top'])&&$element['align_top'] ? ' class="align-top"' : '').(!empty($element['width']) ? ' width="'.$element['width'].'"' : '').'>'.$element['label'];
		if(!empty($element['verify']) && empty($element['empty']))$html .= ' <span class="required">*</span>';
		$html .= '</th><td>';
		foreach($element['_elements'] as $_elements){
			empty($_elements['before_html']) or $html .= $_elements['before_html'];
			if(empty($_elements['type']))$_elements['type'] = 'text';
			switch($_elements['type']){
				case 'select':
					$html .= $this->getSelectHtml($_elements);
					break;
				case 'textarea':case 'editor':
					$html .= $this->getTextareaHtml($_elements);
					break;
				case 'inputlist': case 'checkbox': case 'radio':
					$html .= $this->getInputListHtml($_elements);
					break;
				case 'radiogroup':
					$html .= $this->getRadioGroupHtml($_elements);
					break;
				case 'label':
					$html .= $this->getLabelHtml($_elements);
					break;
				default:
					$html .= $this->getTextInputHtml($_elements);
			}
			empty($_elements['after_html']) or $html .= $_elements['after_html'];
			empty($_elements['notice']) or $html .= '<p class="notice"><i class="icon icon-hand-up"></i>'.$_elements['notice'].'</p>';
		}
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 * @param string $name
	 * @return Admin_Block_Widget_Form
	 */
	public function removeElement($name = NULL){
		if($name===NULL)$this->_elements = array();
		else if(isset($this->_elements[$name]))unset($this->_elements[$name]);
		return $this;
	}

	public function getTextInputHtml(array $data){
		$data['class'] = isset($data['class']) ? "{$data['type']} {$data['class']}" : $data['type'];
		if($data['type']!='text')$data['class'] = 'text '.$data['class'];
		if(!empty($data['verify']) && !isset($data['errormsg']))$data['errormsg'] = Ddm::getTranslate('core')->translate('这是必填项');
		return '<input type="'.$data['type'].'"'.$this->_getProperties($data).' />';
	}

	public function getTextareaHtml(array $data){
		$html = '';
		if($data['type']=='editor' && $this->hasEditor()){
			if(empty($data['id']))$data['id'] = preg_replace('/[^\w\-]/','-',$data['name']);
			unset($data['verify']);
			$jsvarname = preg_replace_callback('/[^A-Za-z]([A-Za-z])/',array($this,'getVarnameCallback'),$data['id']);
			$this->addJs('if(typeof CKEDITOR != "undefined"){var '.$jsvarname.' = CKEDITOR.replace("'.$data['id'].'",{"allowedContent":true});if(typeof CKFinder != "undefined"){CKFinder.setupCKEditor('.$jsvarname.',{"basePath":"'.Ddm::getCurrentBaseUrl().'ckeditor/ckfinder/"});}}');
		}else if(!empty($data['use_editor'])){
			if(empty($data['id']))$data['id'] = preg_replace('/[^\w\-]/','-',$data['name']);
			$jsvarname = preg_replace_callback('/[^A-Za-z]([A-Za-z])/',array($this,'getVarnameCallback'),$data['id']);
			$tp = new Core_Block_Template(false);
			$configJson = '{\'contentsCss\':[\''.Core_Block_Template::getTemplateFile('common.css','css','core',$tp,false).'\',\''.Core_Block_Template::getTemplateFile('style.css','css','core',$tp,false).'\']}';
			$this->addJs('var '.$jsvarname.' = null;DOMLoaded(function(){isDisplayEditor(document.getElementById("is_use_editor_'.$data['id'].'"),\''.$data['id'].'\',\''.$jsvarname.'\','.$configJson.');});');
			$html .= '<span style="display:block;line-height:100%;padding:0 0 4px 0;"><input type="checkbox" id="is_use_editor_'.$data['id'].'" value="1" onclick="isDisplayEditor(this,\''.$data['id'].'\',\''.$jsvarname.'\','.$configJson.')" /> <label for="is_use_editor_'.$data['id'].'">'.Ddm::getTranslate('admin')->translate('使用可视化编辑').'</label></span>';
		}
		$data['class'] = isset($data['class']) ? "{$data['type']} {$data['class']}" : $data['type'];
		if(!empty($data['verify']) && !isset($data['errormsg']))$data['errormsg'] = Ddm::getTranslate('core')->translate('这是必填项');
		isset($data['rows']) or $data['rows'] = '6';
		if(isset($data['value'])){
			$value = $data['value'];
			unset($data['value']);
		}else $value = '';
		$html .= '<textarea'.$this->_getProperties($data).'>';
		$value=='' or $html .= Ddm_String::singleton()->escapeHtml($value);
		$html .= '</textarea>';
		return $html;
	}

	public function getSelectHtml(array $data){
		$id = isset($data['id']) ? $data['id'] : 'form-select-box-'.$this->_selectBoxIdIndex;
		$html = '<span id="'.$id.'"';
		empty($data['class']) or $html .= ' class="'.$data['class'].'"';
		if(!empty($data['verify'])){
			$html .= ' verify="'.($data['verify']===true ? '1' : Ddm_String::singleton()->escapeHtml($data['verify'])).'"';
			unset($data['verify']);
		}
		if(!empty($data['errormsg'])){
			$html .= ' errormsg="'.Ddm_String::singleton()->escapeHtml($data['errormsg']).'"';
			unset($data['errormsg']);
		}else $html .= ' errormsg="'.Ddm_String::singleton()->escapeHtml(Ddm::getTranslate('core')->translate('这是必填项')).'"';
		$html .= '></span>';
		$js = $jsvarname = '';
		if(isset($data['jsvarname'])){
			$js .= $data['jsvarname'].' = ';
			unset($data['jsvarname']);
		}else{
			$jsvarname = preg_replace_callback('/[^A-Za-z]([A-Za-z])/',array($this,'getVarnameCallback'),$id);
			$js .= $jsvarname.' = ';
		}
		$js .= '(new combobox({"apply":"'.$id.'"';
		if(!isset($data['list']) && isset($data['data'])){
			$data['list'] = $data['data'];
			unset($data['data']);
		}
		if(!isset($data['selected']) && isset($data['value'])){
			$data['selected'] = $data['value'];
		}
		if(isset($data['value']))unset($data['value']);
		foreach($data as $key=>$value){
			if($key=='list')$js .= ",'data':".$this->_selectListToString($value);
			else if($key=='selected'){
				if(!is_array($value)){
					$value = (string)$value;
					$_v = NULL;
					foreach($data['list'] as $v){
						if((string)$v['value']===$value){
							$_v = $v;
							break;
						}
					}
					if($_v)$value = $_v;
					else if(isset($data['edit']) && $data['edit'])$value = array('value'=>$value,'label'=>$value);
				}
				if(is_array($value))$js .= ",'selected':['".str_replace(array("\r","\n","'"),array('\\r','\\n',"\\'"),$value['value'])."','".str_replace(array("\r","\n","'"),array('\\r','\\n',"\\'"),$value['label'])."']";
			}else if(is_bool($value))$js .= ",'$key':".($value ? 'true' : 'false');
			else if(is_numeric($value))$js .= ",'$key':$value";
			else if(!isset($this->_excludeProperties[$key])){
				$value = is_array($value) ? (isset($value['expr']) ? $value['expr'] : "'".current($value)."'") : "'$value'";
				$js .= ",'$key':$value";
			}
		}
		$js .= '})).init();';
		if($jsvarname)$this->addJs("var $jsvarname;");
		$this->addJs('DOMLoaded(function(){'.$js.'});');
		$this->_selectBoxIdIndex++;
		return $html;
	}

	public function getInputListHtml(array $data){
		if(isset($data['list'])){
			if(count($data['list'])>20){
				if(isset($data['style'])){
					if(stripos($data['style'],'height')===false)$data['style'] .= 'height:544px;';
				}else{
					$data['style'] = 'height:544px;';
				}
			}
			$html = '<ul class="input-group-list-box"'.$this->_getProperties($data,array('list'=>true,'name'=>true,'value'=>true,'id'=>true,'disabled'=>true,'checked'=>true,'values'=>true,'class'=>true,'verify'=>true,'errormsg'=>true)).'>';
			$i = 0;
			foreach($data['list'] as $_data){
				if(empty($_data['name']))$_data['name'] = $data['name'];
				if(isset($data['values']) && isset($_data['value']) && in_array($_data['value'],$data['values'])){
					$_data['checked'] = 'checked';
				}
				isset($_data['type']) or $_data['type'] = $data['type'];
				if(isset($data['class']) && !isset($_data['class']))$_data['class'] = $data['class'];
				if(!$i){
					if(isset($data['verify']) && !isset($_data['verify']))$_data['verify'] = $data['verify'];
					if(isset($data['errormsg']) && !isset($_data['errormsg']))$_data['errormsg'] = $data['errormsg'];
				}
				$html .= '<li'.($i%2 ? ' class="alternate-row"' : '').'>';
				empty($_data['before_html']) or $html .= $_data['before_html'];
				$html .= $this->getTextInputHtml($_data);
				if(!empty($_data['label']) && ($_data['type']=='checkbox' || $_data['type']=='radio'))$html .= ' <label'.(empty($_data['id']) ? '' : ' for="'.$_data['id'].'"').'>'.$_data['label'].'</label>';
				empty($_data['after_html']) or $html .= $_data['after_html'];
				$html .= '</li>';
				$i++;
			}
			return $html.'</ul>';
		}
		return $this->getTextInputHtml($data);
	}

	public function getRadioGroupHtml(array $data){
		if(isset($data['list'])){
			$id = isset($data['id']) ? $data['id'] : 'radio-group-list-box'.mt_rand(1000,9999);
			$jsvarname = preg_replace_callback('/[^A-Za-z]([A-Za-z])/',array($this,'getVarnameCallback'),$id);
			$this->addJs("var $jsvarname;");
			$this->addJs('DOMLoaded(function(){'.$jsvarname.' = (new RadioGroup("'.$id.'")).init();'.(empty($data['disabled']) ? '' : "$jsvarname.disable(true);").'});');
			$html = '';
			empty($data['before_html']) or $html .= $data['before_html'];
			if(!empty($data['verify']))$data['errormsg'] = Ddm::getTranslate('core')->translate('这是必填项');
			$html .= '<span id="'.$id.'" class="radio-group-list-box"'.$this->_getProperties($data,array('list'=>true,'name'=>true,'value'=>true,'id'=>true,'disabled'=>true,'checked'=>true)).'>';
			$i = 0;
			foreach($data['list'] as $_key=>$_data){
				is_array($_data) or $_data = array('value'=>$_key,'label'=>$_data);
				if(empty($_data['name']))$_data['name'] = $data['name'];
				if(empty($_data['id']))$_data['id'] = "{$_data['name']}-$i";
				if(!isset($_data['checked']) && isset($data['value']) && isset($_data['value']) && (string)$data['value']===(string)$_data['value']){
					$_data['checked'] = 'checked';
				}
				$_data['type'] = 'radio';
				if(!$i){
					if(isset($data['verify']) && !isset($_data['verify']))$_data['verify'] = $data['verify'];
					if(isset($data['errormsg']) && !isset($_data['errormsg']))$_data['errormsg'] = $data['errormsg'];
				}
				$html .= $this->getTextInputHtml($_data);
				if(empty($_data['label']))$_data['label'] = $_data['name'];
				$html .= '<label for="'.$_data['id'].'"'.(!$i ? ' class="first"' : '').' radio_id="'.$_data['id'].'">'.$_data['label'].'</label>';
				$i++;
			}
			$html .= '</span>';
			return $html;
		}
		return $this->getTextInputHtml($data);
	}

	public function getLabelHtml(array $data){
		return empty($data['html']) ? Ddm_String::singleton()->escapeHtml($data['value']) : $data['html'];
	}

	/**
	 * @param string $javascrip
	 * @return Admin_Block_Widget_Form
	 */
	public function addJs($javascrip){
		$this->_js .= "\r\n".$javascrip;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getJs(){
		return $this->_js;
	}

	/**
	 * 表单通过JS验证成功后, 将会执行的Javascript
	 * @param string $javascript
	 * @return Admin_Block_Widget_Form
	 */
	public function addValidateFormSuccessJs($javascript){
		$this->_validateFormSuccess .= "\r\n".$javascript;
		return $this;
	}

	/**
	 * 表单通过JS验证成功后, 将会执行的Javascript
	 * @return string
	 */
	public function getValidateFormSuccessJs(){
		return $this->_validateFormSuccess;
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	public function getVarnameCallback($matches){
		return strtoupper($matches[1]);
	}

	/**
	 * @return Admin_Block_Widget_Form
	 */
	protected function _prepareElements(){
		return $this;
	}

	/**
	 * @param array $data
	 * @param array $additionalExcludeProperties
	 * @return string
	 */
	protected function _getProperties(array $data,array $additionalExcludeProperties = array()){
		$html = '';
		foreach($data as $key=>$value){
			if($value===NULL || $value===false || isset($this->_excludeProperties[$key]) || isset($additionalExcludeProperties[$key]))continue;
			is_numeric($value) or $value = Ddm_String::singleton()->escapeHtml($value);
			$html .= " $key=\"$value\"";
		}
		return $html;
	}

	/**
	 * @param array $list
	 * @return string
	 */
	private function _selectListToString(array $list){
		$string = '';
		foreach($list as $value){
			$string=='' or $string .= ',';
			$v = str_replace(array("\r","\n","'"),array('\\r','\\n',"\\'"),$value['value']);
			$string .= "['$v','".(isset($value['label']) ? str_replace(array("\r","\n","'"),array('\\r','\\n',"\\'"),$value['label']) : $v)."'";
			isset($value['disabled']) and $string .= $value['disabled'] ? ',true' : ',false';
			isset($value['base_label']) and $string .= ",'".str_replace(array("\r","\n","'"),array('\\r','\\n',"\\'"),$value['base_label'])."'";
			$string .= ']';
		}
		return "[{$string}]";
	}

	protected function _beforeToHtml() {
		$this->_prepareElements();
		return parent::_beforeToHtml();
	}
}