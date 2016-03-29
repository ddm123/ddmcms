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

	private $_SQLite = NULL;
	private $_beginTransaction = false;

	public function __construct(array $options){
		$this->_options = array_merge($this->_options, $options);
		$path = dirname(SITE_ROOT.'/'.$this->_options['db_file']);
		is_dir($path) or mkdir($path,$this->_options['dir_mode'],true);
		$this->getConnection();
	}

	public function __destruct() {
		$this->commit();
	}

	/**
	 * @return PDO
	 */
	public function getConnection(){
		if($this->_SQLite===NULL){
			$isExistsDb = is_file(SITE_ROOT.'/'.$this->_options['db_file']);
			$this->_SQLite = new PDO('sqlite:'.SITE_ROOT.'/'.$this->_options['db_file']);
			$isExistsDb or $this->_buildStructure($this->_SQLite);
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
		$result = $this->_exec("REPLACE INTO cache(id,content,expire) VALUES(".$this->_SQLite->quote($id).",".$this->_SQLite->quote($data).",".($lifetime ? $lifetime+$_SERVER['REQUEST_TIME'] : 0).")");
		if($tags)$this->_saveTagIds($id,$tags);
		return $result;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function load($id){
		$sth = $this->_SQLite->query("SELECT content FROM cache WHERE id=".$this->_SQLite->quote($id));
		return $sth ? $sth->fetchColumn() : false;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function remove($id){
		$this->beginTransaction();
		$rid = $rtag = false;
		$rid = $this->_exec("DELETE FROM cache WHERE id=".$this->_SQLite->quote($id)) and $rtag = $this->_exec("DELETE FROM tag WHERE id=".$this->_SQLite->quote($id));
		return $rid && $rtag;
	}

	/**
	 * @param array $tags
	 * @return bool
	 */
	public function removeByTags(array $tags){
		$this->beginTransaction();
		$rid = $rtag = false;
		if($ids = $this->getIdByTags($tags)){
			$rid = $this->_exec("DELETE FROM cache WHERE id IN(".$this->_getArrayValues($ids).")")
				and $rtag = $this->_exec("DELETE FROM tag WHERE name IN(".$this->_getArrayValues($tags).")");
		}
		return $rid && $rtag;
	}

	/**
	 * @return bool
	 */
	public function clear(){
		$this->commit();
		$this->_exec('DROP INDEX tag_name_id');
		$this->_exec('DROP INDEX tag_id_index');
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
			$sth = $this->_SQLite->query("SELECT DISTINCT(id) AS id FROM tag WHERE name IN(".$this->_getArrayValues($tags).")");
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
		$sth = $this->_SQLite->query("SELECT DISTINCT(name) AS name FROM tag");
		foreach($sth as $row){
			$tags[] = $row['name'];
		}
		return $tags;
	}

	/**
	 * @param array $array
	 * @return string
	 */
	protected function _getArrayValues(array $array){
		$values = '';
		foreach($array as $value){
			$values=='' or $values .= ',';
			$values .= $this->_SQLite->quote($value);
		}
		return $values;
	}

	/**
	 * @param string $id
	 * @param array $tags
	 * @return Ddm_Cache_Sqlite
	 */
	protected function _saveTagIds($id, array $tags){
		foreach($tags as $tag){
			$this->_exec("INSERT OR IGNORE INTO tag(name,id) VALUES (".$this->_SQLite->quote($tag).",".$this->_SQLite->quote($id).")");
		}
		return $this;
	}

	/**
	 * @param string $sql
	 * @return bool
	 */
	protected function _exec($sql){
		$result = $this->_SQLite->exec($sql);
		if(false===$result){
			$arr = $this->_SQLite->errorInfo();
			$this->rollBack();
			throw new Exception($arr[2]."\r\nSQL:$sql");
		}
		return $result;
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
