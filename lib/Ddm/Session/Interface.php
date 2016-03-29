<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

interface Ddm_Session_Interface {

	/**
	 * @return Ddm_Session_Interface
	 */
	public function setSaveHandler();

	/**
	 * @return bool
	 */
	public function close();

	/**
	 * @param string $sessionId
	 * @return bool
	 */
	public function destroy ($sessionId);

	/**
	 * @param string $maxlifetime
	 * @return bool
	 */
	public function gc($maxlifetime);

	/**
	 * @param string $savePath
	 * @param string $name
	 * @return bool
	 */
	public function open ($savePath,$name);

	/**
	 * @param string $sessionId
	 * @return string
	 */
	public function read($sessionId);

	/**
	 * @param string $sessionId
	 * @param string $sessionData
	 * @return bool
	 */
	public function write($sessionId,$sessionData);
}
