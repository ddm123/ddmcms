<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Notice{
	protected $_cookieVarname = 'notice';
	protected $_cookieLifetime = 600;
	protected $_notices = array();

	/**
	 * @param bool $getCookie
	 */
	public function __construct($getCookie = true){
		if($getCookie)$this->_getNoticesFromCookie();
	}

	/**
	 * @param string $key
	 * @param string $str
	 * @return Ddm_Notice
	 */
	protected function _addNotices($key,$str){
		if(isset($this->_notices[$key]))$this->_notices[$key][] = $str;
		else $this->_notices[$key] = array($str);
		return $this;
	}

	/**
	 * @return array
	 */
	protected function _getNoticesFromCookie(){
		$notices = Ddm_Cookie::singleton()->get($this->_cookieVarname);
		if($notices){
			Ddm_Cookie::singleton()->delete($this->_cookieVarname,NULL,NULL,NULL,true);
			if($notices = unserialize(Ddm_String::singleton()->base64Decode($notices)))$this->_notices = $notices;
		}
		return $this->_notices;
	}

	/**
	 * @return array
	 */
	public function getNotices(){
		return $this->_notices;
	}

	/**
	 * @param string $str
	 * @return Ddm_Notice
	 */
	public function addNotice($str){
		$this->_addNotices('notices',$str);
		return $this;
	}

	/**
	 * @param string $str
	 * @return Ddm_Notice
	 */
	public function addSuccess($str){
		$this->_addNotices('success',$str);
		return $this;
	}

	/**
	 * @param string $str
	 * @return Ddm_Notice
	 */
	public function addError($str){
		$this->_addNotices('errors',$str);
		return $this;
	}

	/**
	 * @param string $str
	 * @return Ddm_Notice
	 */
	public function addWarning($str){
		$this->_addNotices('warnings',$str);
		return $this;
	}

	/**
	 * @param string $str
	 * @return Ddm_Notice
	 */
	public function save(){
		if($this->_notices && !headers_sent()){
			$notices = Ddm_String::singleton()->base64Encode(serialize($this->_notices));
			Ddm_Cookie::singleton()->set($this->_cookieVarname,$notices,$this->_cookieLifetime,NULL,NULL,NULL,true);
			$this->_notices = array();
		}
		return $this;
	}

	/**
	 * @param array $notices
	 * @return string
	 */
	public function toHtml(array $notices = NULL){
		$notices or $notices = $this->_notices;
		$_html = '';
		if($notices){
			$_html = '<ul class="notices__">';
			foreach($notices as $key=>$value){
				$_html .= '<li class="'.$key.'"><ul>';
				foreach($value as $msg)$_html .= '<li>'.$msg.'</li>';
				$_html .= '</ul></li>';
			}
			$_html .= '</ul>';
		}
		return $_html;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toHtml();
    }
}
