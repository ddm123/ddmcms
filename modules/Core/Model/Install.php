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
				$this->_modulesVersion = Ddm_Db::table('modules')->pluck('version', 'module');
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
		$tables and $this->_createAttributeValueTables($tables);
		return $this;
	}

	/**
	 * @param string $path
	 * @param string|array $value
	 * @param int $languageId
	 * @return Config_Model_Config
	 */
	public function addConfig($path,$value,$languageId = 0){
		$config = $this->getModel('config','config');/** @var Config_Model_Config $config */
		return $config->loadFromPath($path)
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
			unset($modules['Core'],$modules['Language'],$modules['Config']);
			//调整下模块的安装顺序
			$modules = array_merge(array('Config'=>Ddm::getModuleConfig('Config'),'Core'=>Ddm::getModuleConfig('Core'),'Language'=>Ddm::getModuleConfig('Language')),$modules);
			foreach($modules as $moduleName=>$config){
				if(empty($config['version']))$config['version'] = '1.0.0.0';
				$isActive = !empty($config['active']);

				if($isActive){
					$this->_installModule($modulesVersion,$moduleName,$config);
				}
				if($isActive || isset($modulesVersion[$moduleName])){
					$this->getConnection()->insert(Ddm_Db::getTable('modules'),array(
						'module'=>$moduleName,
						'version'=>$isActive ? $config['version'] : $modulesVersion[$moduleName],
						'is_active'=>empty($config['active']) ? 0 : 1
					),false,array('version','is_active'));
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
	protected function _createAttributeValueTables($table){
		foreach((array)$table as $tableName){
			$type = explode('_',$tableName);
			$this->_createAttributeValueTable($tableName,end($type));
		}
		return $this;
	}

	/**
	 * @param string $tableName
	 * @param string $type
	 * @return bool
	 */
	protected function _createAttributeValueTable($tableName,$type){
		if($type==='int'){
			$valueColumn = function($table){ $table->integer('value')->unsigned()->nullable(true); };
		}else if($type==='varchar'){
			$valueColumn = function($table){ $table->string('value')->nullable(true); };
		}else if($type==='text'){
			$valueColumn = function($table){ $table->text('value')->nullable(true); };
		}else{
			return false;
		}

		$tablePrefix = Ddm_Db::getTable('');
		if($tablePrefix)$tableName = substr($tableName, strlen($tablePrefix));
		Ddm_Db::schema()->createIfNotExists($tableName, function ($table) use($tableName, $valueColumn) {
			$table->integer('entity_id')->unsigned()->nullable(false)->default(0);
			$table->integer('attribute_id')->unsigned()->nullable(false)->default(0);
			$table->integer('language_id')->unsigned()->nullable(false)->default(0);
			$valueColumn($table);
			$table->primary(array('entity_id','attribute_id','language_id'));
			$table->index(array('attribute_id'),$tableName.'_attribute_id');
			$table->engine('InnoDB');
		});
		return true;
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
		return $v1 && $v2 ? version_compare($v1[1],$v2[1]) : 0;
	}
}
