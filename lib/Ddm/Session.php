<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Session extends Ddm_Object {
	protected static $_instance = NULL;
	protected $_sessionSaveHandler = NULL;

	public function __construct(){
		//没有调用父类的构造函数
	}

	public function __destruct(){
		$this->toArray();
        session_write_close();
    }

	/**
	 * @return string
	 */
	protected function _getSessionSavePath(){
		$path = SITE_ROOT.'/data/session';
		is_dir($path) or mkdir($path,0755,true);
		return $path;
	}

	/**
	 * 使用单例模式
	 * @return Ddm_Session
	 */
	public static function singleton(){
		if(self::$_instance===NULL){
			self::$_instance = new Ddm_Session();
			self::$_instance->init();
		}
		return self::$_instance;
	}

	/**
	 * @param string $sessionName
	 * @return Ddm_Session
	 */
	public function start($sessionName = NULL){
		if(isset($_SESSION))return $this;

		$handler = $this->getSessionSaveHandler();
		if($handler=='files'){
			session_save_path($this->_getSessionSavePath());
		}else{
			$handler = $this->getSessionSaveHandler();
			$handlerObject = new $handler();
			$handlerObject->setSaveHandler();
		}

		$this->setSessionName($sessionName)->setSessionId();
		session_start();
		if(Ddm_Cookie::singleton()->getLifetime()){
			Ddm_Cookie::singleton()->set(session_name(),session_id());
		}

		return $this;
	}

	/**
	 * @param string $namespace
	 * @param string $sessionName
	 * @return Ddm_Session
	 */
	public function init($namespace = NULL,$sessionName = NULL){
		$namespace or $namespace = 'core';
		$sessionName or $sessionName = 'accessid';
		$this->start($sessionName);
		isset($_SESSION[$namespace]) or $_SESSION[$namespace] = array();
		$this->_data = &$_SESSION[$namespace];
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSessionSaveHandler(){
		if($this->_sessionSaveHandler===NULL){
			$this->_sessionSaveHandler = 'files';
			if(Ddm::isInstalled() && Ddm::getConfig()->getConfigValue('session/save_handler')){
				$this->_sessionSaveHandler = Ddm::getConfig()->getConfigValue('session/save_handler');
			}
		}
		return $this->_sessionSaveHandler;
	}

	/**
	 * @param string $sessionName
	 * @return Ddm_Session
	 */
	public function setSessionName($sessionName){
		if($sessionName){
			$prefix = '';
			if(Ddm::isInstalled()){
				$prefix = Ddm::getConfig()->getConfigValue(Ddm_Cookie::CONFIG_PATH_COOKIE_PREFIX);
			}
			session_name($prefix.$sessionName);
		}
		return $this;
	}

	/**
	 * @param string $sessionId
	 * @return Ddm_Session
	 */
	public function setSessionId($sessionId = NULL){
		if($sessionId===NULL && isset($_GET['_SID']))$sessionId = $_GET['_SID'];
		if($sessionId && preg_match('/^[0-9a-zA-Z,-]+$/',$sessionId)){
			session_id($sessionId);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSessionId(){
		return session_id();
	}
}
