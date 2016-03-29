<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Request {
	protected static $_instance = NULL;
	protected static $_server = NULL;
	protected $_params = array();

	//预设一些验证正则表达式
	private $_verify = array(
		'int'=>'/^[\-\+]?\d+$/',
		'float'=>'/^[\-\+]?\d+(\.\d+)?$/',
		'email'=>'/^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$/i',
		'legalstr'=>"/[^~!#\$%\^&\*\(\),=\+\\\|'\"\s\/]+/",
		'tel'=>'0\d{2,3}-\d{7,8}|\(0\d{2,3}\)\d{7,8}'
	);

	public function __construct() {
		$this->_params['private'] = array();
		$this->_params['public'] = array();
	}

	/**
	 * 使用单例模式
	 * @return Ddm_Request
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Ddm_Request()) : self::$_instance;
    }

	/**
	 * 获取$_GET数组某元素的值, 如果$isNum参数为false, 则会直接返回原值, 例如:Is your name O'reilly?不会返回Is your name O\'reilly?";
	 * @param string $key
	 * @param bool|string $isNum 是否需要是数字还是使用预设的正则或自定义一个正则来验证
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($key,$isNum = false,$default = ''){
		return self::singleton()->getParam($key,'get',$isNum,$default);
	}

	/**
	 * 获取$_POST数组某元素的值, 如果$isNum参数为false, 则会直接返回原值, 例如:Is your name O'reilly?不会返回Is your name O\'reilly?";
	 * @param string $key
	 * @param bool|string $isNum 是否需要是数字还是使用预设的正则或自定义一个正则来验证
	 * @param mixed $default
	 * @return mixed
	 */
	public static function post($key,$isNum = false,$default = ''){
		return self::singleton()->getParam($key,'post',$isNum,$default);
	}

	/**
	 * 其实就是调用PHP的addslashes()函数, 区别是该方法支持数组甚至多维数组
	 * @param mixed $value
	 * @return mixed
	 */
	public static function addslashes($value){
		if(is_array($value)){
			foreach($value as $key=>$val)$value[$key] = self::addslashes($val);
		}else{
			$value = "'".addslashes($value)."'";
		}
		return $value;
	}

	/**
	 * 其实就是调用PHP的stripslashes()函数, 区别是该方法支持数组甚至多维数组
	 * @param mixed $value
	 * @return mixed
	 */
	public function stripslashes($value){
		if(is_array($value)){
			foreach($value as $k=>$v)$value[$k] = $this->stripslashes($v);
		}else{
			$value = stripslashes($value);
		}
		return $value;
	}

	/**
	 * Url重定向
	 * @param string $url
	 * @param int $redirectCode 默认302跳转
	 */
	public static function redirect($url,$redirectCode = 302){
		if($redirectCode!=301)$redirectCode = 302;
		if(headers_sent($filename, $linenum)){
			//throw new Exception("Headers already sent in $filename on line $linenum, Cannot redirect");
			echo '<script type="text/javascript"> window.location.href = "'.$url.'"; </script>';
		}else{
			header('Location: '.$url, true, $redirectCode);
			header('HTTP/1.1 '.$redirectCode);
		}
	}

	/**
	 * 如果参数为一个数组且不想保留不符合条件的元素，可将$default赋值为NULL
	 */
	public function verify($value,$isNum = false,$default = ''){
		if(is_array($value)){
			foreach($value as $key=>$val){
				$value[$key] = $this->verify($val,$isNum,$default);
				if($value[$key]===NULL)unset($value[$key]);
			}
		}else if((string)$value!==''){
			$value = $this->check($value,$isNum,$default);
		}else{
			$value = $default;
		}
		return $value;
	}

	/**
	 * 获取$_GET/$_POST或自定义的值
	 * @param string $key
	 * @param string $method 'get' or 'post'
	 * @param bool|string $isNum
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParam($key,$method = 'get',$isNum = false,$default = NULL){
		if(isset($this->_params['public'][$key])){
			return isset($this->_params['private']["$key-public-$isNum"]) ? $this->_params['private']["$key-public-$isNum"] : ($this->_params['private']["$key-public-$isNum"] = $this->verify($this->_params['public'][$key],$isNum,$default));
		}else if(isset($this->_params['private']["$key-$method-$isNum"]))return $this->_params['private']["$key-$method-$isNum"];
		else{
			$param = $method=='post' ? $_POST : $_GET;
			if(isset($param[$key])){
				if(GET_MQG)$param[$key] = $this->stripslashes($param[$key]);
				return $this->_params['private']["$key-$method-$isNum"] = $this->verify($param[$key],$isNum,$default);
			}else return $default;
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return Ddm_Request
	 */
	public function setParam($key,$value){
		$this->_params['public'][$key] = $value;
		return $this;
	}

	/**
	 * @param string|null $key
	 * @return Ddm_Request
	 */
	public function removeParam($key){
		if($key===NULL){
			$this->_params['private'] = array();
			$this->_params['public'] = array();
		}else{
			if(isset($this->_params['public'][$key]))unset($this->_params['public'][$key]);
			foreach($this->_params['private'] as $k=>$v){
				if(stripos($k,"$key-")===0)unset($this->_params['private'][$k]);
			}
		}
		return $this;
	}

	/**
	 * @param bool $includeGET
	 * @param bool $includePOST
	 * @return array
	 */
	public function getParams($includeGET = true,$includePOST = true){
		$p = $this->_params['public'];
		if($includeGET && $_GET)$p = array_merge($_GET,$p);
		if($includePOST && $_POST)$p = array_merge($_POST,$p);
		return $p;
	}

	public function check($value,$isNum = false,$default = ''){
		if($value!=''){
			if(is_string($isNum)){
				$value = trim($value);
				if(isset($this->_verify[$isNum]))$isNum = $this->_verify[$isNum];
				if(!preg_match($isNum,$value))$value = $default;
			}else if($isNum && !is_numeric($value)){
				$value = $default;
			}
		}else if($value==='' || $value===NULL){
			$value = $default;
		}
		return $value;
	}

	/**
	 * @return Http_Server_Vars
	 */
	public static function server(){
		return self::$_server===NULL ? (self::$_server = new Http_Server_Vars($_SERVER)) : self::$_server;
	}
}

/**
 * @property string $PHP_SELF
 * @property array $argv
 * @property array $argc
 * @property string $GATEWAY_INTERFACE
 * @property string $SERVER_ADDR
 * @property string $SERVER_NAME
 * @property string $SERVER_SOFTWARE
 * @property string $SERVER_PROTOCOL
 * @property string $REQUEST_METHOD
 * @property int $REQUEST_TIME
 * @property string $QUERY_STRING
 * @property string $DOCUMENT_ROOT
 * @property string $HTTP_ACCEPT
 * @property string $HTTP_ACCEPT_CHARSET
 * @property string $HTTP_ACCEPT_ENCODING
 * @property string $HTTP_ACCEPT_LANGUAGE
 * @property string $HTTP_CONNECTION
 * @property string $HTTP_HOST
 * @property string $HTTP_REFERER
 * @property string $HTTP_USER_AGENT
 * @property string $HTTPS
 * @property string $REMOTE_ADDR
 * @property string $REMOTE_HOST
 * @property string $REMOTE_PORT
 * @property string $SCRIPT_FILENAME
 * @property string $SERVER_ADMIN
 * @property string $SERVER_PORT
 * @property string $SERVER_SIGNATURE
 * @property string $PATH_TRANSLATED
 * @property string $SCRIPT_NAME
 * @property string $REQUEST_URI
 * @property string $PHP_AUTH_DIGEST
 * @property string $PHP_AUTH_USER
 * @property string $PHP_AUTH_PW
 * @property string $AUTH_TYPE
 * @property string $PATH_INFO
 * @property string $ORIG_PATH_INFO
 * @property float $START_TIME
 */
class Http_Server_Vars extends Ddm_Object{
}