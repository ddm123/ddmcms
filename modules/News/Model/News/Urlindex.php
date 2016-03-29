<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class News_Model_News_Urlindex extends Core_Model_Urlindex_Abstract {
	protected $_module = 'news';
	protected $_controller = 'view';
	protected $_params = array('id'=>'{entity_id}');

	/**
	 * @return Core_Model_Attribute
	 */
	public function getUrlKeyAttribute(){
		return $this->_urlKeyAttribute===NULL ? ($this->_urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('news','url_key')) : $this->_urlKeyAttribute;
	}
}
