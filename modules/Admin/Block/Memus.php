<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Admin_Block_Memus extends Core_Block_Abstract {
	const CACHE_KEY_PRE = 'administrator_memus_';
	const MEMUS_DATA_CACHE_KEY = 'adminhtml_memus';
	const CACHE_LIFETIME = 86400;

	/**
	 * 获取导航所有链接(未判断权限)
	 * @return array
	 */
	public function getMemusData(){
		$memus = Ddm_Cache::load(self::MEMUS_DATA_CACHE_KEY);
		if($memus===false){
			$memus = array();
			$sort = array();
			$childnodesSort = array();
			foreach(Ddm::getModuleConfig() as $moduleName=>$config){
				if(!empty($config['active']) && isset($config['admin']['menus'])){
					foreach($config['admin']['menus'] as $key=>$value){
						$value['module'] = $moduleName;
						if(!isset($memus[$key])){
							$memus[$key] = $value;
							$memus[$key]['sort'] = isset($memus[$key]['sort']) ? 0+$memus[$key]['sort'] : 0;
						}else{
							if(isset($value['sort']))$value['sort'] += 0;
							isset($memus[$key]['module']) and $value['module'] = $memus[$key]['module'].','.$value['module'];
						}
						if(empty($value['childnodes']) && $value)$memus[$key] = array_merge($memus[$key],$value);
						else{
							$childnodes = $value['childnodes'];
							unset($value['childnodes']);
							$memus[$key] = array_merge($memus[$key],$value);
							isset($memus[$key]['childnodes']) or $memus[$key]['childnodes'] = array();
							foreach($childnodes as $_key=>$_value){
								$_value['module'] = $moduleName;
								if(!isset($memus[$key]['childnodes'][$_key])){
									$memus[$key]['childnodes'][$_key] = $_value;
									$memus[$key]['childnodes'][$_key]['sort'] = isset($memus[$key]['childnodes'][$_key]['sort']) ? 0+$memus[$key]['childnodes'][$_key]['sort'] : 0;
								}else if($_value){
									if(isset($_value['sort']))$_value['sort'] += 0;
									$memus[$key]['childnodes'][$_key] = array_merge($memus[$key]['childnodes'][$_key],$_value);
								}
								$childnodesSort[$key][$_key] = $memus[$key]['childnodes'][$_key]['sort'];
							}
						}
						$sort[$key] = $memus[$key]['sort'];
					}
				}
			}
			if($sort){
				array_multisort($sort,SORT_ASC,SORT_NUMERIC,$memus);
				foreach($memus as $key=>$value){
					if(isset($childnodesSort[$key]) && !empty($value['childnodes'])){
						array_multisort($childnodesSort[$key],SORT_ASC,SORT_NUMERIC,$memus[$key]['childnodes']);
					}
				}
			}
			Ddm_Cache::save(self::MEMUS_DATA_CACHE_KEY,$memus,array('module'),self::CACHE_LIFETIME);
		}
		return $memus;
	}

	/**
	 * 获取导航所有链接(已判断权限)
	 * @return array
	 */
	public function getMemus(){
		$memus = Ddm_Cache::load(Admin_Block_Memus::CACHE_KEY_PRE.Admin_Model_Admin::loggedInAdmin()->getId());
		if($memus===false){
			$memus = array();
			foreach($this->getMemusData() as $key=>$value){
				if(empty($value['childnodes'])){
					if(Admin_Model_Admin::loggedInAdmin()->isAllow("{$value['module']}/$key",Admin_Model_Group::ALLOW_TYPE_READ))
						$memus[$key] = $value;
				}else{
					$childnodes = array();
					foreach($value['childnodes'] as $k=>$val){
						if(Admin_Model_Admin::loggedInAdmin()->isAllow("{$val['module']}/$k",Admin_Model_Group::ALLOW_TYPE_READ))
							$childnodes[$k] = $val;
					}
					if($childnodes){
						$memus[$key] = $value;
						$memus[$key]['childnodes'] = $childnodes;
					}
				}
			}
			Ddm_Cache::save(Admin_Block_Memus::CACHE_KEY_PRE.Admin_Model_Admin::loggedInAdmin()->getId(),$memus,array('admin_user','module'),Admin_Block_Memus::CACHE_LIFETIME);
		}
		return $memus;
	}

	public function isActiveMemu($key,$module){
		if(Admin_Block_Template::singleton()->activeMemu!==NULL){
			return Admin_Block_Template::singleton()->activeMemu==$key;
		}
		if(stripos(",$module,",','.Ddm_Controller::singleton()->getModuleName().',')===false)return false;
		return true;
	}
}
