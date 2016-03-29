<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Cms_Block_Onepage_View extends Core_Block_Abstract {
	protected $_onepageData = NULL;
	protected $_onepageId = NULL;

	public function init() {
		if($this->getOnepageData()){
			$this->getTemplateObject()
				->setTitle($this->_onepageData['title'])
				->addMeta('description',Ddm_String::singleton()->escapeHtml($this->_onepageData['meta_description']))
				->addMeta('keywords',Ddm_String::singleton()->escapeHtml($this->_onepageData['meta_keywords']));

			if($this->_onepageData['add_css']){
				foreach($this->_onepageData['add_css'] as $file)$this->getTemplateObject()->addCss($file);
			}

			if($this->_onepageData['add_js']){
				foreach($this->_onepageData['add_js'] as $file)$this->getTemplateObject()->addJs($file);
			}

			if($this->_onepageData['styles']){
				foreach($this->_onepageData['styles'] as $style)$this->getTemplateObject()->addStyle($style);
			}
		}
		return parent::init();
	}

	/**
	 * @param int $id
	 * @return Cms_Block_Onepage_View
	 */
	public function setOnepageId($id){
		$this->_onepageId = (int)$id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getOnepageId(){
		if($this->_onepageId===NULL){
			$this->_onepageId = intval(Ddm_Request::get('id')) or $this->_onepageId = intval(Ddm::getConfig()->getConfigValue('web/pages/not_found'));
		}
		return $this->_onepageId;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getOnepageData($name = NULL){
		if($this->_onepageData===NULL){
			Ddm::dispatchEvent('get_onepage_data_before',array('onepage'=>$this));
			$id = $this->getOnepageId();
			if(!$id)return $this->_onepageData = false;

			$this->_onepageData = Ddm::getHelper('Cms')->getOnepageCache($id);//先看是否已经存在缓存
			if($this->_onepageData===false){
				//如果未被缓存过
				$onepage = new Cms_Model_Onepage();
				$onepage->setLanguageId(Ddm::getLanguage()->language_id)->load($id);
				if($onepage->getId()){
					$this->_processedContent($onepage);
					$onepage->meta_keywords or $onepage->meta_keywords = $onepage->title;
					$onepage->meta_description or $onepage->meta_description = Ddm_String::singleton()->cutString($onepage->content,200,'...',true);
					$this->_onepageData = $onepage->getData();
					Ddm::getHelper('Cms')->saveOnepageCache($onepage);//保存到缓存
				}
			}
			Ddm::dispatchEvent('get_onepage_data_after',array('onepage'=>$this,'data'=>$this->_onepageData));
		}
		return $name===NULL ? $this->_onepageData : ($this->_onepageData && isset($this->_onepageData[$name]) ? $this->_onepageData[$name] : NULL);
	}

	/**
	 * @param Cms_Model_Onepage $onepage
	 * @return Cms_Block_Onepage_View
	 */
	protected function _processedContent($onepage){
		$template = new Cms_Model_Processed_Template($onepage->content,array('this'=>$this,'onepage'=>$onepage,'template_object'=>$this->getTemplateObject()));
		$template->isGetStyle(true);
		$onepage->content = $template->processed();
		$onepage->add_css = $template->getCssFiles();
		$onepage->add_js = $template->getJsFiles();
		$onepage->styles = $template->getStyles();
		return $this;
	}

	protected function _toHtml(){
		return $this->getOnepageData('content');
	}
}