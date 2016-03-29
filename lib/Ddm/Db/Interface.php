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
	const GET_FIRST = 'GET_FIRST';

	const MYSQL_ASSOC = 1;
	const MYSQL_NUM = 2;
	const MYSQL_BOTH = 3;
	const MYSQL_OBJECT = 4;

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
	 * 返回字符串，该字符串为了数据库查询语句等的需要在某些字符前加上了反斜线
	 * @param string $string
	 * @return string
	 */
	public function quote($string);

	/**
	 * 执行一条SQL查询
	 * @param string $sql
	 * @return resource|bool
	 */
	public function query($sql);

	/**
	 * 第一个参数是SQL, 第二个参数开始是填充SQL的变量, 格式和sprintf一样
	 * @return resource|bool
	 */
	public function queryf();

	/**
	 * 从结果集中取得一行作为关联数组
	 * @param resource $res
	 * @return array|false
	 */
	public function fetch($res);

	/**
	 * 获取查询到的所有记录
	 * @param string $sql
	 * @param string $primaryKey 主键字段名
	 * @return array
	 */
	public function fetchAll($sql, $primaryKey = NULL);

	/**
	 * 获取一行或一个字段的值
	 * @param string $sql
	 * @param bool $getFirst 是否返回第一个字段的值
	 * @return array|mixed|false|null
	 */
	public function fetchOne($sql, $getFirst = false);

	/**
	 * 把多行转成一行的形式,返回一个一维数组,如果有两个字段,则第一个字段的值为键名第二个字段为值
	 * @param string $sql
	 * @return array
	 */
	public function fetchPairs($sql);

	/**
	 * 执行COUNT(*)的SQL查询
	 * @param string $table
	 * @param array $expression
	 * @return int
	 */
	public function count($table, array $expression = NULL);

	/**
	 * 执行SUM(fieldname)的SQL查询
	 * @param type $table
	 * @param array|string $fieldname 可同时查询多个字段 例如: array('alias'=>'fieldname'[,..])
	 * @param array $expression
	 * @return numeric|array
	 */
	public function sum($table, $fieldname, array $expression = NULL);

	/**
	 * @return int 最后一次插入的自增ID
	 */
	public function getLastInsertId();

	/**
	 * $v 一个数组，是需要保存到表的数据：array(字段1=>值1, 字段2=>值2[, ......])
	 * 如果$type值为DUPLICATE时，$expression即为替换值，不再是条件表达式，格式和$v一样
	 * @param string $table
	 * @param array $v
	 * @param string $type
	 * @param array $expression
	 * @return bool
	 */
	public function save($table, array $v, $type = self::SAVE_INSERT, array $expression = NULL);

	/**
	 * 执行SQL的DELETE的查询
	 * @param string $table
	 * @param array $expression
	 * @return bool
	 */
	public function delete($table, array $expression = NULL);

	/**
	 * 同时插入多行记录
	 * @param string $table
	 * @param array $data 要插入的行, 一个二维数组
	 * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
	 * @return bool
	 */
	public function insertMultiple($table, array $data, array $OnDuplicateFields = array());

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
	 * @return Ddm_Db_Select
	 */
	public function getSelect();
}
