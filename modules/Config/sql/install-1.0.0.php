<?php
/* @var $this Core_Model_Install */

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('config')."` (
  `config_id` int(4) unsigned NOT NULL auto_increment,
  `path` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`config_id`),
  UNIQUE KEY `path` (`path`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('config_value')."` (
  `config_value_id` int(8) unsigned NOT NULL auto_increment,
  `config_id` int(4) unsigned NOT NULL default '0',
  `language_id` int(4) unsigned NOT NULL default '0',
  `config_value` text NULL,
  PRIMARY KEY  (`config_value_id`),
  UNIQUE KEY `config_id_language_id` (`config_id`,`language_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->getModel('config','config')->addData(array('path'=>'web/language/inc_url','values'=>array(0=>'1')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/language/default_language','values'=>array(0=>'0')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/language/auto_saved','values'=>array(0=>'1')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/design/frontend_theme','values'=>array(0=>'default')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/design/admin_theme','values'=>array(0=>'default')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/cookie/cookie_prefix','values'=>array(0=>'ddmcms_')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/cookie/cookie_path','values'=>array(0=>'/')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/cookie/cookie_httponly','values'=>array(0=>'0')))->save();
$this->getModel('config','config')->addData(array('path'=>'system/date/datetime_format','values'=>array(0=>'Y-m-d H:i:s')))->save();
$this->getModel('config','config')->addData(array('path'=>'system/date/date_format','values'=>array(0=>'Y-m-d')))->save();
$this->getModel('config','config')->addData(array('path'=>'system/currency/currency_seat','values'=>array(0=>1)))->save();
$this->getModel('config','config')->addData(array('path'=>'system/currency/currency_symbol','values'=>array(0=>'¥')))->save();
$this->getModel('config','config')->addData(array('path'=>'system/currency/dec_point','values'=>array(0=>'.')))->save();
$this->getModel('config','config')->addData(array('path'=>'system/currency/precision','values'=>array(0=>'2')))->save();
$this->getModel('config','config')->addData(array('path'=>'system/currency/thousands_sep','values'=>array(0=>',')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/pages/home','values'=>array(0=>'1')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/base/web_name','values'=>array(0=>'欢迎使用DDMCMS')))->save();
$this->getModel('config','config')->addData(array('path'=>'web/base/hide_indexphp','values'=>array(0=>'1')))->save();

$this->addConfig('system/access/no_access_message','403');