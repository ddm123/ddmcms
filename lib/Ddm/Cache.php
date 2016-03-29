<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Cache {
	protected static $_instance = NULL;
	protected $_cache = NULL;
	protected $_isLocked = array();
	protected $_prefix = '';
	protected $_lockKey = 'LOCK_SAVE_';

	public function __construct(){
		$this->getCache();
	}

	/**
	 * 使用单例模式
	 * @return Ddm_Cache
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Ddm_Cache()) : self::$_instance;
	}

	/**
	 * 保存存一条缓存数据
	 * @param string $id
	 * @param mixed $data
	 * @param array $tags
	 * @param ing $lifetime 如果是0则永不过期
	 * @return bool
	 */
	public static function save($id, $data, array $tags = NULL, $lifetime = 0){
		return self::singleton()->set($id, $data, $tags, $lifetime);
	}

	/**
	 * 读出一条缓存数据
	 * @param string $id
	 * @return mixed
	 */
	public static function load($id){
		return self::singleton()->get($id);
	}

	/**
	 * 删除一条缓存数据
	 * @param string $id
	 * @return Ddm_Cache
	 */
	public static function remove($id){
		return self::singleton()->delete($id);
	}

	/**
	 * 删除所有缓存数据
	 * @return bool
	 */
	public static function clear(){
		return self::singleton()->getCache()->clear();
	}

	/**
	 * @return Ddm_Cache_Interface
	 */
	public function getCache(){
		if($this->_cache===NULL){
			$config = Ddm::getConfig()->getXmlConfig();
			$cacheClass = !isset($config['cache']['driver']) || $config['cache']['driver']=='' ? 'Ddm_Cache_File' : $config['cache']['driver'];
			$this->_cache = new $cacheClass($config['cache']);
			$this->_prefix = isset($config['cache']['prefix']) ? $config['cache']['prefix'] : '';
		}
		return $this->_cache;
	}

	/**
	 * @param string $id
	 * @param mixed $data
	 * @param array $tags
	 * @param int $lifetime 如果是0则永不过期
	 * @return bool
	 */
	public function set($id, $data, array $tags = NULL, $lifetime = 0){
		$id = $this->_id($id,$this->_prefix);
		$tags = $this->_tag($tags ? $tags : array('no_tag'));
		$this->_unlock($id);
		return $this->_cache->save($id, $this->_prepareData($data, $lifetime), $tags, $lifetime);
	}

	/**
	 * @param string $id
	 * @return mixed|false
	 */
	public function get($id){
		$id = $this->_id($id,$this->_prefix);
		if(isset($this->_isLocked[$id]) && $this->_isLocked[$id])return false;

		if($result = $this->_cache->load($id) and $result = unserialize($result)){
			$result = !$result['lifetime'] || $result['expire']>Ddm_Request::server()->REQUEST_TIME || $this->isLocked($id) ? $result['data'] : false;
			$result===false and $this->_lock($id);//缓存已失效, 加锁, 只让一进程重新生成缓存, 其它进程继续使用原来的旧缓存
			return $result;
		}
		return false;
	}

	/**
	 * @param string $id
	 * @return Ddm_Cache
	 */
	public function delete($id){
		$id = $this->_id($id,$this->_prefix);
		$this->_cache->remove($id);
		return $this;
	}

	/**
	 * @param array $tags
	 * @return array
	 */
	public function getIdByTags(array $tags){
		$tags = $this->_tag($tags);
		return $this->_cache->getIdByTags($tags);
	}

	/**
	 * @param array $tags
	 * @return Ddm_Cache
	 */
	public function removeByTags(array $tags){
		$tags = $this->_tag($tags);
		$this->_cache->removeByTags($tags);
		return $this;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function isLocked($id){
		if(!isset($this->_isLocked[$id])){
			$isLocked = (int)$this->_cache->load($this->_lockKey.$id);
			return $isLocked ? Ddm_Request::server()->REQUEST_TIME-$isLocked<=100 : false;
		}
		return $this->_isLocked[$id];
	}

	/**
	 * @param string $id
	 * @param string $prefix
	 * @return string
	 */
	protected function _id($id,$prefix = ''){
		return strtoupper(preg_replace('/[^\w\-\.]/', '_', $prefix.$id));
	}

	/**
	 * Format tag name
	 * @param array $tags
	 * @return array
	 */
	protected function _tag(array $tags){
		foreach($tags as $k=>$tag){
			$tags[$k] = $this->_id($tag);
		}
		return $tags;
	}

	/**
	 * Format cache data
	 * @param mixed $data
	 * @param int $lifetime
	 * @return string
	 */
	private function _prepareData($data, $lifetime){
		return serialize(array(
			'data' => $data,
			'lifetime' => $lifetime,
			'expire' => Ddm_Request::server()->REQUEST_TIME + $lifetime
		));
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	private function _lock($id){
		$this->_isLocked[$id] = (bool)$this->_cache->save($this->_lockKey.$id,Ddm_Request::server()->REQUEST_TIME,array(),100);
		if(get_class($this->_cache)=='Ddm_Cache_Sqlite'){
			$this->_cache->commit();//马上生效
		}
		return $this->_isLocked[$id];
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	private function _unlock($id){
		if(isset($this->_isLocked[$id]) && $this->_isLocked[$id])$this->_cache->remove($this->_lockKey.$id);
		if(get_class($this->_cache)=='Ddm_Cache_Sqlite'){
			$this->_cache->commit();//马上生效
		}
		$this->_isLocked[$id] = false;
		return true;
	}
}
