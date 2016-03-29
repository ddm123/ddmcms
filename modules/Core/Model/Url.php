<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Model_Url {
	protected static $_instance = NULL;
	protected $_mainTable = NULL;

	public function __construct() {
		//;
	}

	/**
	 * 使用单例模式
	 * @return Core_Model_Url
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new self()) : self::$_instance;
    }

	/**
	 * @return string
	 */
	public function getMainTable(){
		return $this->_mainTable===NULL ? ($this->_mainTable = Ddm_Db::getTable('url_index')) : $this->_mainTable;
	}

	/**
	 * @param string $urlKey
	 * @param string $path
	 * @param array $params
	 * @return string
	 */
	public function getUrl($urlKey, $path, array $params = array()){
		if($urlKey){
			$moduleName = explode('/',$path,2);
			$url = Ddm::getBaseUrl().$moduleName[0].'/'.$urlKey;
		}else{
			$url = Ddm::getUrl($path,$params);
		}
		return $url;
	}

	/**
	 * @param array $urlKey
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 * @return Core_Model_Url
	 */
	public function saveUrl(array $urlKey,$module,$controller = NULL,$action = NULL,array $params = NULL){
		if($urlKey && $module){
			if($params!==NULL)$params = is_array($params) ? serialize($params) : NULL;
			$data = array();
			foreach($urlKey as $languageId=>$_urlKey){
				if(!$_urlKey && $_urlKey!=='0')continue;
				$data[] = array(
					'url_path'=>"$module/$_urlKey",
					'language_id'=>(int)$languageId,
					'module'=>$module,
					'controller'=>$controller,
					'action'=>$action,
					'params'=>$params
				);
			}
			if($data)
				Ddm_Db::getWriteConn()->insertMultiple($this->getMainTable(),$data,array('module','controller','action','params'));
		}
		return $this;
	}

	/**
	 * @param array $urlKey
	 * @param int $languageId
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 * @return Core_Model_Url
	 */
	public function saveUrlIndex($urlKey,$languageId,$module,$controller = NULL,$action = NULL,array $params = NULL){
		if($urlKey && $module){
			if($params!==NULL)$params = is_array($params) ? serialize($params) : NULL;
			Ddm_Db::getWriteConn()->save($this->getMainTable(),array(
				'url_path'=>"$module/$urlKey",
				'language_id'=>(int)$languageId,
				'module'=>$module,
				'controller'=>$controller,
				'action'=>$action,
				'params'=>$params
			),Ddm_Db_Interface::SAVE_DUPLICATE,array(
				'module'=>$module,
				'controller'=>$controller,
				'action'=>$action,
				'params'=>$params
			));
		}
		return $this;
	}

	/**
	 * @param string $urlPath
	 * @param int $languageId
	 * @param array $urlPaths
	 * @return array
	 */
	public function loadFromUrlPath($urlPath,$languageId = NULL,&$urlPaths = array()){
		if(!$urlPath)return array();

		$urlPaths = array();
		$k = 0;
		foreach(explode('/',$urlPath) as $p){
			if(stripos($p,'.html') || stripos($p,'.htm')){
				$urlPaths[$k] = $k ? $urlPaths[$k-1].$p : $p;
				$urlPaths[1+$k] = $urlPaths[$k].'/';
				break;
			}else{
				$urlPaths[$k] = $k ? $urlPaths[$k-1].$p.'/' : $p.'/';
				$k++;
			}
		}
		//如果斜杠超过5个以上则认为这并不是后台填写的url_key
		if(isset($urlPaths[2]) && ($urlPaths = array_slice($urlPaths,1,-1)) && !isset($urlPaths[5])){
			$where = Ddm_Db::getReadConn()->getSelect()->quoteInto(array(
				'url_path'=>isset($urlPaths[1]) ? array('in'=>$urlPaths) : $urlPaths[0],
				'language_id'=>intval($languageId===NULL ? Ddm::getLanguage()->language_id : $languageId)
			));
			return Ddm_Db::getReadConn()->fetchAll("SELECT * FROM ".$this->getMainTable()." WHERE $where",'url_path');
		}
		return array();
	}

	/**
	 * @param string $urlPath
	 * @param mixed $languageId
	 * @return Core_Model_Url
	 */
	public function removeFromUrlPath($urlPath,$languageId = true){
		$where = array('url_path'=>$urlPath);
		if($languageId===NULL)$languageId = Ddm::getLanguage()->language_id;
		if($languageId!==true)$where['language_id'] = $languageId;
		Ddm_Db::getWriteConn()->delete($this->getMainTable(),$where);
		return $this;
	}

	/**
	 * @param string $module
	 * @param string $urlKey
	 * @param mixed $languageId
	 * @return Core_Model_Url
	 */
	public function removeFromUrlKey($module,$urlKey,$languageId = true){
		return $this->removeFromUrlPath("$module/$urlKey",$languageId);
	}

	/**
	 * @param array $params
	 * @return Core_Model_Url
	 */
	public function deleteFromLanguage($params){
		if($languageId = (int)$params['object']->getId()){
			Ddm_Db::getWriteConn()->delete($this->getMainTable(),array('language_id'=>$languageId));
		}
		return $this;
	}

	/**
	 * @param array $params
	 * @return Core_Model_Url
	 */
	public function matchesUrl($params){
		if(($urlPath = $params['controller']->getSelfPath()) && ($data = $this->loadFromUrlPath($urlPath,NULL,$urlPaths))){
			for($i = count($urlPaths);$i--;){
				if(isset($data[$urlPaths[$i]])){
					$data = $data[$urlPaths[$i]];
					break;
				}
			}
			$params['controller']
				->setModuleName($data['module'])
				->setControllerName($data['controller'] ? $data['controller'] : NULL)
				->setActionName($data['action'] ? $data['action'] : NULL)
				->parseParams(substr($urlPath,strlen($data['url_path'])))
				->setUrlAlias($data['url_path']);

			$params['vars']->parseUriResult = true;
			if(isset($_GET[$params['vars']->url]))unset($_GET[$params['vars']->url]);

			$params['controller']->setHomePage(false);
			$params['controller']->resetRunModule();

			if($data['params']){
				foreach(unserialize($data['params']) as $key=>$value){
					Ddm_Request::singleton()->setParam($key,$value);
				}
			}
		}
		return $this;
	}
}
