<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

/**
 * @method News_Model_Resource_Category getResource()
 */
class News_Model_Category extends Core_Model_Entity {
	protected static $_instance = NULL;
	protected $_url = NULL;

	protected function _initEntity(){
		$this->setEntity('news_category')->setEntityPrimarykey('category_id');
	}

	/**
	 * 使用单例模式
	 * @return News_Model_Category
	 */
	public static function singleton(){
		return self::$_instance===NULL ? (self::$_instance = new self()) : self::$_instance;
    }

	/**
	 * 返回下一个可用的排序位置
	 * @return int
	 */
	public function getMaxPosition(){
		return $this->getResource()->getMaxPosition();
	}

	/**
	 * 根据url_key获取一条数据
	 * @param string $urlKey
	 * @return News_Model_Category
	 */
	public function loadFromUrlKey($urlKey){
		if($urlKey){
			$this->getResource()->loadFromUrlKey($this,$urlKey);
			$this->_afterLoad();
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCategoryUrl(){
		if($this->category_id){
			return $this->_url===NULL ? ($this->_url = Ddm::getHelper('news')->getCategoryUrlFromUrlKey($this->category_id,$this->url_key)) : $this->_url;
		}
		return NULL;
	}

	/**
	 * 检测分类是否是存在的
	 *
	 * @param mixed $value
	 * @param string $fieldName
	 * @return bool
	 * @throws Exception
	 */
	public function categoryExists($value,$fieldName = 'category_id'){
		if(empty($fieldName)){
			throw new Exception('Field name can not be empty');
		}
		$sql = Ddm_Db::getReadConn()->getSelect()
			->from(Ddm_Db::getTable('news_category'),'category_id')
			->where($fieldName,$value);
		return Ddm_Db::getReadConn()->fetchOne("SELECT EXISTS($sql) AS `exist`",true)=='1';
	}

	protected function _beforeSave(){
		if($this->create_at===NULL)$this->create_at = Ddm_Request::server()->REQUEST_TIME;
		$this->update_at = Ddm_Request::server()->REQUEST_TIME;
		$this->position = (int)$this->position;
		if($this->url_key){
			$this->url_key = preg_replace('/[^\w\-\.\/]/','-',$this->url_key);
			if(!preg_match('/(?:\/|\.html?)$/i',$this->url_key))$this->url_key .= '.html';
		}
		return parent::_beforeSave();
	}
}
