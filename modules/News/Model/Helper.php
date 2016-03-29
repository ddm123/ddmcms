<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Model_Helper {
	/**
	 * @param int $categoryId
	 * @param string $urlKey
	 * @return string
	 */
	public function getCategoryUrlFromUrlKey($categoryId,$urlKey){
		return Core_Model_Url::singleton()->getUrl($urlKey,'news/category/view',array('id'=>$categoryId));
	}

	/**
	 * @param int $newsId
	 * @param string $urlKey
	 * @return string
	 */
	public function getNewsUrlFromUrlKey($newsId,$urlKey){
		return Core_Model_Url::singleton()->getUrl($urlKey,'news/view',array('id'=>$newsId));
	}
}
