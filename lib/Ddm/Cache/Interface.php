<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

interface Ddm_Cache_Interface {

	/**
	 * @param string $id
	 * @param string $data
	 * @param array $tags
	 * @param int $lifetime
	 * @return bool
	 */
	public function save($id, $data, array $tags = NULL, $lifetime = 0);

	/**
	 * @param string $id
	 * @return string|false
	 */
	public function load($id);

	/**
	 * @param string $id
	 * @return bool
	 */
	public function remove($id);

	/**
	 * @param array $tags
	 * @return bool
	 */
	public function removeByTags(array $tags);

	/**
	 * @return bool
	 */
	public function clear();

	/**
	 * @param array $tags
	 * @return array
	 */
	public function getIdByTags(array $tags);

	/**
	 * @return array
	 */
	public function getAllTags();
}
