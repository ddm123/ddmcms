<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

interface Ddm_Db_Interface {
	const SAVE_INSERT = 'INSERT';
	const SAVE_UPDATE = 'UPDATE';
	const SAVE_REPLACE = 'REPLACE';
	const SAVE_DUPLICATE = 'DUPLICATE';

	/**
	 * 选择数据库
	 * @param string $databaseName
	 * @return Ddm_Db_Interface
	 */
	public function setDatabase($databaseName);

	/**
	 * 关闭数据连接
	 * @param resource $dblink
	 * @return Ddm_Db_Interface
	 */
	public function close($dblink = false);

	/**
     * @param string $string
     * @param int $type
     * @return string|false
     */
    public function quote($string, $type = PDO::PARAM_STR);

    /**
     * @param string $sql
     * @param array $params
     * @param int $fetchMode
     * @return PDOStatement|false
     */
    public function query($sql, array $params = array(), $fetchMode = PDO::FETCH_ASSOC);

    /**
     * @param PDOStatement $res
     * @return array|false
     */
    public function fetch($res);

	/**
	 * @return int 最后一次插入的自增ID
	 */
	public function getLastInsertId();

	/**
	 * 新增一条记录
	 * @param string $table
	 * @param array $row
	 * @param bool $useIgnore
	 * @param array $OnDuplicateFields
	 * @return int
	 */
	public function insert($table, array $row, $useIgnore = false, array $OnDuplicateFields = array());

	/**
	 * 新增多条记录
	 * @param string $table
	 * @param array $rows
	 * @param bool|array $useIgnore
	 * @param array $OnDuplicateFields
	 * @return int
	 */
	public function insertMultiple($table, array $rows, $useIgnore = false, array $OnDuplicateFields = array());

	/**
	 * 释放结果内存
	 * @param resource $result
	 * @return Ddm_Db_Interface
	 */
	public function freeResult($result);

	/**
	 * 返回当前连接的MySQL的版本
	 */
	public function getMysqlVersion();

	/**
	 * @return int 一共执行多少次SQL命令
	 */
	public function getQueryCount();

	/**
	 * 开始一个事务
	 * @return bool
	 */
	public function beginTransaction();

	/**
	 * 提交事务
	 * @return bool
	 */
	public function commit();

	/**
	 * 回滚事务
	 * @return bool
	 */
	public function rollBack();

	/**
	 * 返回该数据库标识符(字段名/表名)的引用符
	 * @return string
	 */
	public function getIdentifierQuote();
}
