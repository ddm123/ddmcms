<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Model_Resource_News extends Core_Model_Resource_Entity {
	protected function _init(){
		$this->_setTable('news','news_id');
		return $this;
	}

	/**
	 * 批量修改一条或多条新闻的分类
	 * @param int $categoryId
	 * @param int|array $newsId
	 * @return News_Model_Resource_News
	 */
	public function changeCategory($categoryId,$newsId){
		if(!empty($newsId)){
			$categoryId = (int)$categoryId;
			if(is_array($newsId)){
				if(count($newsId)<2)$newsId = (int)reset($newsId);
			}else{
				$newsId = (int)$newsId;
			}

			Ddm_Db::getWriteConn()->save(Ddm_Db::getTable('news'),array('category_id'=>$categoryId),Ddm_Db_Interface::SAVE_UPDATE,array('news_id'=>$newsId));
		}
		return $this;
	}

	/**
	 * 累加浏览次数
	 * @param int $newsId
	 * @param int $i 增加多少, 默认加1
	 * @return News_Model_Resource_News
	 */
	public function addView($newsId,$i = 1){
		if(($i = (int)$i) && ($newsId = (int)$newsId)){
			Ddm_Db::getWriteConn()->query("UPDATE ".Ddm_Db::getTable('news')." SET `views`=`views`+$i WHERE news_id='$newsId'");
		}
		return $this;
	}

	/**
	 * @param News_Model_News $news
	 * @param string $urlKey
	 * @return News_Model_Resource_News
	 */
	public function loadFromUrlKey(News_Model_News $news,$urlKey){
		$_news = new News_Model_News();
		$result = $_news->setLanguageId($news->language_id)->addAttributeToSelect('url_key')
			->addAttributeToFilter('url_key',$urlKey)
			->getSelect()->fetchOne(false);
		if($result){
			$news->addData($result)->setOrigData($result,NULL,true);
		}
		return $this;
	}

	/**
	 * @param int $newsId
	 * @return News_Model_Resource_News
	 */
	public function saveUrlIndex($newsId){
		if(Ddm::getHelper('core')->getEntityAttribute('news','url_key')){
			$urlIndex = new News_Model_News_Urlindex();
			$urlIndex->saveUrlIndex($newsId);
		}
		return $this;
	}

	protected function _afterSave(Core_Model_Abstract $model){
		parent::_afterSave($model);//必须在第一行

		if($model->url_key!=$model->getOrigData('url_key')){
			//删掉旧的URL记录
			Core_Model_Url::singleton()->removeFromUrlKey('news',$model->getOrigData('url_key'));
			$this->saveUrlIndex($model->getId());
		}

		return $this;
	}
}
