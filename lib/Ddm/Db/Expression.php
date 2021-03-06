<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_Expression {
	protected $_expression = '';

	/**
	 * @param string $expression
	 */
	public function __construct($expression){
		$this->_expression = (string)$expression;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		return $this->_expression==='' ? "''" : $this->_expression;
	}
}