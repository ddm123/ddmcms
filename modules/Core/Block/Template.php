<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Block_Template {
	private $_blocks = array();
	private $_js = array();
	private $_css = array();
	private $_meta = array();
	private $_isInAdmin = false;
	protected $_javascript = '';
	protected $_style = '';
	protected $_blocksLock = false;
	protected $_moduleName = NULL;
	protected $_theme = NULL;
	protected $_widgets = array();
	protected $_designConfig = NULL;
	private static $_templateFile = array();
	protected static $_instance = NULL;
	public $title = '';
	public $template = '';

	public function __construct($inAdmin = IN_ADMIN){
		$this->template = 'main-page.phtml';
		$this->_isInAdmin = (bool)$inAdmin;
		$this->addJavascript('var BASE_URL = "'.Ddm::getCurrentBaseUrl().'";');
		$this->_construct();
	}

	/**
	 * 使用单例模式
	 * @return Core_Block_Template
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Core_Block_Template()) : self::$_instance;
	}

	/**
	 * @return bool
	 */
	public function isInAdmin(){
		return $this->_isInAdmin;
	}

	/**
	 * @param string $moduleName
	 * @param string $blockClass
	 * @param string $templateName
	 * @return Core_Block_Abstract
	 */
	public function createBlock($moduleName,$blockClass,$templateName = NULL){
		$blockClassName = ucfirst($moduleName).'_Block_'.str_replace(' ','_',ucwords(str_replace('_',' ',$blockClass)));
		$block = new $blockClassName();
		$block->setTemplateObject($this)->setModuleName($moduleName)->init();
		$block->setTemplate($templateName);
		return $block;
	}

	/**
	 * 返回模板/图片/样式/Javascript文件的完整路径
	 * @param string $file
	 * @param string $type
	 * @param string $moduleName
	 * @param Core_Block_Template $templateObj
	 * @return string
	 */
	public static function getTemplateFile($file,$type = 'template',$moduleName = NULL,Core_Block_Template $templateObj = NULL){
		if(!isset(self::$_templateFile["$type/$file/$moduleName"])){
			$type=='image' and $type = 'images';
			$moduleName = ucfirst($moduleName ? $moduleName : Ddm_Controller::singleton()->getModuleName());
			$templateObj or $templateObj = self::singleton();
			$paths = array();
			$theme = $templateObj->getTheme();
			$folder = $templateObj->isInAdmin() ? 'adminhtml' : 'frontend';
			if($theme!='default'){
				//在主题文件夹找, default主题必须放在各自的模块里, 不可以放在公共的主题文件夹
				$paths[] = DESIGN_FOLDER."/$folder/$theme/$type/$moduleName/$file";
				$paths[] = DESIGN_FOLDER."/$folder/$theme/$type/$file";
			}
			$paths[] = MODULES_FOLDER."/$moduleName/design/$folder/$theme/$type/$file";
			if($moduleName!='Core')$paths[] = MODULES_FOLDER."/Core/design/$folder/$theme/$type/$file";//在系统模块的当前主题找
			if($theme!='default'){
				$paths[] = MODULES_FOLDER."/$moduleName/design/$folder/default/$type/$file";//在当前模块的默认主题找
				if($moduleName!='Core')$paths[] = MODULES_FOLDER."/Core/design/$folder/default/$type/$file";//在核心模块的默认主题找
			}
//echo '<pre>';print_r($paths);echo '</pre>';
			$pathString = false;
			foreach($paths as $path){
				if(is_file(SITE_ROOT."/$path")){
					$pathString = $path;
					break;
				}
			}
			if($type=='template'){
				self::$_templateFile["$type/$file/$moduleName"] = $pathString ? SITE_ROOT."/$pathString" : false;
			}else{
				self::$_templateFile["$type/$file/$moduleName"] = Ddm::getLanguage()->getBaseUrl($type).($pathString ? $pathString : $paths[0]);
			}
		}
		return self::$_templateFile["$type/$file/$moduleName"];
	}

	/**
	 * @param string $name
	 * @return Core_Block_Template
	 */
	public function setModuleName($name){
		$this->_moduleName = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModuleName(){
		return $this->_moduleName;
	}

	/**
	 * @param string $template
	 * @return Core_Block_Template
	 */
	public function setTemplate($template){
		if($template!='')$this->template = $template;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTheme(){
		if($this->_theme===NULL){
			$this->_theme = Ddm::getConfig()->getConfigValue('web/design/'.($this->_isInAdmin ? 'admin' : 'frontend').'_theme') or $this->_theme = 'default';
			Ddm::dispatchEvent('get_theme_after',array('template'=>$this));
		}
		return $this->_theme;
	}

	/**
	 * @param string $theme
	 * @return Core_Block_Template
	 */
	public function setTheme($theme){
		if($theme)$this->_theme = $theme;
		return $this;
	}

	/**
	 * @param string $file
	 * @param string $type
	 * @param string $moduleName
	 * @return string
	 */
	public function getFile($file,$type = 'template',$moduleName = NULL){
		return self::getTemplateFile($file,$type,$moduleName,$this);
	}

	/**
	 * @param bool $lock
	 * @return Core_Block_Template
	 */
	public function setBlocksLock($lock){
		$this->_blocksLock = $lock;
		return $this;
	}

	/**
	 * @param Core_Block_Abstract $block
	 * @param string $name
	 * @param bool $graceful 如果已存在相同名称的块, 是否抛出异常
	 * @return Core_Block_Template
	 */
	public function addBlock(Core_Block_Abstract $block,$name,$graceful = false){
		if($this->_blocksLock==false){
			if(isset($this->_blocks[$name])){
				if($graceful==false){
					throw new Exception($name.' block name already exists');
				}
			}else{
				$this->_blocks[$name] = $block->setTemplateObject($this);
			}
		}
		return $this;
	}

	/**
	 * 和$this->addBlock()的区别是该方法会插入到$this->_blocks数组的前面, 而不是未尾
	 * @param Core_Block_Abstract $block
	 * @param string $name
	 * @param bool $graceful 如果已存在相同名称的块, 是否抛出异常
	 * @return Core_Block_Template
	 */
	public function addBlockToFirst(Core_Block_Abstract $block,$name,$graceful = false){
		if($this->_blocksLock==false){
			if(isset($this->_blocks[$name])){
				if($graceful==false){
					throw new Exception($name.' block name already exists');
				}
			}else{
				$this->_blocks = array_merge(array($name=>$block),$this->_blocks);
			}
		}
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
	 * @param string $string
	 * @return Core_Block_Template
	 */
	public function setTitle($string){
		$this->title = $string;
		return $this;
	}

	/**
	 * 和setTitle()方法的区别是不断的拼接
	 * @param string $string
	 * @return Core_Block_Template
	 */
	public function addTitle($string){
		$this->title = "$string - {$this->title}";
		return $this;
	}

	/**
	 * @param string $nameValue
	 * @param string $content
	 * @param string $metaName
	 * @param bool $isReplace 是否替换原来的
	 * @return Core_Block_Template
	 */
	public function addMeta($nameValue,$content,$metaName = 'name',$isReplace = true){
		if(!isset($this->_meta["$metaName=$nameValue"]) || $isReplace){
			$this->_meta["$metaName=$nameValue"] = '<meta '.$metaName.'="'.$nameValue.'" content="'.Ddm_String::singleton()->escapeHtml($content).'" />';
		}
		return $this;
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return Core_Block_Template
	 */
	public function addJs($file,$moduleName = NULL){
		isset($this->_js["$file:$moduleName"]) or ($this->_js["$file:$moduleName"] = strpos($file,'http')===0 ? $file : self::getTemplateFile($file,'js',$moduleName,$this));
		return $this;
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return Core_Block_Template
	 */
	public function addCss($file,$moduleName = NULL){
		isset($this->_css["$file:$moduleName"]) or ($this->_css["$file:$moduleName"] = strpos($file,'http')===0 ? $file : self::getTemplateFile($file,'css',$moduleName,$this));
		return $this;
	}

	/**
	 * @param bool $asHtml
	 * @return string|array
	 */
	public function getMeta($asHtml = false){
		return $asHtml ? implode("\r\n",$this->_meta)."\r\n" : $this->_meta;
	}

	/**
	 * @param bool $asHtml
	 * @return string|array
	 */
	public function getJs($asHtml = false){
		if($asHtml){
			$html = '';
			foreach($this->_js as $jsFile)$html .= '<script src="'.$jsFile.'" type="text/javascript"></script>'."\r\n";
			return $html;
		}
		return $this->_js;
	}

	/**
	 * @param bool $asHtml
	 * @return string|array
	 */
	public function getCss($asHtml = false){
		if($asHtml){
			$html = '';
			foreach($this->_css as $cssFile)$html .= '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" />'."\r\n";
			return $html;
		}
		return $this->_css;
	}

	/**
	 * @param string $javascript
	 * @return Core_Block_Template
	 */
	public function addJavascript($javascript){
		$this->_javascript=='' or $this->_javascript .= "\r\n";
		$this->_javascript .= $javascript;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getJavascript(){
		return $this->_javascript;
	}

	/**
	 * @param string $style
	 * @return Core_Block_Template
	 */
	public function addStyle($style){
		if($style){
			$style = trim(preg_replace('/(\{|\}|;|\:)\s+/','\\1',$style));
			$this->_style .= $style;
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStyle(){
		return $this->_style;
	}

	/**
	 * @param string $name
	 * @return Core_Block_Template
	 */
	public function removeBlock($name){
		if(isset($this->_blocks[$name]))unset($this->_blocks[$name]);
		return $this;
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return Core_Block_Template
	 */
	public function removeCss($file,$moduleName = NULL){
		if(isset($this->_css["$file:$moduleName"]))unset($this->_css["$file:$moduleName"]);
		return $this;
	}

	/**
	 * @param string $file
	 * @param string $moduleName
	 * @return Core_Block_Template
	 */
	public function removeJs($file,$moduleName = NULL){
		if(isset($this->_js["$file:$moduleName"]))unset($this->_js["$file:$moduleName"]);
		return $this;
	}

	/**
	 * @param string $nameValue
	 * @param string $metaName
	 * @return Core_Block_Template
	 */
	public function removeMeta($nameValue,$metaName = 'name'){
		if(isset($this->_meta["$metaName=$nameValue"]))unset($this->_meta["$metaName=$nameValue"]);
		return $this;
	}

	/**
	 * @param string $identifier
	 * @return Cms_Model_Widget
	 */
	public function getWidget($identifier){
		if(!isset($this->_widgets[$identifier])){
			$this->_widgets[$identifier] = $this->createBlock('cms','widget_view')->setTemplateObject($this)->init()->getWidget($identifier);
		}
		return $this->_widgets[$identifier];
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	public function getWidgetHtml($identifier){
		return $this->getWidget($identifier)->content;
	}

	/**
	 * 获取单页面的链接地址
	 * @param string $url
	 * @return string
	 */
	public function getOnepageUrl($url){
		return Ddm::getLanguage()->getHomeUrl().$url;
	}

	/**
	 * @param string $path
	 * @param int $languageId
	 * @return string|null
	 */
	public function getConfig($path,$languageId = NULL){
		return Ddm::getConfig()->getConfigValue($path,$languageId);
	}

	/**
	 * @param string $type
	 * @return string
	 */
	public function getBaseUrl($type = 'link'){
		return Ddm::getLanguage()->getBaseUrl($type);
	}

	/**
	 * @return array
	 */
	public function getDesignConfig(){
		if($this->_designConfig===NULL){
			$configFile = SITE_ROOT.'/'.DESIGN_FOLDER.'/'.($this->isInAdmin() ? 'adminhtml' : 'frontend').'/'.$this->getTheme().'/config.xml';
			if($this->getTheme()=='default' || !is_file($configFile)){
				$this->_designConfig = array();
			}else{
				$cacheKey = 'template_design_config_'.($this->isInAdmin() ? 'adminhtml' : 'frontend').'_'.$this->getTheme();
				$this->_designConfig = Ddm_Cache::load($cacheKey);
				if($this->_designConfig===false){
					$this->_designConfig = Ddm::getConfig()->xmlFileToArray($configFile) or $this->_designConfig = array();
					Ddm_Cache::save($cacheKey,$this->_designConfig,array('config'),0);
				}
			}
		}
		return $this->_designConfig;
	}

	/**
	 * @return string
	 */
	public function fetch(){
		$html = '';
		Ddm::dispatchEvent('template_fetch_before', array('object'=>$this));
		$this->_beforeFetch();
		foreach($this->_blocks as $name=>$block){
			//if($block->template==='' && preg_match('/^[\w\-\.]+$/',$name))$block->template = "$name.phtml";
			if($block->isFetch())$html .= $block->toHtml();
		}
		Ddm::dispatchEvent('template_fetch_after', array('object'=>$this));
		$this->_afterFetch();
		return $html;
	}

	/**
	 * @param string $moduleName
	 * @return Core_Block_Template
	 */
	public function display($moduleName = NULL){
		Ddm::dispatchEvent('template_display_before', array('object'=>$this));
		$this->_beforeDisplay();
		if($this->template){
			$templateFile = self::getTemplateFile($this->template,'template',$moduleName ? $moduleName : $this->_moduleName,$this);
			if($templateFile){
				include $templateFile;
			}else{
				echo 'Template file('.$this->template.') not found';
			}
		}
		Ddm::dispatchEvent('template_display_after', array('object'=>$this));
		$this->_afterDisplay();
		return $this;
	}

	/**
	 * @return Core_Block_Template
	 */
	public function clear(){
		$this->_blocks = array();
		$this->_css = array();
		$this->_js = array();
		return $this;
	}

	/**
	 * @return float
	 */
	public function getRunTime(){
		return round(microtime(true)-Ddm_Request::server()->START_TIME,4);
	}

	/**
	 * @return int
	 */
	public function getQueryCount(){
		$_count = 0;
		$databaseConfig = Ddm::getConfig()->getDbConfig();
		$connections = isset($databaseConfig['driver']) && is_string($databaseConfig['driver']) ? (Ddm_Db::getAllConnections() ? array(current(Ddm_Db::getAllConnections())) : array()) : Ddm_Db::getAllConnections();
		foreach($connections as $conn)$_count += $conn->getQueryCount();
		return $_count;
	}

	/**
	 * @return string
	 */
	public function getMemoryUsage(){
		return Ddm_String::singleton()->formatBytes(memory_get_usage());
	}

	/**
	 * @return Core_Block_Template
	 */
	protected function _construct(){
		$this->addMeta('author','DDM')->addCss('common.css','core');
		$this->addBlock($this->createBlock('core','head')->isFetch(false),'head');
		return $this;
	}

	/**
	 * @return Core_Block_Template
	 */
	protected function _beforeFetch(){
		return $this;
	}

	/**
	 * @return Core_Block_Template
	 */
	protected function _afterFetch(){
		return $this;
	}

	/**
	 * @return Core_Block_Template
	 */
	protected function _beforeDisplay(){
		if($this->getDesignConfig() && !empty($this->_designConfig['methods'])){
			foreach($this->_designConfig['methods'] as $method=>$params){
				if(isset($params[0])){
					foreach($params as $_params){
						call_user_func_array(array($this,$method),$_params);
					}
				}else{
					call_user_func_array(array($this,$method),$params);
				}
			}
		}

		return $this;
	}

	/**
	 * @return Core_Block_Template
	 */
	protected function _afterDisplay(){
		return $this;
	}
}
