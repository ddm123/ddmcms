<?php
/* @var $this Core_Model_Install */

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('language')."` (
  `language_id` int(4) unsigned NOT NULL auto_increment,
  `language_code` varchar(25) NOT NULL default '',
  `language_name` varchar(32) NOT NULL default '',
  `is_enable` tinyint(1) unsigned NULL default '0',
  `position` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY (`language_id`),
  UNIQUE KEY `language_code` (`language_code`),
  KEY `is_enable` (`is_enable`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->addConfig('web/language/default_language','0');
$this->addConfig('web/language/default_code','zh_cn');
$this->addConfig('web/language/default_name','简体中文');