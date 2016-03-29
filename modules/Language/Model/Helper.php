<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Language_Model_Helper {
	protected $_languagePath = NULL;
	protected $_currentModuleName = NULL;
	protected $_translateData = array();
	protected $_allModules = NULL;
	protected $_languageFromFile = array();

	public function __construct(){
		$this->_languagePath = SITE_ROOT.'/data/languagedata';
		is_dir($this->_languagePath) or mkdir($this->_languagePath, 0700, true);
	}

	public function __destruct() {
		unset($this->_languageFromFile,$this->_translateData);
	}

	/**
	 * @return array
	 */
	public function getAllModules(){
		if($this->_allModules===NULL){
			$this->_allModules = array();
			foreach(Ddm::getModuleConfig() as $moduleName=>$moduleData){
				if(!empty($moduleData['active']))$this->_allModules[$moduleName] = $moduleName;
			}
		}
		return $this->_allModules;
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
		if($argList = func_get_args()){
			$_language = $this->translate($argList[0]);
			if($_language && isset($argList[1])){
				unset($argList[0]);
				$_language = vsprintf($_language,$argList);
			}
			return $_language;
		}
		return '';
	}

	/**
	 * @param string $moduleName
	 * @return Language_Model_Helper
	 * @throws Exception
	 */
	public function setModuleName($moduleName = 'Core'){
		if($moduleName && Ddm::getEnabledModuleConfig($moduleName)){
			$this->_currentModuleName = ucfirst($moduleName);
		}else{
			$this->_currentModuleName = 'Core';
			//throw new Exception('The '.$moduleName.' module name does not exist.');
		}
		return $this;
	}

	/**
	 * 不支持含有%s之类的转换
	 * @param string $str 待翻译的字符串
	 * @return string 返回当前语言翻译后字符串
	 */
	public function translate($str){
		if($str && Ddm::getLanguage()->language_id && Ddm::getLanguage()->getAllLanguage(false)){
			$this->_currentModuleName or $this->_currentModuleName = 'Core';
			if(isset($this->_translateData[$this->_currentModuleName][$str]))return $this->_translateData[$this->_currentModuleName][$str];
			$translateString = $this->_getModuleTranslate($str,$this->_currentModuleName);
			if($translateString===NULL){//var_dump('IN+++',$this->_currentModuleName,$str);
				if($this->_currentModuleName!='Core'){
					$translateString = $this->_getModuleTranslate($str,'Core');
				}
				if($translateString===NULL){
					//继续尝试找其它模块的翻译，直到找到为止
					foreach($this->getAllModules() as $moduleName=>$value){
						if($moduleName!=$this->_currentModuleName && $moduleName!='Core'){
							$translateString = $this->_getModuleTranslate($str,$moduleName);
							if($translateString!==NULL)break;
						}
					}
					if($translateString===NULL && Ddm::getConfig()->getConfigValue('web/language/auto_saved'))$this->_saveTranslate($str,'');
				}
			}
			$this->_translateData[$this->_currentModuleName][$str] = (string)$translateString;
			if($this->_translateData[$this->_currentModuleName][$str]=='')$this->_translateData[$this->_currentModuleName][$str] = $str;
			else $str = $this->_translateData[$this->_currentModuleName][$str];
		}
		return $str;
	}

	/**
	 * @param int $languageId
	 * @return Language_Model_Helper
	 */
	public function removeTranslate($languageId){
		if(is_numeric($languageId)){
			$path = $this->getCachePath($languageId);
			if(is_dir($path) && ($handle = opendir($path))){
				while(false!==($file = readdir($handle))){
					if($file!='.' && $file!='..')unlink("$path/$file");
				}
				closedir($handle);
				rmdir($path);
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getLanguageToOption(){
		$options = array();
		foreach(Ddm::getLanguage()->getAllLanguage(true) as $language){
			$options[] = array('value'=>$language['language_id'],'label'=>$language['language_name']);
		}
		return $options;
	}

	/**
	 * @param string $languageId
	 * @param string $moduleName
	 * @return string
	 */
	public function getCacheFile($languageId,$moduleName){
		return "$this->_languagePath/$languageId/$moduleName";
	}

	/**
	 * @param int $languageId
	 * @return string
	 */
	public function getCachePath($languageId){
		return "$this->_languagePath/$languageId";
	}

	/**
	 * @param string $str
	 * @param string $moduleName
	 * @return string
	 */
	protected function _getModuleTranslate($str,$moduleName){
		$file = $this->getCacheFile(Ddm::getLanguage()->language_id,$moduleName);
		if(!isset($this->_languageFromFile[$file])){
			$this->_languageFromFile[$file] = is_file($file) ? unserialize(file_get_contents($file)) : array();
		}

		return !$this->_languageFromFile[$file] || !isset($this->_languageFromFile[$file][$str]) ? NULL : $this->_languageFromFile[$file][$str];
	}

	/**
	 * @param string $str1
	 * @param string $str2
	 * @return Language_Model_Helper
	 */
	protected function _saveTranslate($str1,$str2 = ''){
		$languageId = Ddm::getLanguage()->language_id;
		$file = $this->getCacheFile($languageId,$this->_currentModuleName);
		if(is_file($file)){
			$_language = unserialize(file_get_contents($file));
			$_language[$str1] = $str2;
		}else{
			$_language = array($str1=>$str2);
			//$path = $this->getCachePath($languageId);
			//is_dir($path) or mkdir($path, 0700, false);
		}
		Ddm::getHelper('core')->saveFile($file,serialize($_language),LOCK_EX);
		return $this;
	}
}
