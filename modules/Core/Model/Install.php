<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Core_Model_Install {
	const MODULES_CACHE_KEY = 'modules_version';
	const INSTALL_LOCK_CACHE_KEY = 'modules_install_lock';

	protected $_connection = NULL;
	protected $_modulesVersion = NULL;

	/**
	 * @return Ddm_Db_Interface
	 */
	public function getConnection(){
		return $this->_connection===NULL ? ($this->_connection = Ddm_Db::getWriteConn()) : $this->_connection;
	}

	/**
	 * 执行一条SQL查询
	 * @param string $sql
	 * @return resource|bool
	 */
	public function executeSql($sql){
		return $this->getConnection()->query($sql);
	}

	/**
	 * @return array
	 */
	public function getModulesVersion(){
		if($this->_modulesVersion===NULL){
			$this->_modulesVersion = Ddm_Cache::load(self::MODULES_CACHE_KEY);
			if($this->_modulesVersion===false){
				$this->_modulesVersion = $this->getConnection()->fetchPairs("SELECT `module`,`version` FROM ".Ddm_Db::getTable('modules'));
				Ddm_Cache::save(self::MODULES_CACHE_KEY,$this->_modulesVersion,array('module'),0);
			}
		}
		return $this->_modulesVersion;
	}

	/**
	 * @param string $module
	 * @param string $class
	 * @return Core_Model_Abstract
	 */
	public function getModel($module,$class){
		$className = ucfirst($module).'_Model_'.str_replace(' ','_',ucwords(str_replace('_',' ',$class)));
		return new $className();
	}

	/**
	 * @param array $data
	 * @return Core_Model_Install
	 */
	public function addAttribute(array $data){
		$tables = array();
		if(is_numeric(key($data))){
			foreach($data as $item){
				if(Ddm::getHelper('core')->getEntityAttribute($item['entity_type'],$item['attribute_code']))
					continue;

				$a = new Core_Model_Attribute();
				$a->addData($item)->save();

				if($a->attribute_id && $a->backend_type!='static')
					$tables[$a->getTable()] = $a->getTable();
			}
		}else if(!Ddm::getHelper('core')->getEntityAttribute($data['entity_type'],$data['attribute_code'])){
			$a = new Core_Model_Attribute();
			$a->addData($data)->save();

			if($a->attribute_id && $a->backend_type!='static')
				$tables[$a->getTable()] = $a->getTable();
		}
		$tables and $this->_createAttributeValueTable($tables);
		return $this;
	}

	/**
	 * @param string $path
	 * @param string|array $value
	 * @param int $languageId
	 * @return Config_Model_Config
	 */
	public function addConfig($path,$value,$languageId = 0){
		return $this->getModel('config','config')
			->loadFromPath($path)
			->addData(array('path'=>$path,'values'=>is_array($value) ? $value : array((int)$languageId=>$value)))
			->save();
	}

	/**
	 * @return Core_Model_Install
	 */
	public function applyInstall(){
		if(!Ddm_Cache::load(self::INSTALL_LOCK_CACHE_KEY)){
			Ddm_Cache::save(self::INSTALL_LOCK_CACHE_KEY,$_SERVER['REQUEST_TIME'],array('module'),0);

			$modulesVersion = $this->getModulesVersion();
			$modules = Ddm::getModuleConfig();
			unset($modules['Core'],$modules['Language']);
			//把Core模块和Language模块先安装
			$modules = array_merge(array('Core'=>Ddm::getModuleConfig('Core'),'Language'=>Ddm::getModuleConfig('Language')),$modules);
			foreach($modules as $moduleName=>$config){
				if(empty($config['version']))$config['version'] = '1.0.0.0';
				$isActive = !empty($config['active']);

				if($isActive){
					$this->_installModule($modulesVersion,$moduleName,$config);
				}
				if($isActive || isset($modulesVersion[$moduleName])){
					$this->getConnection()->save(Ddm_Db::getTable('modules'),array(
						'module'=>$moduleName,
						'version'=>$isActive ? $config['version'] : $modulesVersion[$moduleName],
						'is_active'=>empty($config['active']) ? 0 : 1
					),Ddm_Db_Interface::SAVE_DUPLICATE,array('version','is_active'));
				}
			}
		}
		return $this;
	}

	/**
	 * @param array $modulesVersion
	 * @param string $moduleName
	 * @param array $config
	 * @return Core_Model_Install
	 */
	protected function _installModule($modulesVersion,$moduleName,$config){
		if(isset($modulesVersion[$moduleName])){
			if($files = $this->_getUpgradeFiles($moduleName,$config)){
				$isUpgrade = false;
				foreach($files as $file){
					if(preg_match('/sql\/upgrade-(.+?)\.php$/',$file,$v)){
						if(version_compare($v[1],$modulesVersion[$moduleName])<=0){
							continue;
						}
						if(version_compare($v[1],$config['version'])>0){
							break;
						}
						include $file;
					}
				}
			}
		}else if($files = $this->_getInstallFiles($moduleName,$config)){
			foreach($files as $file){
				include $file;
			}
			if(preg_match('/sql\/install-(.+?)\.php$/',$file,$v)){
				$modulesVersion[$moduleName] = $v[1];
				$this->_installModule($modulesVersion,$moduleName,$config);
			}
		}
		return $this;
	}

	/**
	 * @param array|string $table
	 * @return Core_Model_Install
	 */
	protected function _createAttributeValueTable($table){
		foreach((array)$table as $tableName){
			$type = explode('_',$tableName);
			if($sql = $this->_getCreateAttributeValueTableSql($tableName,end($type))){
				$this->executeSql($sql);
			}
		}
		return $this;
	}

	/**
	 * @param string $tableName
	 * @param string $type
	 * @return string
	 */
	protected function _getCreateAttributeValueTableSql($tableName,$type){
		$valueTypes = array('int'=>'int(10)','varchar'=>'varchar(255)','text'=>'text');
		$sql = '';
		if(isset($valueTypes[$type])){
			$sql .= "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
			$sql .= "`entity_id` int(10) unsigned NOT NULL default '0',\n";
			$sql .= "`attribute_id` int(4) unsigned NOT NULL default '0',\n";
			$sql .= "`language_id` int(4) unsigned NOT NULL default '0',\n";
			$sql .= "`value` ".$valueTypes[$type]." NULL default NULL,\n";
			$sql .= "UNIQUE KEY `entity_id_attribute_id_language_id` (`entity_id`,attribute_id,`language_id`),\n";
			$sql .= "KEY `entity_id` (`entity_id`)\n";
			$sql .= ") ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci";
		}
		return $sql;
	}

	/**
	 * @param string $moduleName
	 * @param array $config
	 * @return array
	 */
	private function _getInstallFiles($moduleName,$config){
		if($files = glob(SITE_ROOT.'/'.MODULES_FOLDER.'/'.$moduleName.'/sql/install-*.php')){
			if(count($files)>1)usort($files,array($this,'_sortFilesCallback'));
		}
		return $files;
	}

	/**
	 * @param string $moduleName
	 * @param array $config
	 * @return array
	 */
	private function _getUpgradeFiles($moduleName,$config){
		if($files = glob(SITE_ROOT.'/'.MODULES_FOLDER.'/'.$moduleName.'/sql/upgrade-*.php')){
			if(count($files)>1)usort($files,array($this,'_sortFilesCallback'));
		}
		return $files;
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	private function _sortFilesCallback($a,$b){
		preg_match('/sql\/(?:install|upgrade)-(.+?)\.php$/',$a,$v1);
		preg_match('/sql\/(?:install|upgrade)-(.+?)\.php$/',$b,$v2);
		return $v1 && $v2 ? version_compare($v1,$v2) : 0;
	}
}
