<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Uploader {
	protected $_file = array();
	protected $_path = '';
	protected $_createFolder = false;
	protected $_allowedExtensions = array();
	protected $_alloweMaxSize = 2097152;//2M
	protected $_error = array();

	public function __construct($FILES = NULL){
		if($FILES)$this->setFiles($FILES);
	}

	/**
	 * @return array
	 */
	public function getErrors(){
		return $this->_error;
	}

	/**
	 * @param string|array $FILES
	 * @return Ddm_Uploader
	 * @throws Exception
	 */
	public function setFiles($FILES){
		if(is_string($FILES)){//如果是表单的文件域的名称
			if(isset($_FILES[$FILES])){
				$this->_file = $_FILES[$FILES];
			}else{
				$this->_error[] = 'Undefined index: '.$FILES.' ($_FILES['.$FILES.'])';
			}
		}else if(is_array($FILES)){
			if(!isset($FILES['name'])){
				throw new Exception('Undefined index: \'name\' in $FILES');
			}else if(!isset($FILES['tmp_name'])){
				throw new Exception('Undefined index: \'tmp_name\' in $FILES');
			}
			$this->_file = $FILES;
		}
		return $this;
	}

	/**
	 * 保存到哪个文件夹
	 * @param string $path
	 * @return Ddm_Uploader
	 */
	public function setSavePath($path){
		$this->_path = rtrim($path,' /\\');
		return $this;
	}

	/**
	 * 允许上传哪些文件, 不要带".", 多个请使用数组格式
	 * @param array|string $extensions
	 * @return Ddm_Uploader
	 */
	public function setAllowedExtensions($extensions){
		if(is_array($extensions))$this->_allowedExtensions = $extensions;
		else $this->_allowedExtensions = array_merge($this->_allowedExtensions,explode(',',$extensions));
		return $this;
	}

	/**
	 * 允许上传多大的文件
	 * @param int $maxSize 单位是字节
	 * @return Ddm_Uploader
	 */
	public function setAlloweMaxSize($maxSize){
		$this->_alloweMaxSize = $maxSize+0;
		return $this;
	}

	/**
	 * 是否分文件夹存放
	 * @param bool $flag
	 * @return Ddm_Uploader
	 */
	public function isCreateFolder($flag){
		$this->_createFolder = $flag;
		return $this;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	public function getExtension($fileName){
		if(!$fileName || false===($i = strrpos($fileName,'.')))return false;
		return strtolower(substr($fileName,$i+1));
	}

	/**
	 * @param bool $createFolder 是否分文件夹存放
	 * @param type $newFilename 不含扩展名
	 * @param string $extension
	 * @param int $maxSize
	 * @return boolean|string|array 如果是多文件上传则返回一个数组, 如果单个文件返回一个文件路径, 如果查没上传到文件或不合法返回false
	 */
	public function save($createFolder = NULL, $newFilename = NULL, $extension = NULL, $maxSize = NULL){
		$result = false;
		if(!$this->_path){
			throw new Exception('需要指定上传的文件存放在哪个目录下');
		}
		if($this->_file){
			if(is_array($this->_file['tmp_name'])){
				$images = array();
				foreach($this->_file['tmp_name'] as $key=>$tmpName){
					if($file = $this->_saveFile($tmpName,$this->_file['name'][$key],$newFilename,isset($this->_file['size'][$key]) ? $this->_file['size'][$key] : NULL, $createFolder, $extension, $maxSize))$images[] = $file;
				}
				if($images)$result = $images;
			}else{
				$result = $this->_saveFile($this->_file['tmp_name'],$this->_file['name'],$newFilename,isset($this->_file['size']) ? $this->_file['size'] : NULL, $createFolder, $extension, $maxSize);
			}
		}
		return $result;
	}

	/**
     * Correct filename with special chars and spaces
     *
     * @param string $fileName
     * @return string
     */
    static public function getCorrectFileName($fileName){
        $fileName = preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $fileName);
        $fileInfo = pathinfo($fileName);

        if (preg_match('/^_+$/', $fileInfo['filename'])) {
            $fileName = 'file.' . $fileInfo['extension'];
        }
        return $fileName;
    }

	protected function _saveFile($file,$fileName,$newFilename = NULL,$fileSize = NULL,$createFolder = NULL, $extension = NULL, $maxSize = NULL){
		$maxSize===NULL and $maxSize = $this->_alloweMaxSize;
		$fileSize===NULL and $fileSize = filesize($file);
		if($fileSize<1)return false;
		if($maxSize>0 && $fileSize>$maxSize){
			$this->_error[] = '最大只允许上传 '.Ddm::getHelper('core')->formatBytes($maxSize);
			return false;
		}

		$createFolder===NULL and $createFolder = $this->_createFolder;
		if($newFilename)$newFilename = self::getCorrectFileName($newFilename);
		else $newFilename = $_SERVER['REQUEST_TIME'];
		if($extension===NULL)$extension = $this->_allowedExtensions;
		else if(is_string($extension)){
			$extension = array_merge($this->_allowedExtensions,explode(',',$extension));
		}
		if($extension && !$this->_checkExtension($extension,$ext = $this->getExtension($fileName))){
			$this->_error[] = "不允许 '.$ext' 格式的文件";
			return false;
		}

		$path = '';
		if($createFolder){
			$path = substr(str_pad($newFilename,2,'0'),-2);
			is_dir("$this->_path/$path") or mkdir("$this->_path/$path",0777,true);
		}

		if(move_uploaded_file($file,"$this->_path/".($path=='' ? "$newFilename.$ext" : "$path/$newFilename.$ext"))){
			return $path=='' ? "/$newFilename.$ext" : "/$path/$newFilename.$ext";
		}
		return false;
	}

	/**
	 * @param array $extensions
	 * @param string $extension
	 * @return boolean
	 */
	protected function _checkExtension(array $extensions,$extension){
		if(!$extension)return false;
		foreach($extensions as $_ext){
			if(strtolower($_ext)==$extension)return true;
		}
		return false;
	}
}