<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Block_Abstract extends Ddm_Object {
	protected $_cacheId = NULL;
	protected $_cacheTags = array('HTML_BLOCK');
	protected $_cacheLifetime = 0;
	protected $_blocks = array();
	protected $_parentBlock = NULL;
	protected $_moduleName = NULL;
	protected $_templateObject = NULL;
	protected $_isFetch = true;
	protected $_html = '';
	public $template = '';

	/**
	 * @return Core_Block_Abstract
	 */
	public function init(){
		return $this;
	}

	/**
	 * @param string $name
	 * @return Core_Block_Abstract
	 */
	public function setModuleName($name){
		$this->_moduleName = ucfirst($name);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModuleName(){
		if($this->_moduleName===NULL){
			$a = explode('_',get_class($this),2);
			$this->_moduleName = $a[0];
		}
		return $this->_moduleName;
	}

	/**
	 * @param Core_Block_Abstract $block
	 * @return Core_Block_Abstract
	 */
	public function setParentBlock(Core_Block_Abstract $block){
		$this->_parentBlock = $block;
		return $this;
	}

	/**
	 * @return Core_Block_Abstract
	 */
	public function getParentBlock(){
		return $this->_parentBlock;
	}

	/**
	 * 设置/获取该Block是否给Core_Block_Template::fetch()方法输出
	 * @param bool $flag
	 * @return Core_Block_Abstract
	 */
	public function isFetch($flag = 'get'){
		if($flag=='get')return $this->_isFetch;
		$this->_isFetch = $flag;
		return $this;
	}

	/**
	 * @param string $template
	 * @return Core_Block_Abstract
	 */
	public function setTemplate($template){
		$template===NULL or $this->template = $template;
		return $this;
	}

	/**
	 * @param Core_Block_Template $templateObject
	 * @return Core_Block_Abstract
	 */
	public function setTemplateObject(Core_Block_Template $templateObject){
		$this->_templateObject = $templateObject;
		return $this;
	}

	/**
	 * @return Core_Block_Template
	 */
	public function getTemplateObject(){
		return $this->_templateObject===NULL ? Core_Block_Template::singleton() : $this->_templateObject;
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return string
	 */
	public function getTemplateFile($file = NULL,$moduleName = NULL){
		return Core_Block_Template::getTemplateFile($file ? $file : $this->template,'template',$moduleName ? $moduleName : $this->_moduleName,$this->getTemplateObject());
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return string
	 */
	public function getImageUrl($file,$moduleName = NULL){
		return Core_Block_Template::getTemplateFile($file,'images',$moduleName ? $moduleName : $this->_moduleName,$this->getTemplateObject());
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return string
	 */
	public function getJsUrl($file,$moduleName = NULL){
		return Core_Block_Template::getTemplateFile($file,'js',$moduleName ? $moduleName : $this->_moduleName,$this->getTemplateObject());
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return string
	 */
	public function getCssUrl($file,$moduleName = NULL){
		return Core_Block_Template::getTemplateFile($file,'css',$moduleName ? $moduleName : $this->_moduleName,$this->getTemplateObject());

	}

	/**
	 * @param string $file
	 * @return string
	 */
	public function getUploadsUrl($file){
		return Ddm::getLanguage()->getBaseUrl('image')."data/uploads/$file";
	}

	/**
	 * @param string $path
	 * @param int $languageId
	 * @return string|null
	 */
	public function getConfig($path,$languageId = NULL){
		return $this->getTemplateObject()->getConfig($path,$languageId);
	}

	/**
	 * @param string $type
	 * @return string
	 */
	public function getBaseUrl($type = 'link'){
		return $this->getTemplateObject()->getBaseUrl($type);
	}

	/**
	 * @param string $moduleName
	 * @param string $blockClass
	 * @param string $templateName
	 * @return Core_Block_Abstract
	 */
	public function createBlock($moduleName,$blockClass,$templateName = NULL){
		return $this->getTemplateObject()->createBlock($moduleName,$blockClass,$templateName);
	}

	/**
	 * @param Core_Block_Abstract $block
	 * @param string $name
	 * @param bool $graceful
	 * @return Core_Block_Abstract
	 */
	public function addBlock(Core_Block_Abstract $block,$name,$graceful = false){
		if(isset($this->_blocks[$name])){
			if($graceful==false){
				throw new Exception($name.' block name already exists');
			}
		}else{
			$this->_blocks[$name] = $block->setTemplateObject($this->getTemplateObject())->setParentBlock($this);
		}
		return $this;
	}

	/**
	 * 和$this->addBlock()的区别是该方法会插入到$this->_blocks数组的前面, 而不是未尾
	 * @param Core_Block_Abstract $block
	 * @param string $name
	 * @param bool $graceful
	 * @return Core_Block_Abstract
	 */
	public function addBlockToFirst(Core_Block_Abstract $block,$name,$graceful = false){
		if(isset($this->_blocks[$name])){
			if($graceful==false){
				throw new Exception($name.' block name already exists');
			}
		}else{
			$this->_blocks = array_merge(array($name=>$block->setTemplateObject($this->getTemplateObject())),$this->_blocks);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return Core_Block_Abstract
	 */
	public function removeBlock($name){
		if(isset($this->_blocks[$name]))unset($this->_blocks[$name]);
		return $this;
	}

	/**
	 * @param string $name
	 * @return Core_Block_Abstract|array|false
	 */
	public function getBlock($name = NULL){
		return $name===NULL ? $this->_blocks : (isset($this->_blocks[$name]) ? $this->_blocks[$name] : false);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getBlockHtml($name){
		return isset($this->_blocks[$name]) ? $this->_blocks[$name]->toHtml() : '';
	}

	/**
	 * @param string $identifier
	 * @return Cms_Model_Widget
	 */
	public function getWidget($identifier){
		return $this->getTemplateObject()->getWidget($identifier);
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	public function getWidgetHtml($identifier){
		return $this->getTemplateObject()->getWidgetHtml($identifier);
	}

	/**
	 * 获取单页面的链接地址
	 * @param string $url
	 * @return string
	 */
	public function getOnepageUrl($url){
		return $this->getTemplateObject()->getOnepageUrl($url);
	}

	/**
	 * 可支持含有%s之类字符的格式转换,
	 * 如果待翻译的字符串中不含有%s之类字符, 建议使用translate()方法, 运行速度更快些
	 *
	 * @param string $str 待翻译的字符串
	 * @param mixed [, mixed $args [, mixed $... ]] 任意多个参数
	 * @return string 返回当前语言翻译后字符串
	 */
	public function ___(){
		$args = func_get_args();
		return call_user_func_array(array(Ddm::getTranslate($this->getModuleName()),'___'),$args);
	}

	/**
	 * 不支持含有%s之类的转换
	 * @param string $str 待翻译的字符串
	 * @return string 返回当前语言翻译后字符串
	 */
	public function translate($str){
		return Ddm::getTranslate($this->getModuleName())->translate($str);
	}

	/**
	 * 尽可能的随机以多种方式输出, 增加匹配难度
	 * @param bool $useJavascript
	 * @return string
	 */
	public function getFormKeyHiddenInput($useJavascript = false){
		if($useJavascript){
			$quotes = '"';
			$formKeyValue = array_map(create_function('$s','return ord($s);'),str_split(Ddm::getHelper('core')->getFormKey()));
			$htmlId = Ddm::getHelper('core')->getRandomString(8);
			$attributes = array('id='.$quotes.'form-key-'.$htmlId.$quotes,'name='.$quotes.'form_key'.$quotes,'type='.$quotes.'hidden'.$quotes,'value='.$quotes.Ddm::getHelper('core')->getRandomString(8).$quotes);
			$jsHash = array("Str","get","docu","form","Ele","Char","key","By",".","-","Id","Code","ment",")",'"',"(","=",","," ","from","value","ing");
			$js = 'document.getElementById("form-key-'.$htmlId.'").value = String.fromCharCode('.implode(',',$formKeyValue).')';

			$html = '<input name="form_key_value" style="display:none;" type="text" value="" />'."\n";
			$html .= '<input '.implode(' ',$attributes).' />';
			$html .= '<script type="text/javascript" id="javascript-'.$htmlId.'">';
			$html .= '(function(){var self = document.getElementById("javascript-'.$htmlId.'");';
			$html .= 'var c = ["'.str_replace('"""','\'"\'',implode('","',$jsHash)).'"];';
			foreach($jsHash as $k=>$v)$js = str_replace($v,'$$+c['.$k.']+$$',$js);
			$html .= 'eval('.'"'.str_replace(array('+$$$$+','$$'),array('+','"'),$js).';"'.');';
			$html .= 'window.setTimeout(function(){try{self.removeNode(true);}catch($exception){self.parentNode.removeChild(self);}},1);';
			$html .= '})();';
			$html .= '</script>';
			return $html;
		}else{
			$quotes = mt_rand(0,1) ? '"' : "'";
			$attributes = array('name='.$quotes.'form_key'.$quotes,'type='.$quotes.'hidden'.$quotes,'value='.$quotes.Ddm::getHelper('core')->getFormKey().$quotes);
			shuffle($attributes);
			$html = '<input name="form_key_value" style="display:none;" type="text" value="" />'."\n";
			$html .= '<input '.implode(' ',$attributes).' />';
		}
		return $html;
	}

	/**
	 *
	 * @param string $id
	 * @param array $tags
	 * @param int $lifetime
	 * @return Core_Block_Abstract
	 */
	public function setCache($id, array $tags = NULL, $lifetime = NULL){
		if(Ddm::getConfig()->getConfigValue('cache/enable')){
			$this->_cacheId = $id;
			if($tags)$this->_cacheTags = array_unique(array_merge($this->_cacheTags, $tags),SORT_STRING);
			if($lifetime!==NULL)$this->_cacheLifetime = (int)$lifetime;
		}
		return $this;
	}

	public function getCacheId(){
		return $this->_cacheId;
	}

	public function getCacheTags(){
		return $this->_cacheTags;
	}

	public function getCacheLifetime(){
		return $this->_cacheLifetime;
	}

	/**
	 * @param bool $alwaysReturn 是否总是返回HTML内容, 而不是直接输出
	 * @return  string
	 */
	final public function toHtml($alwaysReturn = false){
		$this->_html = '';
		$this->_beforeToHtml();
		$cacheKey = $this->_cacheId.'_'.Ddm::getLanguage()->language_id.'_'.$this->getTemplateObject()->getTheme();
		if(!$this->_cacheId || ($this->_html = Ddm_Cache::load($cacheKey))===false){
			if($this->template && ($alwaysReturn || $this->_cacheId))ob_start();
			try{
				if($this->template)echo $this->_toHtml();
				else $this->_html = $this->_toHtml();
			}catch(Exception $e){
				if($this->template && ($alwaysReturn || $this->_cacheId))ob_get_clean();
				throw $e;
			}
			if($alwaysReturn || $this->_cacheId){
				if($this->template)$this->_html = ob_get_clean();
				if($this->_cacheId){
					$this->_html = preg_replace("/(<\/?\w+[^>]*>)\s*[\r\n]+\s*/",'\1',$this->_html);
					Ddm_Cache::save($cacheKey,$this->_html,$this->_cacheTags,$this->_cacheLifetime);
				}
			}
		}
		$this->_afterToHtml();
		return $this->_html;
	}

	/**
	 * @return string
	 */
	protected function _toHtml(){
		if($this->template){
			if($templateFile = $this->getTemplateFile()){
				include $templateFile;
			}else{
				echo 'Template file('.$this->template.') not found';
			}
		}
		return '';
	}

	/**
	 * @return Core_Block_Abstract
	 */
	protected function _beforeToHtml(){
		return $this;
	}

	/**
	 * @return Core_Block_Abstract
	 */
	protected function _afterToHtml(){
		return $this;
	}
}
