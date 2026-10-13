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

	/** @var PDOStatement */
	private $_saveStatement = NULL;

	/** @var PDOStatement */
	private $_loadStatement = NULL;

	public function __construct(array $options){
		$this->_options = array_merge($this->_options, $options);
		$path = dirname(SITE_ROOT.'/'.$this->_options['db_file']);
		if(!is_dir($path) && !@mkdir($path,$this->_options['dir_mode'],true) && !is_dir($path)){
			throw new Exception('Unable to create cache directory: '.$path);
		}
		$this->getConnection();
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
			$isExistsDb = is_file(SITE_ROOT.'/'.$this->_options['db_file']);
			$this->_SQLite = new PDO('sqlite:'.SITE_ROOT.'/'.$this->_options['db_file'], NULL, NULL, array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_EMULATE_PREPARES => false
			));
			$isExistsDb or $this->_buildStructure($this->_SQLite);

			$this->_saveStatement = $this->_SQLite->prepare('REPLACE INTO cache(id,content,expire) VALUES(:id,:content,:expire)');
			$this->_loadStatement = $this->_SQLite->prepare('SELECT content FROM cache WHERE id = :id');
		}
		return $this->_SQLite;
	}

	/**
	 * param string $id
	 * @param string $data
	 * @param array $tags
	 * @param int $lifetime
	 * @return bool
	 */
	public function save($id, $data, array $tags = NULL, $lifetime = 0){
		$this->_saveStatement->execute(array(':id'=>$id,':content'=>$data,':expire'=>$lifetime ? $lifetime+$_SERVER['REQUEST_TIME'] : 0));
		if($tags)$this->_saveTagIds($id,$tags);
		return $this->_saveStatement->rowCount()>0;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function load($id){
		$this->_loadStatement->execute(array(':id'=>$id));
		$result = $this->_loadStatement->fetchColumn();
		$this->_loadStatement->closeCursor();
		return $result;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function remove($id){
		$rid = $rtag = false;
		$rid = $this->_exec("DELETE FROM cache WHERE id=?", array($id));
		$rtag = $this->_exec("DELETE FROM tag WHERE id=?", array($id));
		return $rid && $rtag;
	}

	/**
	 * @param array $tags
	 * @return bool
	 */
	public function removeByTags(array $tags){
		$rid = $rtag = false;
		if($ids = $this->getIdByTags($tags)){
			$rid = $this->_exec("DELETE FROM cache WHERE id IN(".$this->_getPlaceHolders($ids).")", array_values($ids));
			$rtag = $this->_exec("DELETE FROM tag WHERE name IN(".$this->_getPlaceHolders($tags).")", array_values($tags));
		}
		return $rid && $rtag;
	}

	/**
	 * @return bool
	 */
	public function clear(){
		//$this->_exec('DROP INDEX tag_name_id');
		//$this->_exec('DROP INDEX tag_id_index');
		$this->_exec("DROP TABLE cache");
		$this->_exec("DROP TABLE tag");
		$this->_buildStructure($this->_SQLite);
		return true;
	}

	/**
	 * @param array $tags
	 * @return array
	 */
	public function getIdByTags(array $tags){
		$ids = array();
		if($tags){
			$stmt = $this->_SQLite->prepare(
				"SELECT DISTINCT(id) AS id FROM tag WHERE name IN(".$this->_getPlaceHolders($tags).")"
			);
			$stmt->execute(array_values($tags));
			while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$ids[$row[0]] = $row[0];
			}
			$stmt->closeCursor();
		}
		return $ids;
	}

	/**
	 * @return array
	 */
	public function getAllTags(){
		$sth = $this->_SQLite->prepare("SELECT DISTINCT(name) AS name FROM tag");
		$sth->execute();
		$tags = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
		$sth->closeCursor();
		return $tags;
	}

	/**
	 * @param array $value
	 * @return string
	 */
	protected function _getPlaceHolders(array $value){
		$len = count($value);
		if($len===1)return '?';
		if($len===2)return '?,?';

		return '?' . str_repeat(',?', $len - 1);
	}

	/**
	 * @param string $id
	 * @param array $tags
	 * @return Ddm_Cache_Sqlite
	 */
	protected function _saveTagIds($id, array $tags){
		foreach($tags as $tag){
			$this->_exec("INSERT OR IGNORE INTO tag(name,id) VALUES (?,?)", array($tag, $id));
		}
		return $this;
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return int
	 */
	protected function _exec($sql, array $params = array()){
		if($params){
			$sth = $this->_SQLite->prepare($sql);
			$sth->execute($params);
			return $sth->rowCount();
		}

		return $this->_SQLite->exec($sql);
	}

	/**
	 * @param PDO $sqlite
	 * @return Ddm_Cache_Sqlite
	 */
	private function _buildStructure($sqlite){
        $sqlite->exec('CREATE TABLE cache (id VARCHAR(255) PRIMARY KEY, content BLOB,expire INT(10))');
        $sqlite->exec('CREATE TABLE tag (name VARCHAR(255), id VARCHAR(255))');
        $sqlite->exec('CREATE UNIQUE INDEX tag_name_id ON tag(name,id)');
        $sqlite->exec('CREATE INDEX tag_id_index ON tag(id)');
		return $this;
    }
}
