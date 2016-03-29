<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

abstract class Core_Model_Attribute_Source_Abstract {
	protected $_options = NULL;

	/**
	 * @param bool $withEmpty
	 * @param bool $defaultValue
	 * @return array
	 */
	abstract public function getAllOptions($withEmpty = false,$defaultValue = true);

	/**
	 * @param bool $withEmpty
	 * @param bool $defaultValue
	 * @return array
	 */
	public function getBaseOptions($withEmpty = false,$defaultValue = true){
		$options = array();
		foreach($this->getAllOptions($withEmpty,$defaultValue) as $option){
			$options[$option['value']] = $option['label'];
		}
		return $options;
	}

	/**
	 * @param bool $withEmpty
	 * @param bool $defaultValue
	 * @return string
	 */
	public function toComboboxData($withEmpty = false,$defaultValue = false){
		$str = '[';
		foreach($this->getAllOptions($withEmpty,$defaultValue) as $option){
			$options[$option['value']] = $option['label'];
			$str=='[' or $str .= ',';
			$str .= '["'.str_replace('"','\\"',$option['value']).'","'.str_replace('"','\\"',$option['label']).'"]';
		}
		$str .= ']';

		return $str;
	}
}
