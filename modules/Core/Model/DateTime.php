<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Model_DateTime extends DateTime {
	protected static $_now = NULL;
	protected $_timezone = NULL;
	protected $_timezoneName = NULL;
	protected $_defaultDateTimeZoneName = NULL;

	/**
	 * @param string $time
	 * @param DateTimeZone $timezone
	 */
	public function __construct($time = 'now',DateTimeZone $timezone = NULL){
		if(!$timezone)$timezone = $this->getDefaultDateTimeZoneName();
		parent::__construct($time,($timezone instanceof DateTimeZone) ? $timezone : new DateTimeZone($timezone));
	}

	/**
	 * @return string
	 */
	public static function now(){
		if(self::$_now===NULL){
			$d = new DateTime('now',$this->getTimezone());
			self::$_now = $d->format('Y-m-d H:i:s');
		}
		return self::$_now;
	}

	/**
	 * @param int $unixtimestamp
	 * @param string|DateTimeZone $timezone
	 * @return Core_Model_DateTime
	 */
	public static function getFromUnixtimestamp($unixtimestamp = NULL,$timezone = NULL){
		$_datetime = $timezone ? new self('now',$timezone) : new self('now');
		if(is_numeric($unixtimestamp))$_datetime->setTimestamp((int)$unixtimestamp);
		return $_datetime;
	}

	/**
	 * @param string|DateTimeZone $timezone
	 * @return Core_Model_DateTime
	 */
	public function setTimezone($timezone){
		$this->_timezone = ($timezone instanceof DateTimeZone) ? $timezone : new DateTimeZone($timezone);
		$this->_timezoneName = $this->_timezone->getName();
		parent::setTimezone($this->_timezone);
		return $this;
	}

	/**
	 * @return DateTimeZone
	 */
	public function getTimezone(){
		if($this->_timezone)return $this->_timezone;
		return $this->_timezone = parent::getTimezone();
	}

	/**
	 * @param int $unixtimestamp
	 * @return Core_Model_DateTime
	 */
	public function setTimestamp($unixtimestamp){
		$unixtimestamp = (int)$unixtimestamp;
		if(method_exists('DateTime','setTimestamp')){//如果是PHP5.3版本以上
			parent::setTimestamp($unixtimestamp);
		}else{
			$_date = new DateTime(date('c',$unixtimestamp));
			$_date->setTimezone($this->getTimezone());
			$dateTime = explode('|',$_date->format('Y|n|j|G|i|s'));
			$this->setDate($dateTime[0],$dateTime[1],$dateTime[2]);
			$this->setTime($dateTime[3],(int)$dateTime[4],(int)$dateTime[5]);
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTimestamp(){
		return method_exists('DateTime','getTimestamp') ? parent::getTimestamp() : $this->format('U');
	}

	/**
	 * @return string
	 */
	public function getDefaultDateTimeZoneName(){
		if($this->_defaultDateTimeZoneName===NULL){
			$this->_defaultDateTimeZoneName = Ddm::getConfig()->getConfigValue('system/date/timezone')
				or $this->_defaultDateTimeZoneName = date_default_timezone_get() or $this->_defaultDateTimeZoneName = 'Etc/UTC';
		}
		return $this->_defaultDateTimeZoneName;
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function date($format = NULL){
		$format or $format = Ddm::getConfig()->getConfigValue('system/date/date_format') or $format = 'Y-m-d';
		return $this->format($format);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function dateTime($format = NULL){
		$format or $format = Ddm::getConfig()->getConfigValue('system/date/datetime_format') or $format = 'Y-m-d H:i:s';
		return $this->format($format);
	}

	/**
	 * 作用和strtotime()一样, 失败时返回false
	 * @param string $time
	 * @return int|false 返回时间戳
	 */
	public function stringToTime($time = 'now',$c = true){
		try{
			$_date = new DateTime(strtr($time,'.','-'),$this->getTimezone());
			//$_date->setTimezone($this->getTimezone());
			$timestamp =  method_exists('DateTime','getTimestamp') ? $_date->getTimestamp() : $_date->format('U');
		}catch(Exception $e){
			$d = $c ? $this->_parseStringDate($time) : NULL;
			$timestamp = $d ? $this->stringToTime($d,false) : false;
		}
		return $timestamp;
	}

	/**
	 * @param int $date
	 * @return string
	 */
	protected function _parseStringDate($date,$y = 'Y',$m = 'm'){
		$result = NULL;
		$now = new DateTime('now',$this->getTimezone());

		if((string)(int)$date===(string)$date){
			$days = $now->format('t');
			if($date<=$days){
				$result = $now->format("$y-$m-$date");
			}else if($date<130){
				$d = explode('.',$date/10);
				$result = $now->format("$y-$d[0]-".(empty($d[1]) ? '1' : $d[1]));
			}else if($date>200 && $date<1300){
				$d = explode('.',$date/100);
				$result = $now->format("$y-$d[0]-".(empty($d[1]) ? '1' : (strlen($d[1])==1 ? $d[1]*10 : $d[1])));
			}else if($date>1310 && $date<991300){
				$result = $this->_parseStringDate(substr($date,2),substr($date,0,2));
			}else if($date>190010){
				$result = $this->_parseStringDate(substr($date,4),substr($date,0,4));
			}
		}else{
			if(strpos($date,':')){
				$d = preg_split('/(\s|T)/i',$date,2,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				$result = $this->_parseStringDate($d[0]).(isset($d[2]) ? $d[1].$d[2] : '');
			}else if($d = preg_split('/[^\d]+/',$date,-1,PREG_SPLIT_NO_EMPTY)){
				if(!isset($d[1])){
					$result = (int)$d[0] ? $now->format("Y-m-$d[0]") : NULL;
				}else if(!isset($d[2])){
					$result = (int)$d[0] && (int)$d[1] ? $now->format("Y-$d[0]-$d[1]") : NULL;
				}else if((int)$d[0] && (int)$d[1] && (int)$d[2]){
					$result = "$d[0]-$d[1]-$d[2]";
				}
			}
		}
		return $result;
	}

	public function __toString() {
		return $this->format('c');
	}
}
