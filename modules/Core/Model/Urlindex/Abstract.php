<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Model_Urlindex_Abstract {
	protected $_urlKeyAttribute = NULL;
	protected $_module = NULL;
	protected $_controller = NULL;
	protected $_action = NULL;
	protected $_params = NULL;

	public function __construct(){
		//;
	}

	/**
	 * @param Core_Model_Attribute $attribute
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function setUrlKeyAttribute(Core_Model_Attribute $attribute){
		$this->_urlKeyAttribute = $attribute;
		return $this;
	}

	/**
	 * @return Core_Model_Attribute
	 */
	public function getUrlKeyAttribute(){
		return $this->_urlKeyAttribute;
	}

	/**
	 * @param string $module
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function setModule($module){
		$this->_module = $module;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModule(){
		return $this->_module;
	}

	/**
	 * @param string $controller
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function setController($controller){
		$this->_controller = $controller;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getController(){
		return $this->_controller;
	}

	/**
	 * @param string $action
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function setAction($action){
		$this->_action = $action;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction(){
		return $this->_action;
	}

	/**
	 * @param array $params
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function setParams(array $params){
		$this->_params = $params;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getParams(){
		return $this->_params;
	}

	/**
	 * @param int $entityId
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function saveUrlIndex($entityId){
		if(!$this->getUrlKeyAttribute()){
			throw new Exception('Property \'_urlKeyAttribute\' is undefined.');
		}
		if(!$this->getModule()){
			throw new Exception('Property \'_module\' is undefined.');
		}

		if((int)$entityId){
			$urlKeys = Ddm_Db::getReadConn()
				->fetchPairs("SELECT language_id,`value` FROM ".$this->getUrlKeyAttribute()->getTable()." WHERE entity_id='".intval($entityId)."' AND attribute_id='".$this->getUrlKeyAttribute()->getId()."'");
			if($urlKeys){
				foreach(Ddm::getLanguage()->getAllLanguage(false) as $language){
					isset($urlKeys[$language['language_id']]) or $urlKeys[$language['language_id']] = $urlKeys[0];
				}
				if($params = $this->getParams()){
					foreach($params as $key=>$value){
						if($value=='{entity_id}'){
							$params[$key] = (int)$entityId;
						}
					}
				}
				Core_Model_Url::singleton()->saveUrl($urlKeys,$this->getModule(),$this->getController(),$this->getAction(),$params);
			}
		}
		return $this;
	}

	/**
	 * @param int $from
	 * @param int $to
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function saveUrlIndexFromCategoryId($from,$to){
		if(!$this->getUrlKeyAttribute()){
			throw new Exception('Property \'_urlKeyAttribute\' is undefined.');
		}
		if(!$this->getModule()){
			throw new Exception('Property \'_module\' is undefined.');
		}

		$from = (int)$from;
		$to = (int)$to;
		$sql = "SELECT CONCAT(entity_id,'-',language_id) AS `key`,entity_id,language_id,`value` FROM ".$this->getUrlKeyAttribute()->getTable()." WHERE ";
		$sql .= $from==$to ? "entity_id='$from'" : "entity_id BETWEEN $from AND $to";
		$sql .= " AND attribute_id='".$this->getUrlKeyAttribute()->getId()."'";
		$urlKeys = Ddm_Db::getReadConn()->fetchAll($sql,'key');
		if($urlKeys){
			$data = array();
			$allLanguages = Ddm::getLanguage()->getAllLanguage(true);
			$idField = NULL;
			if($params = $this->getParams()){
				foreach($params as $key=>$value){
					if($value=='{entity_id}'){
						$idField = $key;
						break;
					}
				}
				if(!$idField)$params = serialize($params);
			}
			foreach($urlKeys as $_urlKey){
				foreach($allLanguages as $language){
					if(isset($urlKeys[$_urlKey['entity_id'].'-'.$language['language_id']])){
						$urlKey = $urlKeys[$_urlKey['entity_id'].'-'.$language['language_id']]['value'];
					}else if(isset($urlKeys[$_urlKey['entity_id'].'-0'])){
						$urlKey = $urlKeys[$_urlKey['entity_id'].'-0']['value'];
					}else{
						$urlKey = $_urlKey['value'];
					}
					if($urlKey){
						if($idField){
							$params = $this->getParams();
							$params[$idField] = $_urlKey['entity_id'];
							$params = serialize($params);
						}
						$data[] = array(
							'url_path'=>$this->getModule()."/$urlKey",
							'language_id'=>$language['language_id'],
							'module'=>(string)$this->getModule(),
							'controller'=>(string)$this->getController(),
							'action'=>(string)$this->getAction(),
							'params'=>(string)$params
						);
					}
				}
			}
			if($data){
				Ddm_Db::getWriteConn()->insertMultiple(Core_Model_Url::singleton()->getMainTable(),$data,array('module','controller','action','params'));
			}
		}

		return $this;
	}

	/**
	 * @param bool $checkCache
	 * @return Core_Model_Urlindex_Abstract
	 * @throws Exception
	 */
	public function clearUrlIndex($checkCache = true){
		if(!$this->getUrlKeyAttribute()){
			throw new Exception('Property \'_urlKeyAttribute\' is undefined.');
		}
		if(!$this->getModule()){
			throw new Exception('Property \'_module\' is undefined.');
		}

		$cacheKey = 'remove_not_exist_url_'.$this->getUrlKeyAttribute()->getId();
		if(!$checkCache || Ddm_Cache::load($cacheKey)==false){
			Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('url_index'),array(
				'module'=>(string)$this->getModule(),
				'controller'=>(string)$this->getController(),
				'action'=>(string)$this->getAction()
			));
			Ddm_Cache::save($cacheKey,'1',array(),86400);
		}
		return $this;
	}

	/**
	 * @return Core_Model_Urlindex_Abstract
	 */
	public function removeClearUrlIndexCache(){
		if($this->getUrlKeyAttribute()){
			Ddm_Cache::remove('remove_not_exist_url_'.$this->getUrlKeyAttribute()->getId());
		}
		return $this;
	}
}
