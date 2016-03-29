<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method Admin_Model_Resource_Admin getResource()
 * @property string $admin_name
 * @property int $admin_loginnum
 * @property int $is_active
 * @property int $edit_name
 * @property int $edit_pass
 */
class Admin_Model_Admin extends Core_Model_Abstract {
	const COOKIE_VARNAME = 'admin_c';
	const SESSION_VARNAME = 'loggedin_admin';

	protected static $_instance = NULL;
	protected static $_loggedInAdmin = NULL;
	protected $_allows = NULL;
	protected $_loggedStatusAdapter = 'session';//cookie or session
	private $_isLoggedIn = NULL;
	private $_verifyCookieResult = NULL;
	private $_verifySessionResult = NULL;

	/**
	 * 使用单例模式
	 * @return Admin_Model_Admin
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new Admin_Model_Admin()) : self::$_instance;
    }

	/**
	 * 获取当前登录的管理员
	 * @return Admin_Model_Admin
	 */
	public static function loggedInAdmin(){
		return self::$_loggedInAdmin===NULL ? (self::$_loggedInAdmin = new Admin_Model_Admin()) : self::$_loggedInAdmin;
	}

	/**
	 * @return bool
	 */
	public function isLoggedIn(){
		if($this->_isLoggedIn===NULL){
			$this->_isLoggedIn = $this->getLoggedStatusAdapter()=='cookie' ? $this->verifyCookie()===1 : $this->verifySession()===1;
			if($this->_isLoggedIn && $this->getLoggedStatusAdapter()=='cookie' && Ddm_Cookie::singleton()->getLifetime()){
				$this->_saveLoggedInStatus();//刷新Cookie的过期时间
			}
		}
		return $this->_isLoggedIn;
	}

	/**
	 * 返回：0用户名密码错误,1登录成功,2帐户被禁用
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	public function login($username,$password){
		if($username!='' && $password!=''){
			if($this->getResource()->login($this,$username,$password)){
				if($this->is_active){
					$this->_afterSuccessfulLogin();
					return 1;
				}else{
					return 2;
				}
			}
		}
		return 0;
	}

	/**
	 * @param int $id
	 * @return int 0:ID不存在,1:登录成功,2:帐户被禁用
	 */
	public function loginById($id){
		if($id = (int)$id){
			$this->load($id);
			if($this->getId()){
				if($this->is_active){
					$this->_afterSuccessfulLogin();
					return 1;
				}else{
					return 2;
				}
			}
		}
		return 0;
	}

	/**
	 * 返回一个整数, 0:不是有效的Session, 1:验证通过, 2:帐户已被其他人在另一设备登录
	 * @return int
	 */
	public function verifyCookie(){
		if($this->_verifyCookieResult===NULL){
			$this->_verifyCookieResult = 0;
			if($cookieHash = Ddm_Cookie::singleton()->get(self::COOKIE_VARNAME)){
				$cookieHash = explode('-',Ddm_String::singleton()->base64Decode($cookieHash),3);
				if(isset($cookieHash[2]) && isset($cookieHash[1]) && is_numeric($cookieHash[1]) && is_numeric($cookieHash[0])){
					if(!$this->getId())$this->load($cookieHash[0]);
					if($cookieHash2 = $this->getCookieHash(true)){
						if($this->admin_id && $this->is_active && $cookieHash2[2]===$cookieHash[2]){
							$this->_verifyCookieResult = $cookieHash2[1]===$cookieHash[1] ? 1 : 2;
						}
					}
				}
			}
		}
		return $this->_verifyCookieResult;
	}

	/**
	 * 返回一个整数, 0:不是有效的Session, 1:验证通过, 2:帐户已被其他人在另一设备登录
	 * @return int
	 */
	public function verifySession(){
		if($this->_verifySessionResult===NULL){
			$this->_verifySessionResult = 0;
			if($adminData = Ddm::getHelper('admin')->getSession()->getData(self::SESSION_VARNAME)){
				if(isset($adminData[2]) && isset($adminData[1]) && is_numeric($adminData[1]) && is_numeric($adminData[0])){
					if(!$this->getId())$this->load($adminData[0]);
					$cookieHash = $this->getCookieHash(true);
					if(!$cookieHash || !$this->is_active || $cookieHash[2]!=$adminData[2])return $this->_verifySessionResult;
					$this->_verifySessionResult = $cookieHash[1]==$adminData[1] ? 1 : 2;
				}
			}
		}
		return $this->_verifySessionResult;
	}

	/**
	 * @return bool
	 */
	public function logOut(){
		$this->getLoggedStatusAdapter()=='cookie' ? Ddm_Cookie::singleton()->delete(self::COOKIE_VARNAME,NULL,NULL,NULL,true) : Ddm::getHelper('admin')->getSession()->unsetData(self::SESSION_VARNAME);
		$this->_isLoggedIn = false;
		return true;
	}

	/**
	 * @return string
	 */
	public function getLoggedStatusAdapter(){
		return $this->_loggedStatusAdapter;
	}

	/**
	 * @param bool $asArray
	 * @return string|array
	 */
	public function getCookieHash($asArray = false){
		$data = $this->getId() ? array($this->admin_id,$this->admin_logintime,md5("$this->admin_name/$this->admin_pass/".Ddm::getConfig()->getConfigValue('hash_key'))) : array();
		if($asArray)return $data;
		return $data ? "$data[0]-$data[1]-$data[2]" : '';
	}

	/**
	 * @return array
	 */
	public function getGroups(){
		if($this->groups===NULL){
			if($this->getId())$this->groups = $this->getResource()->getGroupsFromAdminId($this->getId());
		}
		return $this->groups;
	}

	/**
	 * @return array
	 */
	public function getAllows(){
		if($this->_allows===NULL){
			$this->_allows = $this->getId() ? Admin_Model_Group::singleton()->getAllowsFromAdminId($this->getId()) : array();
		}
		return $this->_allows;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function isAllow($path,$type = Admin_Model_Group::ALLOW_TYPE_READ){
		foreach($this->getAllows() as $row){
			if($row=='all')return true;

			$path = strtolower($path);
			if(isset($row[$path])){
				if($type==Admin_Model_Group::ALLOW_TYPE_READ){
					foreach($row[$path] as $allowValue){
						if($allowValue)return true;
					}
					return false;
				}else{
					return isset($row[$path][$type]) ? (bool)$row[$path][$type] : false;
				}
			}
		}
		return false;
	}

	/**
	 * @param string $password
	 * @return string
	 */
	public function getPasswordHash($password){
		return md5("$password/".base64_encode($password));
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return Admin_Model_Admin
	 */
	public function setExtra($key,$value = NULL){
		if(is_array($key)){
			$this->extra = $key;
		}else{
			$this->extra = $this->getExtra();
			$this->extra[$key] = $value;
		}
		return $this;
	}

	/**
	 * @param string $key
	 * @return string|array
	 */
	public function getExtra($key = false){
		if($this->extra){
			if(is_string($this->extra)){
				$this->extra = unserialize($this->extra) or $this->extra = array();
			}
			return $key===false ? $this->extra : (isset($this->extra[$key]) ? $this->extra[$key] : NULL);
		}
		return $key===false ? array() : NULL;
	}

	/**
	 * @return Admin_Model_Admin
	 */
	protected function _afterSuccessfulLogin(){
		if($this->admin_id){
			$this->addData(array(
				'admin_loginnum'=>$this->admin_loginnum+1,
				'admin_prevtime'=>$this->admin_logintime,
				'admin_logintime'=>Ddm_Request::server()->REQUEST_TIME
			))->save();
			$this->_isLoggedIn = true;
			$this->_saveLoggedInStatus();
		}
		return $this;
	}

	/**
	 * @return Admin_Model_Admin
	 */
	protected function _saveLoggedInStatus(){
		if($this->isLoggedIn()===true){
			if($this->getLoggedStatusAdapter()=='cookie'){
				Ddm_Cookie::singleton()->set(self::COOKIE_VARNAME,Ddm_String::singleton()->base64Encode($this->getCookieHash()),NULL,NULL,NULL,NULL,true);
			}else{
				Ddm::getHelper('admin')->getSession()->setData(self::SESSION_VARNAME,$this->getCookieHash(true));
			}
		}
		return $this;
	}

	protected function _beforeSave(){
		if($this->groups_position){
			$groupIds = Ddm_Db::getReadConn()->getSelect()
				->from(Ddm_Db::getTable('admin_group'),'group_id')
				->where('group_id',array_keys($this->groups_position))
				->fetchPairs();
			if($groupIds){
				foreach($this->groups_position as $groupId=>$position){
					if(!in_array($groupId,$groupIds))unset($this->groups_position[$groupId]);
					else $this->groups_position[$groupId] = (int)$position;
				}
			}else{
				$this->unsetData('groups_position');
			}
		}
		if(!$this->getId() && !$this->groups_position){
			throw new Exception(Ddm::getTranslate('admin')->translate('至少要选择一个管理员组'));
		}
		if($this->admin_pass){
			if(is_string($this->admin_pass) && !preg_match('/^[0-9a-z]{32}$/',$this->admin_pass)){
				$this->admin_pass = $this->getPasswordHash($this->admin_pass);
			}
		}else{
			$this->admin_pass = $this->getOrigData('admin_pass') or $this->unsetData('admin_pass');
		}
		if(!$this->getId() && !$this->admin_logintime){
			$this->admin_logintime = Ddm_Request::server()->REQUEST_TIME;
		}
		if(!$this->getId() && !$this->admin_prevtime){
			$this->admin_prevtime = Ddm_Request::server()->REQUEST_TIME;
		}

		return parent::_beforeSave();
	}

	protected function _afterSave(){
		if($this->issetData('groups_position')){
			Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_group_user'),array('admin_id'=>$this->getId()));
		}
		if($this->groups_position){
			foreach($this->groups_position as $groupId=>$position){
				Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('admin_group_user'),array(
					'group_id'=>$groupId,
					'admin_id'=>$this->getId(),
					'position'=>$position
				), Ddm_Db_Interface::SAVE_INSERT);
			}
		}

		return parent::_afterSave();
	}

	protected function _afterDelete(){
		Ddm_Db::getWriteConn()->delete(Ddm_Db::getTable('admin_group_user'),array('admin_id'=>$this->getId()));

		return parent::_afterDelete();
	}

	protected function _afterLoad() {
		parent::_afterLoad();
		if($this->extra!=''){
			$this->extra = $this->getExtra();
		}
		return $this;
	}
}
