<?php
/* @var $this Core_Model_Install */

Ddm_Db::schema()->createIfNotExists('config', function ($table) {
  $table->integer('config_id')->unsigned()->autoIncrement()->nullable(false);
  $table->string('path',255)->nullable(false)->default('');
  $table->primary(array('config_id'));
  $table->unique(array('path'), 'config_path');
  $table->engine('InnoDB');
});

Ddm_Db::schema()->createIfNotExists('config_value', function ($table) {
  $table->integer('config_value_id')->unsigned()->autoIncrement()->nullable(false);
  $table->integer('config_id')->unsigned()->nullable(false)->default(0);
  $table->integer('language_id')->unsigned()->nullable(false)->default(0);
  $table->text('config_value')->nullable(true);
  $table->primary(array('config_value_id'));
  $table->unique(array('config_id', 'language_id'), 'config_value_config_id_language_id');
});

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
