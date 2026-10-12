<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Cache_Sqlite implements Ddm_Cache_Interface {
	protected $_options = array('db_file'=>'data/cache/data-cache/cache.sq3','dir_mode'=>0755);

	/** @var PDO */
	private $_SQLite = NULL;

	private $_beginTransaction = false;
	private $_statements = array();

	public function __construct(array $options){
		$this->_options = array_merge($this->_options, $options);
		$path = dirname(SITE_ROOT.'/'.$this->_options['db_file']);
		if(!is_dir($path) && !@mkdir($path,$this->_options['dir_mode'],true) && !is_dir($path)){
			throw new Exception('Unable to create cache directory: '.$path);
		}
		$this->getConnection();
	}

	public function __destruct() {
		$this->commit();
	}

	/**
	 * 返回绝对路径
	 * @return string
	 */
	public function getCacheDir(){
		return SITE_ROOT.DIRECTORY_SEPARATOR.$this->_options['db_file'];
	}

	/**
	 * @return PDO
	 */
	public function getConnection(){
		if($this->_SQLite===NULL){
			$this->_SQLite = new PDO('sqlite:'.SITE_ROOT.'/'.$this->_options['db_file']);
			$this->_SQLite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->_buildStructure($this->_SQLite);
			$this->_statements = array(
				'save' => $this->_SQLite->prepare('REPLACE INTO cache(id,content,expire) VALUES(:id,:content,:expire)'),
				'load' => $this->_SQLite->prepare('SELECT content FROM cache WHERE id = :id'),
				'remove' => $this->_SQLite->prepare('DELETE FROM cache WHERE id = :id'),
				'removeTagsById' => $this->_SQLite->prepare('DELETE FROM tag WHERE id = :id'),
				'removeOrphanTags' => $this->_SQLite->prepare('DELETE FROM tag WHERE id NOT IN(SELECT id FROM cache)'),
				'saveTag' => $this->_SQLite->prepare('INSERT OR IGNORE INTO tag(name,id) VALUES(:name,:id)'),
				'getAllTags' => $this->_SQLite->prepare('SELECT DISTINCT(name) AS name FROM tag'),
				'clearCache' => $this->_SQLite->prepare('DELETE FROM cache'),
				'clearTags' => $this->_SQLite->prepare('DELETE FROM tag')
			);
		}
		return $this->_SQLite;
	}

	/**
	 * @return bool
	 */
	public function beginTransaction(){
		if(!$this->_beginTransaction){
			$this->_beginTransaction = $this->_SQLite->beginTransaction();
		}
		return $this->_beginTransaction;
	}

	/**
	 * @return bool
	 */
	public function commit(){
		if($this->_beginTransaction){
			$this->_SQLite->commit();
			$this->_beginTransaction = false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function rollBack(){
		if($this->_beginTransaction){
			$this->_SQLite->rollBack();
			$this->_beginTransaction = false;
		}
		return true;
	}

	/**
	 * param string $id
	 * @param string $data
	 * @param array $tags
	 * @param int $lifetime
	 * @return bool
	 */
	public function save($id, $data, array $tags = NULL, $lifetime = 0){
		$this->beginTransaction();
		$requestTime = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
		$result = $this->_execute($this->_statements['save'], array(
			':id'=>$id,
			':content'=>$data,
			':expire'=>$lifetime ? $lifetime+$requestTime : 0
		));
		$this->_execute($this->_statements['removeTagsById'], array(':id'=>$id));
		if($tags)$this->_saveTagIds($id,$tags);
		return $result;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function load($id){
		$statement = $this->_statements['load'];
		$this->_execute($statement, array(':id'=>$id));
		$result = $statement->fetchColumn();
		return $result===false ? false : $result;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function remove($id){
		$this->beginTransaction();
		$this->_execute($this->_statements['remove'], array(':id'=>$id));
		$this->_execute($this->_statements['removeTagsById'], array(':id'=>$id));
		return true;
	}

	/**
	 * @param array $tags
	 * @return bool
	 */
	public function removeByTags(array $tags){
		if(!$tags)return true;
		$this->beginTransaction();
		$placeholders = $this->_getPlaceholders(count($tags));
		$statement = $this->_SQLite->prepare('DELETE FROM cache WHERE id IN(SELECT id FROM tag WHERE name IN('.$placeholders.'))');
		$this->_execute($statement, array_values($tags));
		$this->_execute($this->_statements['removeOrphanTags']);
		return true;
	}

	/**
	 * @return bool
	 */
	public function clear(){
		$this->commit();
		$this->_execute($this->_statements['clearCache']);
		$this->_execute($this->_statements['clearTags']);
		return true;
	}

	/**
	 * @param array $tags
	 * @return array
	 */
	public function getIdByTags(array $tags){
		$ids = array();
		if($tags){
			$placeholders = $this->_getPlaceholders(count($tags));
			$sth = $this->_SQLite->prepare('SELECT DISTINCT(id) AS id FROM tag WHERE name IN('.$placeholders.')');
			$this->_execute($sth, array_values($tags));
			foreach($sth as $row){
				$ids[$row['id']] = $row['id'];
			}
		}
		return $ids;
	}

	/**
	 * @return array
	 */
	public function getAllTags(){
		$tags = array();
		$statement = $this->_statements['getAllTags'];
		$this->_execute($statement);
		foreach($statement as $row){
			$tags[] = $row['name'];
		}
		return $tags;
	}

	/**
	 * @param int $count
	 * @return string
	 */
	protected function _getPlaceholders($count){
		$placeholders = array();
		for($i=0; $i<$count; $i++){
			$placeholders[] = '?';
		}
		return implode(',', $placeholders);
	}

	/**
	 * @param string $id
	 * @param array $tags
	 * @return Ddm_Cache_Sqlite
	 */
	protected function _saveTagIds($id, array $tags){
		$statement = $this->_statements['saveTag'];
		foreach($tags as $tag){
			$this->_execute($statement, array(':name'=>$tag,':id'=>$id));
		}
		return $this;
	}

	/**
	 * @param PDOStatement $statement
	 * @param array $parameters
	 * @return bool
	 */
	protected function _execute($statement, array $parameters = array()){
		try{
			return $statement->execute($parameters);
		}catch(PDOException $e){
			$this->rollBack();
			throw $e;
		}
	}

	/**
	 * @param PDO $sqlite
	 * @return Ddm_Cache_Sqlite
	 */
	private function _buildStructure($sqlite){
        $sqlite->exec('CREATE TABLE IF NOT EXISTS cache (id VARCHAR(255) PRIMARY KEY, content BLOB,expire INT(10))');
        $sqlite->exec('CREATE TABLE IF NOT EXISTS tag (name VARCHAR(255), id VARCHAR(255))');
        $sqlite->exec('CREATE UNIQUE INDEX IF NOT EXISTS tag_name_id ON tag(name,id)');
        $sqlite->exec('CREATE INDEX IF NOT EXISTS tag_id_index ON tag(id)');
		return $this;
    }
}
