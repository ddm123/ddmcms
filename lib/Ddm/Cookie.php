<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Cookie {
	const CONFIG_PATH_COOKIE_DOMAIN = 'web/cookie/cookie_domain';
	const CONFIG_PATH_COOKIE_PATH = 'web/cookie/cookie_path';
	const CONFIG_PATH_COOKIE_LIFETIME = 'web/cookie/cookie_lifetime';
	const CONFIG_PATH_COOKIE_HTTPONLY = 'web/cookie/cookie_httponly';
	const CONFIG_PATH_COOKIE_PREFIX = 'web/cookie/cookie_prefix';

	protected static $_instance = NULL;
	protected $_lifetime = NULL;
	protected $_path = NULL;
	protected $_domain = NULL;
	protected $_isSecure = NULL;
	protected $_httponly = NULL;
	protected $_prefix = NULL;

	public function __construct() {
		$this->_prefix = (string)Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_PREFIX);
	}

	/**
	 * 使用单例模式
	 * @return Ddm_Cookie
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Ddm_Cookie()) : self::$_instance;
	}

	/**
	 * Retrieve Domain for cookie
	 * @return string
	 */
	public function getDomain(){
		if($this->_domain===NULL){
			$this->_domain = (string)Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_DOMAIN);
		}
		return $this->_domain;
	}

	/**
	 * Set cookie domain
	 * @param string domain
	 * @return Ddm_Cookie
	 */
	public function setDomain($domain){
		if($domain)$this->_domain = $domain;
		return $this;
	}

	/**
	 * Retrieve Path for cookie
	 * @return string
	 */
	public function getPath(){
		if($this->_path===NULL){
			$this->_path = Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_PATH);
			if(!$this->_path)$this->_path = '/';
		}
		return $this->_path;
	}

	/**
	 * Set cookie path
	 * @param string $path
	 * @return Ddm_Cookie
	 */
	public function setPath($path){
		if($path)$this->_path = $path;
		return $this;
	}

	/**
	 * Retrieve cookie lifetime
	 * @return int
	 */
	public function getLifetime(){
		if($this->_lifetime===NULL){
			$this->_lifetime = (int)Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_LIFETIME);
			$this->_lifetime *= 60;
		}
		return $this->_lifetime;
	}

	/**
	 * Set cookie lifetime
	 *
	 * @param int $lifetime
	 * @return Ddm_Cookie
	 */
	public function setLifetime($lifetime){
		$this->_lifetime = (int)$lifetime;
		return $this;
	}

	/**
	 * Retrieve use HTTP only flag
	 *
	 * @return bool
	 */
	public function getHttponly(){
		if($this->_httponly===NULL){
			$this->_httponly = (bool)Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_HTTPONLY);
		}
		return $this->_httponly;
	}

	/**
	 * Set cookie httponly
	 *
	 * @param bool $httponly
	 * @return Ddm_Cookie
	 */
	public function setHttponly($httponly){
		$this->_httponly = (bool)$httponly;
		return $this;
	}

	/**
	 * Is https secure request
	 * Use secure on adminhtml only
	 *
	 * @return bool
	 */
	public function isSecure(){
		return (bool)$this->_isSecure;
	}

	/**
	 * Set cookie secure
	 *
	 * @param bool $secure
	 * @return Ddm_Cookie
	 */
	public function setSecure($secure){
		$this->_isSecure = (bool)$secure;
		return $this;
	}

	/**
	 * Retrieve cookie or NULL if not exists
	 *
	 * @param string $name The cookie name
	 * @return mixed
	 */
	public function get($name){
		return isset($_COOKIE[$this->_prefix.$name]) ? $_COOKIE[$this->_prefix.$name] : NULL;
	}

	/**
	 * Set cookie
	 *
	 * @param string $name The cookie name
	 * @param string $value The cookie value
	 * @param int $lifetime
	 * @param string $path
	 * @param string $domain
	 * @param int|bool $secure
	 * @param bool $httponly
	 * @param string $prefix
	 * @return Ddm_Cookie
	 */
	public function set($name, $value, $lifetime = NULL, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL, $prefix = NULL){
		$name = ($prefix===NULL ? $this->_prefix : $prefix).$name;
		$lifetime===NULL and $lifetime = $this->getLifetime();
		$expire = $lifetime ? $lifetime+Ddm_Request::server()->REQUEST_TIME : 0;
		$path===NULL and $path = $this->getPath();
		$domain===NULL and $domain = $this->getDomain();
		$secure===NULL and $secure = $this->isSecure();
		$httponly===NULL and $httponly = $this->getHttponly();
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
		$_COOKIE[$name] = $value;
		return $this;
	}

	/**
	 * Delete cookie
	 *
	 * @param string $name
	 * @param string $path
	 * @param string $domain
	 * @param int|bool $secure
	 * @param int|bool $httponly
	 * @return Ddm_Cookie
	 */
	public function delete($name, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL){
		$name = $this->_prefix.$name;
		$path===NULL and $path = $this->getPath();
		$domain===NULL and $domain = $this->getDomain();
		$secure===NULL and $secure = $this->isSecure();
		$httponly===NULL and $httponly = $this->getHttponly();
		setcookie($name, '', Ddm_Request::server()->REQUEST_TIME-3600, $path, $domain, $secure, $httponly);
		unset($_COOKIE[$name]);
		return $this;
	}
}
