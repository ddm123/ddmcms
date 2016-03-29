<?php
/* @var $this Core_Model_Install */

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('admin_group')."` (
  `group_id` int(4) unsigned NOT NULL auto_increment,
  `group_name` varchar(25) NOT NULL default '',
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('admin_user')."` (
  `admin_id` int(4) unsigned NOT NULL auto_increment,
  `admin_name` varchar(50) NOT NULL default '',
  `admin_pass` char(32) NOT NULL default '',
  `admin_loginnum` int(4) unsigned NULL default '0',
  `admin_logintime` int(10) unsigned NULL default '0',
  `admin_prevtime` int(10) unsigned NULL default '0',
  `is_active` tinyint(1) unsigned NOT NULL default '0',
  `edit_name` tinyint(1) unsigned NOT NULL default '0',
  `edit_pass` tinyint(1) unsigned NOT NULL default '0',
  `extra` text NULL default NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`),
  KEY `name_pass` (`admin_name`,`admin_pass`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('admin_group_user')."` (
  `group_id` int(4) unsigned NULL default '0',
  `admin_id` int(4) unsigned NULL default '0',
  `position` int(8) unsigned NULL default '0',
  PRIMARY KEY (`group_id`,`admin_id`),
  KEY `position` (`position`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('admin_allow')."` (
  `allow_id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(4) unsigned NULL default '0',
  `path` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (`allow_id`),
  UNIQUE KEY `admin_id_path` ( `group_id`,`path` ),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('admin_allow_value')."` (
  `allow_id` int(10) unsigned NOT NULL default '0',
  `group_id` int(4) unsigned NULL default '0',
  `allow_type` varchar(16) NOT NULL DEFAULT 'read',
  `allow_value` tinyint(1) unsigned NULL default '0',
  PRIMARY KEY `allow_id_allow_type` (`allow_id`,`allow_type`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$group = $this->getModel('admin','group');
$group->loadFromGroupName('Administrators');
if(!$group->getId()){
	$group->load(1);
}
$group->addData('group_name','Administrators')
	->save()
	->saveResources('all');