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
	protected $_prefix = '';

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
	 * @return string|null
	 */
	public function getDomain(){
		if($this->_domain===NULL){
			$this->_domain = array('value' => Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_DOMAIN));
		}
		return $this->_domain['value'];
	}

	/**
	 * Set cookie domain
	 * @param string $domain
	 * @return Ddm_Cookie
	 */
	public function setDomain($domain){
		if($this->_domain)$this->_domain['value'] = $domain;
		else $this->_domain = array('value' => $domain);
		return $this;
	}

	/**
	 * Retrieve Path for cookie
	 * @return string|null
	 */
	public function getPath(){
		if($this->_path===NULL){
			$this->_path = array('value' => Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_PATH));
		}
		return $this->_path['value'];
	}

	/**
	 * Set cookie path
	 * @param string $path
	 * @return Ddm_Cookie
	 */
	public function setPath($path){
		if($this->_path)$this->_path['value'] = $path;
		else $this->_path = array('value' => $path);
		return $this;
	}

	/**
	 * Retrieve cookie lifetime
	 * @return int|null
	 */
	public function getLifetime(){
		if($this->_lifetime===NULL){
			$lifetime = Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_LIFETIME);
			if($lifetime==='' || $lifetime===NULL)$lifetime = NULL;
			else $lifetime = (int)$lifetime * 60;
			$this->_lifetime = array('value' => $lifetime);
		}
		return $this->_lifetime['value'];
	}

	/**
	 * Set cookie lifetime
	 *
	 * @param int $lifetime seconds
	 * @return Ddm_Cookie
	 */
	public function setLifetime($lifetime){
		if($this->_lifetime)$this->_lifetime['value'] = $lifetime;
		else $this->_lifetime = array('value' => $lifetime);
		return $this;
	}

	/**
	 * Retrieve use HTTP only flag
	 *
	 * @return bool|null
	 */
	public function getHttponly(){
		if($this->_httponly===NULL){
			$httponly = Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_HTTPONLY);
			if($httponly==='' || $httponly===NULL)$httponly = NULL;
			else $httponly = (bool)$httponly;
			$this->_httponly = array('value' => $httponly);
		}
		return $this->_httponly['value'];
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
	 * @return bool|null
	 */
	public function isSecure(){
		return $this->_isSecure;
	}

	/**
	 * Set cookie secure
	 *
	 * @param bool $secure
	 * @return Ddm_Cookie
	 */
	public function setSecure($secure){
		$this->_isSecure = $secure===NULL ? NULL : (bool)$secure;
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
		setcookie($name, $value, $expire, (string)$path, (string)$domain, (bool)$secure, (bool)$httponly);
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
		setcookie($name, '', Ddm_Request::server()->REQUEST_TIME-3600, (string)$path, (string)$domain, (bool)$secure, (bool)$httponly);
		unset($_COOKIE[$name]);
		return $this;
	}
}
