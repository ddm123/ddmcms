<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

if (version_compare(phpversion(), '5.2.0', '<')===true){
    echo  '<div style="font:12px/1.35em arial, helvetica, sans-serif;">
<div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
<h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">
非常遗憾，你当前的PHP版本不能正常使用本系统</h3></div><p>DDMCMS需要运行在PHP 5.2.0或更高的版本</p></div>';
    exit;
}

$_SERVER['CMS_INSTALL'] = true;
require '../Ddm.php';
Ddm::init(0);

$configFile = SITE_ROOT.Config_Model_Config::XML_CONFIG_FILE;
if(is_file($configFile)){
	Ddm_Request::singleton()->redirect('../',301);
	exit;
}

$steps = array('welcome','check','setting','setup');
$step = isset($_GET['step']) && in_array($_GET['step'] = strtolower($_GET['step']),$steps) ? ucfirst($_GET['step']) : 'Welcome';
$className = "Step_$step";

require SITE_ROOT."/install/Step/Abstract.php";
require SITE_ROOT."/install/Step/$step.php";

$install = new $className();
$install->setSteps($steps)->run();
