<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

$_SERVER['START_TIME'] = microtime(true);

define('_VERSION_', '1.5.0');
define('GET_MQG',get_magic_quotes_gpc());
define('SITE_ROOT',strtr(dirname(__FILE__),'\\','/'));
define('MODULES_FOLDER','modules');
define('DESIGN_FOLDER','design');

defined('E_STRICT') or define('E_STRICT', 2048);
defined('E_RECOVERABLE_ERROR') or define('E_RECOVERABLE_ERROR', 4096);
defined('E_DEPRECATED') or define('E_DEPRECATED', 8192);
defined('E_USER_DEPRECATED') or define('E_USER_DEPRECATED',16384);

set_include_path(SITE_ROOT.'/lib'.PATH_SEPARATOR.SITE_ROOT.'/'.MODULES_FOLDER);

final class Ddm{
	const MODULES_CONFIG_CACHE_KEY = 'all_modules';
	const MODULE_CONFIG_CACHE_KEY_PRE = 'module_config_';

	private static $_versionCompare = array();
	private static $_initialized = false;
	private static $_isInstalled = NULL;
	private static $_registry = array();
	private static $_allModules = NULL;
	private static $_config = NULL;
	private static $_language = NULL;
	private static $_languages = NULL;
	private static $_translate = NULL;
	private static $_events = NULL;
	private static $_eventListener = array();
	private static $_helpers = array();
	private static $_sessions = array();

	public static $enableDebug = true;

	private function __construct() {
		//私有的构造方法
	}

	/**
	 * 判断当前PHP版本是否大于或等于某个版本号
	 * @param string $compareVersion
	 * @return bool
	 */
	public static function leastThisVersion($compareVersion){
		return isset(self::$_versionCompare[$compareVersion]) ? self::$_versionCompare[$compareVersion] : (self::$_versionCompare[$compareVersion] = version_compare($compareVersion,PHP_VERSION,'<='));
	}

	/**
	 * 获取当前浏览器地址栏上显示的域名,末尾包含斜杠
	 * @return string
	 */
	public static function getCurrentBaseUrl(){
		return Ddm_Controller::singleton()->getCurrentBaseUrl();
	}

	/**
	 * 获取首页URL, 末尾包含斜杠
	 * @return string
	 */
	public static function getHomeUrl(){
		return self::$_language->getBaseUrl('home');
	}

	/**
	 * 如果你是想在获取首页的链接后面拼接另一个路径作为一个的新链接, 就需要使用该方法, 而不是getHomeUrl()方法, 末尾包含斜杠
	 * @return string
	 */
	public static function getBaseUrl(){
		return self::$_language->getBaseUrl('link');
	}

	/**
	 * 返回一个链接
	 * @param string $path 格式: Module/Ddm_Controller/Action
	 * @param array $params
	 * @param bool $folderStyle
	 * @return string
	 */
	public static function getUrl($path, array $params = array(), $folderStyle = false){
		return self::$_language->getUrl($path,$params,$folderStyle);
	}

	/**
	 * 获取在后台配置的网站名称
	 * @return string
	 */
	public static function getSiteName(){
		return self::$_config->getConfigValue('web/base/web_name');
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param bool $graceful
	 * @throws Exception
	 * @return void
	 */
	public static function register($key, $value, $graceful = false){
		if(isset(self::$_registry[$key])){
			if($graceful)return;
			throw new Exception('Registry key "'.$key.'" already exists.');
		}
		self::$_registry[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return mixed
	 * @return void
	 */
	public static function registry($key){
		return isset(self::$_registry[$key]) ? self::$_registry[$key] : NULL;
	}

	/**
	 * @param string $key
	 * @return void
	 */
	public static function unregister($key){
		if(isset(self::$_registry[$key])){
			if(is_object(self::$_registry[$key]) && (method_exists(self::$_registry[$key], '__destruct'))){
				self::$_registry[$key]->__destruct();
			}
			unset(self::$_registry[$key]);
		}
	}

	/**
	 * 临时监听一些事件, 这和在config.xml定义的事件监听不一样: 在config.xml定义的, 只要监听的事件被触发都能监听到
	 * 这对于在访问某个页面的时候才需要对某个事件做一些处理很有用
	 * @param string $eventName
	 * @param callable $callback
	 * @return void
	 */
	public static function addEventListener($eventName,$callback){
		isset(self::$_eventListener[$eventName]) or self::$_eventListener[$eventName] = array();
		self::$_eventListener[$eventName][] = $callback;
	}

	/**
	 * 获取全部在config.xml中定义的事件监听
	 * @param string $eventName
	 * @return array
	 */
	public static function getEvents($eventName = NULL){
		if(self::$_events===NULL){
			self::$_events = array();
			foreach(self::getModuleConfig() as $config){
				if($config['active'] && !empty($config['events'])){
					foreach($config['events'] as $_eventName=>$observer){
						isset(self::$_events[$_eventName]) or self::$_events[$_eventName] = array();
						isset($observer[0]) or $observer = array(0=>$observer);
						self::$_events[$_eventName] = array_merge(self::$_events[$_eventName], $observer);
					}
				}
			}
		}
		if($eventName!==NULL)return isset(self::$_events[$eventName]) ? self::$_events[$eventName] : NULL;
		return self::$_events;
	}

	/**
	 * @param string $name
	 * @param array $data
	 * @return void
	 */
	public static function dispatchEvent($name, array $data = array()){
		if($events = self::getEvents($name)){
			foreach($events as $observer){
				isset($observer['type']) or $observer['type'] = 'new';
				if($observer['class'] && $observer['method'] && $observer['type']!='disabled'){
					$object = $observer['type']=='singleton' ? call_user_func(array($observer['class'], 'singleton')) : new $observer['class']();
					call_user_func(array($object,$observer['method']),$data);
				}
			}
		}
		if(isset(self::$_eventListener[$name])){
			foreach(self::$_eventListener[$name] as $callback)
				call_user_func($callback,$data);
		}
	}

	/**
	 * @return array
	 */
	public static function getAllModuleConfig(){
		if(self::$_allModules===NULL){
			self::$_allModules = Ddm_Cache::load(self::MODULES_CONFIG_CACHE_KEY);
			if(self::$_allModules===false){
				$handle = opendir(SITE_ROOT.'/'.MODULES_FOLDER);
				self::$_allModules = array();
				while($file = readdir($handle)){
					if($file!='.' && $file!='..' && is_dir(SITE_ROOT."/".MODULES_FOLDER."/$file")){
						self::$_allModules[$file] = array('module_name'=>$file,'active'=>false);
					}
				}
				closedir($handle);
				Ddm_Cache::save(self::MODULES_CONFIG_CACHE_KEY,self::$_allModules,array('module'),0);
			}
			foreach(self::$_allModules as $_moduleName=>$_value){
				$moduleConfig = Ddm_Cache::load(self::MODULE_CONFIG_CACHE_KEY_PRE.$_moduleName);
				if($moduleConfig===false){
					if(is_file(SITE_ROOT."/".MODULES_FOLDER."/$_moduleName/etc/config.xml") && ($config = Ddm::getConfig()->xmlFileToArray(SITE_ROOT."/".MODULES_FOLDER."/$_moduleName/etc/config.xml"))){
						$config['module_name'] = $_moduleName;
						self::$_allModules[$_moduleName] = $config;
					}
					Ddm_Cache::save(self::MODULE_CONFIG_CACHE_KEY_PRE.$_moduleName,self::$_allModules[$_moduleName],array('module','config'),0);
				}else self::$_allModules[$_moduleName] = $moduleConfig;
			}
		}
		return self::$_allModules;
	}

	/**
	 * @param string $moduleName
	 * @return array
	 */
	public static function getModuleConfig($moduleName = NULL){
		self::$_allModules===NULL and self::getAllModuleConfig();
		if($moduleName)
			return isset(self::$_allModules[$moduleName = ucfirst($moduleName)]) ? self::$_allModules[$moduleName] : NULL;
		return self::$_allModules;
	}

	/**
	 * @param string $moduleName
	 * @return array|null
	 */
	public static function getEnabledModuleConfig($moduleName){
		$mc = self::getModuleConfig($moduleName);
		return $mc && !empty($mc['active']) ? $mc : NULL;
	}

	/**
	 * @return Config_Model_Config
	 */
	public static function getConfig(){
		return self::$_config;
	}

	/**
	 * @param string $path
	 * @param int $languageId
	 * @return string|null
	 */
	public static function getConfigValue($path, $languageId = NULL){
		return self::$_config->getConfigValue($path, $languageId);
	}

	/**
	 * @param int|string $languageId 'ID' or 'Code'
	 * @return Language_Model_Language
	 */
	public static function getLanguage($languageId = NULL){
		if($languageId===NULL)return self::$_language;
		if(!isset(self::$_languages[$languageId])){
			$languageId = (string)$languageId;
			if($languageId==self::$_language->language_id || $languageId==self::$_language->language_code){
				self::$_languages[$languageId] = self::$_language;
			}else{
				self::$_languages[$languageId] = new Language_Model_Language();
				self::$_languages[$languageId]->loadFromId($languageId);

				//If not exist, then load the default language
				if(!self::$_languages[$languageId]->language_code){
					return self::$_languages[$languageId] = self::getLanguage('0');
				}

				$languageCode = $languageId==self::$_languages[$languageId]->language_id ? self::$_languages[$languageId]->language_code : self::$_languages[$languageId]->language_id;
				if($languageCode!=$languageId){
					self::$_languages[$languageCode] = self::$_languages[$languageId];
				}
			}
		}
		return self::$_languages[$languageId];
	}

	/**
	 * @param string $moduleName
	 * @return Core_Model_Helper
	 */
	public static function getHelper($moduleName){
		if(isset(self::$_helpers[$moduleName = ucfirst($moduleName)]))return self::$_helpers[$moduleName];
		$className = $moduleName.'_Model_Helper';
		return self::$_helpers[$moduleName] = new $className();
	}

	/**
	 * @param string $namespace
	 * @return Ddm_Session
	 */
	public static function getSession($namespace = NULL){
		if(isset(self::$_sessions[$namespace]))return self::$_sessions[$namespace];
		self::$_sessions[$namespace] = new Ddm_Session();
		self::$_sessions[$namespace]->init($namespace);
		return self::$_sessions[$namespace];
	}

	/**
	 * @param string $moduleName
	 * @return Language_Model_Helper
	 */
	public static function getTranslate($moduleName = 'Core'){
		return self::$_translate->setModuleName($moduleName);
	}

	/**
	 * @param int|string|false $languageId 语言ID或语言Code
	 * @return void
	 */
	public static function init($languageId = false){
		if(self::$_initialized==false){
			try{
				spl_autoload_register(array('Ddm','_loadClass'));
				if(self::isInstalled()){
					//ini_set('display_errors','Off');
					//ini_set('log_errors','Off');
					set_error_handler(array('Ddm','_errorHandler'),E_ALL);
					register_shutdown_function(array('Ddm','_onShutdown'));
				}
				self::$_config = new Config_Model_Config();
				self::$_language = new Language_Model_Language();

				if(!self::isInstalled() && !isset($_SERVER['CMS_INSTALL'])){
					Ddm_Request::singleton()->redirect(self::getCurrentBaseUrl().'install/');
					return;
				}else if(!isset($_SERVER['CMS_INSTALL'])){
					$install = new Core_Model_Install();
					$install->applyInstall();
				}

				self::$_language->isSaveToCookie(true)->loadFromVisitor($languageId);
				self::$_translate = self::getHelper('Language');
				self::$_initialized = true;
			}catch(Exception $exception){
				$exceptionLog = $exception->getMessage()."\r\n".$exception->getTraceAsString();
				Ddm::getHelper('core')->saveFile(SITE_ROOT.'/data/errors/exception/exception-log-'.date('Y-m').'.txt',date('c')."\r\n$exceptionLog\r\n[{$_SERVER['REQUEST_METHOD']}] {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\r\n\r\n",FILE_APPEND);
				if(self::$enableDebug){
					echo "<pre>$exceptionLog</pre>";
				}
			}
		}
	}

	/**
	 * 是否是已经安装好了的
	 * @param bool $refresh
	 * @return bool
	 */
	public static function isInstalled($refresh = false){
		return self::$_isInstalled===NULL || $refresh ? (self::$_isInstalled = file_exists(SITE_ROOT.Config_Model_Config::XML_CONFIG_FILE)) : self::$_isInstalled;
	}

	private static function _loadClass($className){
		$classFile = str_replace('_','/', $className).'.php';
		require $classFile;
	}

	/**
	 * @access private
	 */
	public static function _errorHandler($errno,$errstr,$errfile,$errline){
		if(($errno&error_reporting())==0)return;

		if($errno==E_DEPRECATED && stripos($errstr,'magic_quotes_gpc')!==false){
			// ignore strict and deprecated notices
			return true;
		}

		$errorMessage = '';
		switch($errno){
			case E_ERROR:$errorMessage .= 'Fatal Error';break;
			case E_WARNING:$errorMessage .= 'Warning';break;
			case E_PARSE:$errorMessage .= 'Parse Error';break;
			case E_NOTICE:$errorMessage .= 'Notice';break;
			case E_CORE_ERROR:$errorMessage .= 'Core Error';break;
			case E_CORE_WARNING:$errorMessage .= 'Core Warning';break;
			case E_COMPILE_ERROR:$errorMessage .= 'Compile Error';break;
			case E_COMPILE_WARNING:$errorMessage .= 'Compile Warning';break;
			case E_USER_ERROR:$errorMessage .= 'User Error';break;
			case E_USER_WARNING:$errorMessage .= 'User Warning';break;
			case E_USER_NOTICE:$errorMessage .= 'User Notice';break;
			case E_STRICT:$errorMessage .= 'Strict Notice';break;
			case E_RECOVERABLE_ERROR:$errorMessage .= 'Recoverable Error';break;
			case E_DEPRECATED:$errorMessage .= 'Deprecated functionality';break;
			case E_USER_DEPRECATED:$errorMessage .= 'User-generated warning message';break;
			default:$errorMessage .= 'Unknown error ($errno)';
		}

		$errorMessage .= ": $errstr  in $errfile on line $errline";
		Ddm::getHelper('core')->saveFile(SITE_ROOT.'/data/errors/php/php-errors-'.date('Y-m').'.txt','['.date('c')."] $errorMessage\r\n[{$_SERVER['REQUEST_METHOD']}]{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\r\n",FILE_APPEND);
		if(self::$enableDebug)echo "$errorMessage<br />\r\n";
		return true;
	}

	/**
	 * @access private
	 */
	public static function _onShutdown(){
		if(Ddm_Db::isBeginTransaction()){
			Ddm_Db::getWriteConn()->rollBack();
		}
		if($error = error_get_last()){
			self::_errorHandler($error['type'],$error['message'],$error['file'],$error['line']);
		}
	}
}

//-------------- debug functions ---------------------
function varDump($str,$filename = NULL,$noObj = false){
    if(is_array($str)){
        if($noObj){
            $str = _filterObject($str);
        }
        $str = print_r($str,true);
    }else if(is_object($str))$str = $noObj ? "object(".get_class($str).")" : print_r($str,true);
    else if(is_bool($str))$str = $str ? 'bool(true)' : 'bool(false)';
    else if($str===NULL)$str = 'NULL';
    if($filename){
        file_put_contents(SITE_ROOT.'/data/debug/'.$filename,"$str\r\n",FILE_APPEND);
    }else{
        echo "\r\n<pre style=\"text-align:left;\">$str</pre>\r\n";
    }
}
function _filterObject($var){
    if(is_array($var)){
        foreach($var as $k=>$v){
            if(is_array($v))$var[$k] = _filterObject($v);
            else if(is_object($v))$var[$k] = "object(".get_class($v).")";
        }
    }
    return $var;
}
/**
 * PHP5.3版本以上
 * @param string $saveAsFile
 * @return void
 */
function debugPrintBacktrace($saveAsFile = NULL){
    $output = '';
    foreach(debug_backtrace() as $key=>$item){
        if($item['function'] == "include" || $item['function'] == "include_once" || $item['function'] == "require_once" || $item['function'] == "require"){
             $output .= "#".$key." ".$item['function']."(".$item['args'][0].") called at [".$item['file'].":".$item['line']."]\r\n";
        }else{
            if($args = isset($item['args']) ? $item['args'] : array()){
                $args = array_map(function($v){
                    if(is_object($v))$v = get_class($v);
                    else if(is_array($v))$v = 'array';
                    else if(is_numeric($v));
                    else if(is_string($v))$v = "'$v'";
                    else if(is_bool($v))$v = $v ? 'true' : 'false';
                    else if($v===NULL)$v = 'NULL';
                    return $v;
                },$args);
            }
            $class = isset($item['object']) ? get_class($item['object']) : '';
            isset($item['file']) or $item['file'] = 'None';
            isset($item['line']) or $item['line'] = '0';
            $output .= "#".$key." ".(isset($item['class']) ? $item['class'].($class==$item['class'] ? '' : "($class)").$item['type'] : '').$item['function']."(".implode(',',$args).") called at [".$item['file'].":".$item['line']."]\r\n";
        }
    }
    if($saveAsFile)file_put_contents(SITE_ROOT.'/data/debug/'.$saveAsFile,"[".strftime('%Y-%m-%d %H:%M:%S')."]\r\n$output\r\n",FILE_APPEND);
    else echo "<pre style=\"text-align:left;\">\r\n$output</pre>";
}