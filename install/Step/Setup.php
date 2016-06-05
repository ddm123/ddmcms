<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

require SITE_ROOT.'/install/Step/Setting.php';

class Step_Setup extends Step_Abstract {
	protected $_template = 'setup.phtml';

	/**
	 * @return Step_Setup
	 */
	private function _saveConfig(){
		$xml = str_replace(array(
			'{admin_path}',
			'{hash_key}',
			'{db_tablepre}',
			'{db_driver}',
			'{db_host}',
			'{db_port}',
			'{db_username}',
			'{db_password}',
			'{db_dbname}',
			'{cache_driver}',
			'{cache_prefix}'
		),array(
			$_POST['other_adminpath'],
			md5($_SERVER['HTTP_HOST'].'-'.(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : gethostbyname($_SERVER['HTTP_HOST'])).'-'.$_SERVER['REQUEST_TIME']),
			$_POST['db_tablepre'],
			$_POST['db_driver'],
			$_POST['db_host'],
			$_POST['db_port'],
			$_POST['db_username'],
			$_POST['db_password'],
			$_POST['db_name'],
			$_POST['other_cache_driver'],
			$_POST['other_cache_prefix']
		),file_get_contents(SITE_ROOT.'/install/define_template.xml.php'));
		Ddm::getHelper('core')->saveFile(SITE_ROOT.'/data/configs/define.xml.php',$xml);
		return $this;
	}

	/**
	 * @return array
	 */
	private function _checkPostData(){
		$result = array('status'=>true,'message'=>'');
		$setting = new Step_Setting();
		foreach($setting->getElements() as $group){
			foreach($group['elements'] as $name=>$attributes){
				$_POST[$name] = isset($_POST[$name]) ? trim($_POST[$name]) : '';
				if($name!='db_password' && $name!='db_tablepre' && $_POST[$name]===''){
					$result['message'] = $attributes['label'].'不能为空';
				}else if($_POST[$name]!='' && $name=='db_tablepre' && !preg_match('/^\w+$/',$_POST[$name])){
					$result['message'] = '表名前缀必须是英文字母、数字和下划线或者是这些字符的组合';
				}else if($name=='other_adminpath' && !preg_match('/^\w+$/',$_POST[$name])){
					$result['message'] = '后台登录入口会被用于访问后台的URL, 必须是英文字母、数字和下划线或者是这些字符的组合';
				}else if($name=='other_cache_prefix' && !preg_match('/^\w+$/',$_POST[$name])){
					$result['message'] = '缓存键名前缀前缀必须是英文字母、数字和下划线或者是这些字符的组合';
				}
				if($result['message']!=''){
					$result['status'] = false;
					break;
				}
			}
		}
		if($result['status']){
			if($_POST['db_driver']=='Ddm_Db_Mysqli'){
				$link = @mysqli_connect($_POST['db_host'],$_POST['db_username'],$_POST['db_password'],$_POST['db_name'],$_POST['db_port']);
				if(!$link){
					$result['message'] = 'MySQL返回错误: '.mysqli_connect_error();
					$result['status'] = false;
				}
			}else{
				$_POST['db_driver'] = 'Ddm_Db_Mysql';
				$link = @mysql_connect($_POST['db_host'].':'.$_POST['db_port'],$_POST['db_username'],$_POST['db_password']);
				if(!$link){
					$result['message'] = 'MySQL返回错误: '.mysql_error();
					$result['status'] = false;
				}else{
					mysql_select_db($_POST['db_name'],$link);
					if(mysql_error()){
						$result['message'] = 'MySQL返回错误: '.mysql_error($link);
						$result['status'] = false;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @return Step_Setup
	 */
	private function _savePostData(){
		Ddm::getHelper('core')->saveFile(SITE_ROOT.'/data/setting-post-data.tmp',serialize($_POST));
		return $this;
	}

	/**
	 * @return Step_Setup
	 */
	private function _createMainTable(){
		Ddm_Db::getWriteConn()->query("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('modules')."`(
  `module` varchar(64) NOT NULL default '',
  `version` varchar(16) NOT NULL default '',
  `is_active` tinyint(1) unsigned NULL default '0',
  PRIMARY KEY (`module`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");
		return $this;
	}

	/**
	 * @return Step_Setup
	 */
	private function _saveAdminUser(){
		$admin = new Admin_Model_Admin();
		$admin->getResource()->load($admin,$_POST['admin_username'],'admin_name');
		if(!$admin->getId())$admin->load(1);
		$admin->addData(array(
			'admin_name'=>$_POST['admin_username'],
			'admin_pass'=>$_POST['admin_password'],
			'groups_position'=>array(1=>1),
			'admin_loginnum'=>0,
			'is_active'=>1,
			'edit_name'=>1,
			'edit_pass'=>1
		))->save();
		return $this;
	}

	protected function _beforeRun(){
		$result = $this->_checkPostData();
		if($result['status']){
			$this->_saveConfig();
			if(file_exists(SITE_ROOT.'/data/setting-post-data.tmp')){
				unlink(SITE_ROOT.'/data/setting-post-data.tmp');
			}

			$this->clearCache();
			Ddm::isInstalled(true);
			$this->_createMainTable();

			$install = new Core_Model_Install();
			$install->applyInstall();

			$this->_saveAdminUser();
		}else{
			$_POST['errors'] = $result['message'];
			$this->_savePostData();
			Ddm_Request::redirect('./index.php?step=setting');
			$this->_template = NULL;
		}

		$this->addTitle('安装完成');
		return parent::_beforeRun();
	}

	protected function _afterRun() {
		parent::_afterRun();
		$this->clearCache();
		return $this;
	}
}