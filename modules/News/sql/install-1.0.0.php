<?php
/* @var $this Core_Model_Install */

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('news_category')."` (
  `category_id` int(4) unsigned NOT NULL auto_increment,
  `position` int(10) unsigned NULL DEFAULT '0',
  `create_at` int(10) unsigned NULL DEFAULT '0',
  `update_at` int(10) unsigned NULL DEFAULT '0',
  PRIMARY KEY  (`category_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->executeSql("CREATE TABLE IF NOT EXISTS `".Ddm_Db::getTable('news')."` (
  `news_id` int(10) unsigned NOT NULL auto_increment,
  `category_id` int(4) unsigned NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `create_at` int(10) unsigned NULL DEFAULT '0',
  `update_at` int(10) unsigned NULL DEFAULT '0',
  PRIMARY KEY  (`news_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

$this->addAttribute(array(
	array('entity_type'=>'news_category','attribute_code'=>'name','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'名称','source_model'=>NULL,'is_required'=>'1','is_global'=>'0',
		'is_system'=>'1','note'=>NULL,'position'=>'1'),
	array('entity_type'=>'news_category','attribute_code'=>'url_key','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'URL Key','source_model'=>NULL,'is_required'=>'0','is_global'=>'0',
		'is_system'=>'1','note'=>'使用友好的URL显示链接','position'=>'5'),
	array('entity_type'=>'news_category','attribute_code'=>'position','backend_type'=>'static','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'排序','source_model'=>NULL,'is_required'=>'1','is_global'=>'1',
		'is_system'=>'1','note'=>NULL,'position'=>'10'),
	array('entity_type'=>'news','attribute_code'=>'category_id','backend_type'=>'static','backend_table'=>'news',
		'frontend_type'=>'select','frontend_label'=>'分类','source_model'=>'News_Model_Category_Option','is_required'=>'1',
		'is_global'=>'1','is_system'=>'1','note'=>NULL,'position'=>'0'),
	array('entity_type'=>'news','attribute_code'=>'title','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'标题','source_model'=>NULL,'is_required'=>'1','is_global'=>'0',
		'is_system'=>'1','note'=>NULL,'position'=>'5'),
	array('entity_type'=>'news','attribute_code'=>'url_key','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'URL Key','source_model'=>NULL,'is_required'=>'0','is_system'=>'1',
		'is_global'=>'0','note'=>'使用友好的URL显示链接','position'=>'10'),
	array('entity_type'=>'news','attribute_code'=>'from','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'来源','source_model'=>NULL,'is_required'=>'0','is_global'=>'1',
		'is_system'=>'1','note'=>NULL,'position'=>'15'),
	array('entity_type'=>'news','attribute_code'=>'author','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'作者','source_model'=>NULL,'is_required'=>'0','is_global'=>'1',
		'is_system'=>'1','note'=>NULL,'position'=>'20'),
	array('entity_type'=>'news','attribute_code'=>'content','backend_type'=>'text','backend_table'=>'news',
		'frontend_type'=>'editor','frontend_label'=>'内容','source_model'=>NULL,'is_required'=>'1','is_global'=>'0',
		'is_system'=>'1','note'=>NULL,'position'=>'25'),
	array('entity_type'=>'news','attribute_code'=>'meta_keywords','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'SEO 关键词','source_model'=>NULL,'is_required'=>'0','is_global'=>'0',
		'is_system'=>'1','note'=>NULL,'position'=>'30'),
	array('entity_type'=>'news','attribute_code'=>'meta_description','backend_type'=>'varchar','backend_table'=>'news',
		'frontend_type'=>'text','frontend_label'=>'SEO 描述','source_model'=>NULL,'is_required'=>'0','is_global'=>'0',
		'is_system'=>'1','note'=>NULL,'position'=>'35')
));
