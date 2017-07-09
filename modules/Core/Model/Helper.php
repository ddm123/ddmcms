<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Model_Helper {
	const ENTITY_ATTRIBUTE_CACHE_KEY = 'entity_attributes_';
	const CHARS_LOWERS = 'abcdefghijklmnopqrstuvwxyz';
	const CHARS_UPPERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const CHARS_DIGITS = '0123456789';
	const CHARS_SPECIALS = '!$*+-.=?@^_|~';

	private $_formKey = NULL;

	protected $_dateTime = NULL;
	protected $_attributes = array();
	protected $_entityAttributes = array();

	public function __construct() {
		//;
	}

	/**
	 * @return string
	 */
	public function getFormKey(){
		if($this->_formKey===NULL){
			$this->_formKey = Ddm::getSession()->form_key or Ddm::getSession()->setData('form_key',$this->_formKey = $this->getRandomString(8));
		}
		return $this->_formKey;
	}

	/**
	 * @return Core_Model_Helper
	 */
	public function destroyFormKey(){
		Ddm::getSession()->unsetData('form_key');
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRandomString($len, $chars = NULL){
		$chars or $chars = self::CHARS_LOWERS.self::CHARS_UPPERS.self::CHARS_DIGITS;
		mt_srand(10000000*(double)microtime());
		for($i = 0, $str = '', $lc = strlen($chars)-1; $i<$len; $i++){
			$str .= $chars[mt_rand(0, $lc)];
		}
		return $str;
	}

	/**
	 * 返回一个带单位的货币显示格式
	 * @param float $number
	 * @return string
	 */
	public function formatCurrency($number){
		return Ddm_String::singleton()->currency($number);
	}

	/**
	 * 把字节以一个友好的显示格式返回
	 * @param int $bytes
	 * @return string
	 */
	public function formatBytes($bytes){
		return Ddm_String::singleton()->formatBytes($bytes);
	}

	/**
	 * @param string $unixtimestamp
	 * @param string $format 和date()函数的第一参数是一样的
	 * @return string
	 */
	public function formatDate($unixtimestamp,$format = NULL){
		return $this->getDateTime()->setTimestamp($unixtimestamp)->date($format);
	}

	/**
	 * @param string $unixtimestamp
	 * @param string $format 和date()函数的第一参数是一样的
	 * @return string
	 */
	public function formatDateTime($unixtimestamp,$format = NULL){
		return $this->getDateTime()->setTimestamp($unixtimestamp)->dateTime($format);
	}

	/**
	 * @return Core_Model_DateTime
	 */
	public function getDateTime(){
		return $this->_dateTime===NULL ? ($this->_dateTime = new Core_Model_DateTime()) : $this->_dateTime;
	}

	/**
	 * 出于安全考虑, 保存一个文件时请使用该方法, 不要直接使用file_put_contents()函数
	 * @param string $filename
	 * @param mixed $data
	 * @param int $flags
	 * @param int $mode
	 * @return int
	 */
	public function saveFile($filename,$data,$flags = LOCK_EX,$mode = 0755){
		$path = dirname($filename);
		if($path && $path!='.'){
			if(strpos(strtr($path,'\\','/'),SITE_ROOT.'/data/')===0){
				($res = @file_put_contents($filename,$data,$flags)) or (mkdir($path,$mode,true) and $res = file_put_contents($filename,$data,$flags));
				if($res!==false)return $res;
			}
		}
		throw new Exception('This folder('.$path.') prohibit write!');
	}

	/**
	 * 删除一个非空文件夹
	 * @param string $path
	 * @return Core_Model_Helper
	 */
	public function deleteFolder($path){
		if($handle = opendir($path)){
			while(false!==($file = readdir($handle))){
				if($file!='.' && $file!='..'){
					if(is_dir("$path/$file")){
						$this->deleteFolder("$path/$file");
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

	/**
	 * 获取一个实体的属性模型
	 * @param string $entityType
	 * @param string $attributeCode
	 * @return Core_Model_Attribute
	 */
	public function getEntityAttribute($entityType,$attributeCode){
		if(!isset($this->_attributes["$entityType/$attributeCode"])){
			$this->_attributes["$entityType/$attributeCode"] = false;
			$data = $this->getEntityAttributes($entityType);
			if(isset($data[$attributeCode])){
				$this->_attributes["$entityType/$attributeCode"] = new Core_Model_Attribute();
				$this->_attributes["$entityType/$attributeCode"]->addData($data[$attributeCode])->setOrigData($data[$attributeCode],NULL,true);
			}
		}
		return $this->_attributes["$entityType/$attributeCode"];
	}

	/**
	 * 获取一个实体的全部属性
	 * @param array $entityType
	 * @return array
	 */
	public function getEntityAttributes($entityType){
		if(!isset($this->_entityAttributes[$entityType])){
			$this->_entityAttributes[$entityType] = Ddm_Cache::load(self::ENTITY_ATTRIBUTE_CACHE_KEY.$entityType);
			if($this->_entityAttributes[$entityType]===false){
				$this->_entityAttributes[$entityType] = Ddm_Db::getReadConn()->getSelect()->from(Ddm_Db::getTable('attribute'))->where('entity_type',$entityType)->order('`position` ASC')->fetchAll('attribute_code');
				Ddm_Cache::save(self::ENTITY_ATTRIBUTE_CACHE_KEY.$entityType,$this->_entityAttributes[$entityType],array('attribute'),0);
			}
		}
		return $this->_entityAttributes[$entityType];
	}

	/**
	 * 删除一个实体属性的缓存
	 * @param type $entityType
	 * @return Core_Model_Helper
	 */
	public function removeAttributesCache($entityType = NULL){
		if($entityType===NULL){
			foreach($this->_entityAttributes as $entityType=>$entityAttribute)Ddm_Cache::remove(self::ENTITY_ATTRIBUTE_CACHE_KEY.$entityType);
			$this->_entityAttributes = array();
		}else{
			if(isset($this->_entityAttributes[$entityType]))unset($this->_entityAttributes[$entityType]);
			Ddm_Cache::remove(self::ENTITY_ATTRIBUTE_CACHE_KEY.$entityType);
		}
		$this->_attributes = array();

		return $this;
	}
}
