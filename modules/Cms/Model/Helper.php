<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Model_Helper {
	const ONEPAGE_CACHEKEY_PREFIX = 'cms_onepage_content_';
	const WIDGET_CACHEKEY_PREFIX = 'cms_widget_content_';
	const ONEPAGE_URLS_CACHE_KEY = 'cms_onepage_urls';

	protected $_onepageOptions = NULL;

	/**
	 * @param Cms_Model_Onepage $onepage
	 * @return Ddm_Cache|bool
	 */
	public function saveOnepageCache(Cms_Model_Onepage $onepage){
		if($onepage->getId() && is_numeric($onepage->cache_lifetime) && $onepage->cache_lifetime>=0){
			$data = $onepage->getData();
			if(is_array($data) && !empty($data['content'])){
				$data['content'] = preg_replace("/(<\/?\w+[^>]*>)\s*[\r\n]+\s*/",'\1',$data['content']);
			}
			return Ddm_Cache::save(self::ONEPAGE_CACHEKEY_PREFIX.$onepage->getId().'_'.Ddm::getLanguage()->language_id,$data,array('HTML_BLOCK','onepage'),$onepage->cache_lifetime*60);
		}

		return false;
	}

	/**
	 * @param int $onepageId
	 * @return Ddm_Cache
	 */
	public function removeOnepageCache($onepageId){
		return Ddm_Cache::remove(self::ONEPAGE_CACHEKEY_PREFIX.$onepageId.'_'.Ddm::getLanguage()->language_id);
	}

	/**
	 * @param int $onepageId
	 * @return string
	 */
	public function getOnepageCache($onepageId){
		return Ddm_Cache::load(self::ONEPAGE_CACHEKEY_PREFIX.$onepageId.'_'.Ddm::getLanguage()->language_id);
	}

	/**
	 * @param Cms_Model_Widget $widget
	 * @return Ddm_Cache|bool
	 */
	public function saveWidgetCache(Cms_Model_Widget $widget){
		if($widget->identifier && is_numeric($widget->cache_lifetime) && $widget->cache_lifetime>=0){
			$data = $widget->getData();
			if(is_array($data) && !empty($data['content'])){
				$data['content'] = preg_replace("/(<\/?\w+[^>]*>)\s*[\r\n]+\s*/",'\1',$data['content']);
			}
			return Ddm_Cache::save(self::WIDGET_CACHEKEY_PREFIX.$widget->identifier.'_'.Ddm::getLanguage()->language_id,$data,array('HTML_BLOCK','widget'),$widget->cache_lifetime*60);
		}

		return false;
	}

	/**
	 * @param string $identifier
	 * @return Ddm_Cache
	 */
	public function removeWidgetCache($identifier){
		return Ddm_Cache::remove(self::WIDGET_CACHEKEY_PREFIX.$identifier.'_'.Ddm::getLanguage()->language_id);
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	public function getWidgetCache($identifier){
		return Ddm_Cache::load(self::WIDGET_CACHEKEY_PREFIX.$identifier.'_'.Ddm::getLanguage()->language_id);
	}

	/**
	 * @param bool $withEmpty
	 * @return array
	 */
	public function getOnepageToOption($withEmpty = false){
		if($this->_onepageOptions===NULL){
			$this->_onepageOptions = array();
			$onepage = new Cms_Model_Onepage();
			$onepage->setLanguageId(Ddm::getLanguage()->language_id)
				->addAttributeToSelect('title')
				->addAttributeToFilter('is_enabled',1);
			foreach($onepage->getSelect()->fetchAll() as $opt){
				$this->_onepageOptions[] = array('value'=>$opt['onepage_id'],'label'=>$opt['title']);
			}
		}
		return $withEmpty ? array_merge(array(array('value'=>'','label'=>'')),$this->_onepageOptions) : $this->_onepageOptions;
	}

	/**
	 * @param string $urlKey
	 * @return int
	 */
	public function getOnepageIdFromUrlKey($urlKey){
		$onepageUrls = Ddm_Cache::load(self::ONEPAGE_URLS_CACHE_KEY);
		if($onepageUrls===false){
			$onepageUrls = array();
			if($urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('onepage','url_key')){
				$result = Ddm_Db::getReadConn()->getSelect()
					->from($urlKeyAttribute->getTable(),array('entity_id','language_id','value'))
					->where('attribute_id',$urlKeyAttribute->attribute_id)
					->fetchAll();
				foreach($result as $row){
					isset($onepageUrls[$row['value']]) or $onepageUrls[$row['value']] = array();
					$onepageUrls[$row['value']][$row['language_id']] = $row['entity_id'];
				}
			}
			Ddm_Cache::save(self::ONEPAGE_URLS_CACHE_KEY,$onepageUrls,array('onepage'),0);
		}
		if(Ddm::getLanguage()->language_id && isset($onepageUrls[$urlKey][Ddm::getLanguage()->language_id])){
			return $onepageUrls[$urlKey][Ddm::getLanguage()->language_id];
		}
		return isset($onepageUrls[$urlKey][0]) ? $onepageUrls[$urlKey][0] : 0;
	}
}
