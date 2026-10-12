<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Session_Sqlite implements Ddm_Session_Interface {
	/** @var int */
	protected $_lifeTime = NULL;

	/** @var int */
	protected $_gcProbability = 30;

	/** @var PDO */
	protected $_pdo = NULL;

	/** @var PDOStatement */
	protected $_readStatement = NULL;

	/** @var PDOStatement */
	protected $_writeStatement = NULL;

	/** @var PDOStatement */
	protected $_destroyStatement = NULL;

	/** @var PDOStatement */
	protected $_gcStatement = NULL;

	/**
	 * @param string $savePath
	 * @param string $sessionName
	 * @return bool
	 */
	public function open($savePath, $sessionName){
		return (bool)$this->_pdo;
	}

	/**
	 * @return bool
	 */
	public function close(){
		$this->gc($this->getLifeTime());
		return true;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function read($id){
		$this->_readStatement->execute(array(':id'=>$id));
		$data = $this->_readStatement->fetchColumn();
		return $data===false ? '' : $data;
	}

	/**
	 * @param string $id
	 * @param string $data
	 * @return bool
	 */
	public function write($id, $data){
		$this->_writeStatement->bindValue(':id', $id, PDO::PARAM_STR);
		$this->_writeStatement->bindValue(':data', $data, PDO::PARAM_LOB);
		$this->_writeStatement->bindValue(':updated', $this->_getRequestTime(), PDO::PARAM_INT);
		return $this->_writeStatement->execute();
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function destroy($id){
		$this->_destroyStatement->execute(array(':id'=>$id));
		return $this->_destroyStatement->rowCount()>0;
	}

	/**
	 * @param int $maxlifetime
	 * @return bool
	 */
	public function gc($maxlifetime){
		if($this->_gcProbability<1 || ($this->_gcProbability!=1 && mt_rand(1,$this->_gcProbability)!=1))return true;
		$this->_gcStatement->bindValue(':expire', $this->_getRequestTime() - $maxlifetime, PDO::PARAM_INT);
		$this->_gcStatement->execute();
		return $this->_gcStatement->rowCount()>0;
	}

	/**
	 * @return int
	 */
	public function getLifeTime(){
		if($this->_lifeTime===NULL){
			$this->_lifeTime = (float)Ddm::getConfig()->getConfigValue(Ddm_Cookie::CONFIG_PATH_COOKIE_LIFETIME) * 60;
			if($this->_lifeTime<60){
				$this->_lifeTime = (int)ini_get('session.gc_maxlifetime');
				if(!$this->_lifeTime)$this->_lifeTime = 1440;
			}
		}
		return $this->_lifeTime;
	}

	/**
	 * @return Ddm_Session_Sqlite
	 */
	public function setSaveHandler(){
		$path = SITE_ROOT.'/data/session';
		if(!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)){
			throw new Exception('Unable to create session directory: '.$path);
		}

		$this->_pdo = new PDO('sqlite:'.$path.'/session.sqlite');
		$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->_pdo->exec('CREATE TABLE IF NOT EXISTS session (id VARCHAR(255) PRIMARY KEY, data BLOB, updated INTEGER NOT NULL)');
		$this->_pdo->exec('CREATE INDEX IF NOT EXISTS session_updated ON session(updated)');
		$this->_readStatement = $this->_pdo->prepare('SELECT data FROM session WHERE id = :id');
		$this->_writeStatement = $this->_pdo->prepare('REPLACE INTO session (id, data, updated) VALUES (:id, :data, :updated)');
		$this->_destroyStatement = $this->_pdo->prepare('DELETE FROM session WHERE id = :id');
		$this->_gcStatement = $this->_pdo->prepare('DELETE FROM session WHERE updated < :expire');

		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc')
		);
		return $this;
	}

	/**
	 * @return int
	 */
	protected function _getRequestTime(){
		return isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
	}
}
