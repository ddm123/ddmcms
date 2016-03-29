<?php
/* @var $this Core_Model_Install */

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('attribute')."` (
  `attribute_id` int(4) unsigned NOT NULL auto_increment,
  `entity_type` varchar(64) NOT NULL DEFAULT '',
  `attribute_code` varchar(64) NOT NULL DEFAULT '',
  `backend_type` varchar(16) NOT NULL DEFAULT 'static',
  `backend_table` varchar(32) NULL DEFAULT NULL,
  `frontend_type` varchar(16) NOT NULL DEFAULT '',
  `frontend_label` varchar(225) NULL DEFAULT '',
  `source_model` varchar(225) NULL DEFAULT NULL,
  `is_required` tinyint(1) unsigned NULL default '0',
  `is_global` tinyint(1) unsigned NULL default '0',
  `is_system` tinyint(1) unsigned NULL default '0',
  `is_visible` tinyint(1) unsigned NOT NULL default '1',
  `note` varchar(255) NULL DEFAULT NULL,
  `position` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`attribute_id`),
  UNIQUE KEY `entity_type_attribute_code` (`entity_type`,`attribute_code`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('url_index')."` (
  `url_path` varchar(225) NOT NULL DEFAULT '',
  `language_id` int(4) unsigned NOT NULL DEFAULT '0',
  `module` varchar(64) NOT NULL DEFAULT '',
  `controller` varchar(64) NULL DEFAULT NULL,
  `action` varchar(64) NULL DEFAULT NULL,
  `params` varchar(225) NULL DEFAULT NULL,
  PRIMARY KEY  (`url_path`,`language_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");