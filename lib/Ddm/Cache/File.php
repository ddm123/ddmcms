<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Cache_File implements Ddm_Cache_Interface {
	protected $_options = array('cache_dir'=>'data/cache/data-cache','dir_mode'=>0755);

	private $_ids = array();

	public function __construct(array $options){
		$this->_options = array_merge($this->_options, $options);
	}

	/**
	 * param string $id
	 * @param string $data
	 * @param array $tags
	 * @param int $lifetime
	 * @return bool
	 */
	public function save($id, $data, array $tags = NULL, $lifetime = 0){
		$result = Ddm::getHelper('core')->saveFile($this->_getCacheFile($id), $data, LOCK_EX);
		if($tags){
			$nextDir = md5($id);$nextDir = $nextDir[0];
			foreach($tags as $tag){
				$_path = SITE_ROOT."/{$this->_options['cache_dir']}/tags/$tag/$nextDir";
				$_file = "$_path/$id";
				if(!is_dir($_path))mkdir($_path,$this->_options['dir_mode'],true);
				if(!is_file($_file))Ddm::getHelper('core')->saveFile($_file, $lifetime, LOCK_EX);
			}
		}
		return (bool)$result;
	}

	/**
	 * @param string $id
	 * @return string|false
	 */
	public function load($id){
		$file = $this->_getCacheFile($id);
		return is_file($file) ? file_get_contents($file) : false;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function remove($id){
		$file = $this->_getCacheFile($id);
		$result = true;
		if(is_file($file))$result = unlink($file);
		return $result;
	}

	/**
	 * @param array $tags
	 * @return bool
	 */
	public function removeByTags(array $tags){
		$_path = SITE_ROOT."/{$this->_options['cache_dir']}/tags";
		foreach($tags as $tag){
			$path = "$_path/$tag";
			if(is_dir($path) && ($handle = opendir($path))){
				while(false!==($file = readdir($handle))){
					if($file!='.' && $file!='..' && ($handle2 = opendir("$path/$file"))){
						while(false!==($file2 = readdir($handle2))){
							if($file2!='.' && $file2!='..'){
								$this->remove($file2);
								unlink("$path/$file/$file2");
							}
						}
						closedir($handle2);
					}
				}
				closedir($handle);
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function clear(){
		$this->_deleteFile(SITE_ROOT.'/'.$this->_options['cache_dir']);
		return true;
	}

	/**
	 * @param array $tags
	 * @return array
	 */
	public function getIdByTags(array $tags){
		$ids = array();
		$_path = SITE_ROOT."/{$this->_options['cache_dir']}/tags";
		foreach($tags as $tag){
			$path = "$_path/$tag";
			if(is_dir($path) && ($handle = opendir($path))){
				while(false!==($file = readdir($handle))){
					if($file!='.' && $file!='..' && ($handle2 = opendir("$path/$file"))){
						while(false!==($file2 = readdir($handle2))){
							if($file2!='.' && $file2!='..')$ids[$file2] = $file2;
						}
						closedir($handle2);
					}
				}
				closedir($handle);
			}
		}
		return $ids;
	}

	/**
	 * @return array
	 */
	public function getAllTags(){
		$tags = array();
		$_path = SITE_ROOT."/{$this->_options['cache_dir']}/tags";
		if(is_dir($_path) && ($handle = opendir($_path))){
			while(false!==($file = readdir($handle))){
				if($file!='.' && $file!='..' && is_dir("$_path/$file"))$tags[] = $file;
			}
			closedir($handle);
		}
		return $tags;
	}

	/**
	 * @return string
	 */
	protected function _getCacheFile($id){
		if(!isset($this->_ids[$id])){
			$hashKey = md5($id);
			$this->_ids[$id] = SITE_ROOT."/{$this->_options['cache_dir']}/$hashKey[0]$hashKey[1]/$hashKey[2]";
			is_dir($this->_ids[$id]) or mkdir($this->_ids[$id],$this->_options['dir_mode'],true);
			$this->_ids[$id] .= "/$id";
		}
		return $this->_ids[$id];
	}

	/**
	 * @param string $path
	 * @return Ddm_Cache_File
	 */
	protected function _deleteFile($path){
		if($handle = opendir($path)){
			while(false!==($file = readdir($handle))){
				if($file!='.' && $file!='..'){
					if(is_dir("$path/$file")){
						$this->_deleteFile("$path/$file");
					}else{
						unlink("$path/$file");
					}
				}
			}
			closedir($handle);
			rmdir($path);
		}
		return $this;
	}
}
