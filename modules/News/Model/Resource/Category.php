<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Model_Resource_Category extends Core_Model_Resource_Entity {
	protected function _init(){
		$this->_setTable('news_category','category_id');
		return $this;
	}

	/**
	 * 返回下一个可用的排序位置
	 * @return int
	 */
	public function getMaxPosition(){
		return Ddm_Db::getReadConn()->fetchOne("SELECT MAX(`position`) AS p FROM ".$this->getMainTable(),true) + 1;
	}

	/**
	 * @param News_Model_Category $category
	 * @param string $urlKey
	 * @return News_Model_Resource_Category
	 */
	public function loadFromUrlKey(News_Model_Category $category,$urlKey){
		$_category = new News_Model_Category();
		$result = $_category->setLanguageId($category->language_id)->addAttributeToSelect('url_key')
			->addAttributeToFilter('url_key',$urlKey)
			->getSelect()->fetchOne(false);
		if($result){
			$category->addData($result)->setOrigData($result,NULL,true);
		}
		return $this;
	}

	/**
	 * 获取全部分类下的新闻记录数
	 * @return array
	 */
	public function getNewsCount(){
		$sql = "SELECT a.category_id,COUNT(b.news_id) AS c FROM ".Ddm_Db::getTable('news_category')." AS a";
		$sql .= " LEFT JOIN ".Ddm_Db::getTable('news')." AS b ON(b.category_id=a.category_id) GROUP BY a.category_id ORDER BY NULL";
		return Ddm_Db::getReadConn()->fetchPairs($sql);
	}

	/**
	 * @param int $categoryId
	 * @return News_Model_Resource_Category
	 */
	public function saveUrlIndex($categoryId){
		if(Ddm::getHelper('core')->getEntityAttribute('news_category','url_key')){
			$urlIndex = new News_Model_Category_Urlindex();
			$urlIndex->saveUrlIndex($categoryId);
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

	protected function _afterDelete(Core_Model_Abstract $object){
		if($object->getId()){
			Ddm_Db::getWriteConn()->save(
				Ddm_Db::getTable('news'),
				array('category_id'=>0),
				Ddm_Db_Interface::SAVE_UPDATE,
				array('category_id'=>$object->getId())
			);
		}
		return parent::_afterDelete($object);
	}
}

