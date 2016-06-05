<?php

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Ddm_Session_File implements Ddm_Session_Interface {
	protected $_lifeTime = NULL;
	protected $_gcProbability = 30;
	protected $_savePath;
	protected $_sessions = array();
	protected $_sessionsInfo = array();
	protected $_sessionsInfoFileName = 'sessionsinfo.txt';
	protected $_sessionsInfoLockFileName = 'sessionsinfo.lock';

	/**
	 * @param string $id
	 * @return string
	 */
	protected function _getPath($id){
		$path = "/$id[0]/$id[1]";
		is_dir($this->_savePath.$path) or mkdir($this->_savePath.$path, 0755, true);
		return $path;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	protected function _getFile($id){
		return $this->_getPath($id)."/sess_$id";
	}

	/**
	 * @return array
	 */
	protected function _getSessionsInfoFromFile(){
		$sessionsInfo = array();
		if(is_file($this->_savePath.'/'.$this->_sessionsInfoFileName)){
			$sessionsInfo = unserialize(file_get_contents($this->_savePath.'/'.$this->_sessionsInfoFileName));
		}
		return $sessionsInfo;
	}

	/**
	 * @return Ddm_Session_File
	 */
	protected function _saveSessionsInfoToFile(){
		if($this->_sessionsInfo){
			$i = 0;
			if(file_exists($this->_savePath.'/'.$this->_sessionsInfoLockFileName) && filemtime($this->_savePath.'/'.$this->_sessionsInfoLockFileName)+30<$_SERVER['REQUEST_TIME']){
				unlink($this->_savePath.'/'.$this->_sessionsInfoLockFileName);
			}
			while(!($fp = @fopen($this->_savePath.'/'.$this->_sessionsInfoLockFileName,'x',false))){
				if(++$i>=20)return $this;
				usleep(500000);//500毫秒
			}
			fclose($fp);
			$origSessionsInfo = $this->_getSessionsInfoFromFile();
			foreach($this->_sessionsInfo as $id=>$data){
				if($data)$origSessionsInfo[$id] = $data;
				else unset($origSessionsInfo[$id]);
			}
			file_put_contents($this->_savePath.'/'.$this->_sessionsInfoFileName,serialize($origSessionsInfo));
			unlink($this->_savePath.'/'.$this->_sessionsInfoLockFileName);
		}
		return $this;
	}

	public function open($savePath, $sessionName){
		return (bool)$this->_savePath;
	}

	public function close(){
		$this->gc($this->getLifeTime());
		$this->_saveSessionsInfoToFile();
		//unset($this->_sessionsInfo,$this->_sessions);
		return true;
	}

	public function read($id){
		if(!isset($this->_sessions[$id])){
			$file = $this->_savePath.$this->_getFile($id);
			$this->_sessions[$id] = is_file($file) ? file_get_contents($file) : '';
		}
		return $this->_sessions[$id];
	}

	public function write($id, $data){
		$file = $this->_getFile($id);
		$this->_sessionsInfo[$id] = array($_SERVER['REQUEST_TIME'],$file);
		$this->_sessions[$id] = $data;
		return file_put_contents($this->_savePath.$file,$data)===false ? false : true;
	}

	public function destroy($id){
		$file = $this->_savePath.$this->_getFile($id);
		$this->_sessions[$id] = '';
		$this->_sessionsInfo[$id] = false;
		return is_file($file) && unlink($file);
	}

	public function gc($maxlifetime){
		if($this->_gcProbability<1 || ($this->_gcProbability!=1 && mt_rand(1,$this->_gcProbability)!=1))return true;

		$sessionsInfo = $this->_getSessionsInfoFromFile();
		foreach($sessionsInfo as $id=>$data){
			if($data[0] + $maxlifetime < $_SERVER['REQUEST_TIME']){
				if(is_file($file = $this->_savePath.$data[1]))unlink($file);
				$this->_sessions[$id] = '';
				$this->_sessionsInfo[$id] = false;
			}
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getLifeTime(){
		if($this->_lifeTime===NULL){
			$this->_lifeTime = Ddm::getConfig()->getConfigValue(Ddm_Cookie::CONFIG_PATH_COOKIE_LIFETIME)*60;
			if($this->_lifeTime<60){
				$this->_lifeTime = (int)ini_get('session.gc_maxlifetime') or $this->_lifeTime = 1440;
			}
		}
		return $this->_lifeTime;
	}

	public function setSaveHandler(){
		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc')
		);
		$path = SITE_ROOT.'/data/session';
		$this->_savePath = (is_dir($path) || mkdir($path,0755,true)) ? $path : false;
		return $this;
	}

}
