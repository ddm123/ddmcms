<?php
/* @var $this Core_Model_Install */

$this->executeSql("ALTER IGNORE TABLE `".Ddm_Db::getTable('admin_group')."` ADD `description` VARCHAR(255) NULL default NULL AFTER `group_name`");
$this->executeSql("ALTER IGNORE TABLE `".Ddm_Db::getTable('admin_user')."` ADD `description` VARCHAR(255) NULL default NULL AFTER `admin_pass`");

$group = $this->getModel('admin','group');
$group->loadFromGroupName('Administrators');
if($group->getId()){
	$group->setData('description','该组下的管理员对后台有不受限制的完全访问权')
		->save();
}