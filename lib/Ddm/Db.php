<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db {
	const READ = 'read';
	const WRITE = 'write';

	/** @var array<string, Ddm_Db_Interface> */
	private static $_connections = array();

	/** @var array|null */
	private static $_databaseConfig = NULL;

	/** @var int */
	private static $_beginTransaction = 0;

	/** @var string|null 'read' or 'write' */
	private static $lockReadWiteType = NULL;

	/** @var string[] */
	private static $lockReadWiteTypes = array();

	public function __clone(){
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

	/**
	 * 解除锁定读/写库
	 * @return void
	 */
	public static function unLockReadWite(){
		self::$lockReadWiteType = self::$lockReadWiteTypes ? array_pop(self::$lockReadWiteTypes) : NULL;
	}

	/**
	 * 锁定读库
	 * @return void
	 */
	public static function lockReadConn(){
		self::lockConn(self::READ);
	}

	/**
	 * 锁定写库
	 * @return void
	 */
	public static function lockWriteConn(){
		self::lockConn(self::WRITE);
	}

	/**
	 * 锁定读/写库
	 * @param string $type
	 * @return void
	 */
	public static function lockConn($type){
		self::$lockReadWiteTypes[] = self::$lockReadWiteType;
		self::$lockReadWiteType = $type ? $type : self::READ;
	}

	/**
	 * @return Ddm_Db_Interface
	 */
	public static function getReadConn(){
		return self::getConn(self::READ);
	}

	/**
	 * @return Ddm_Db_Interface
	 */
	public static function getWriteConn(){
		return self::getConn(self::WRITE);
	}

	/**
	 * Connect to the database
	 * @param string $name 连接哪个数据库
	 * @return Ddm_Db_Interface
	 */
	public static function getConn($name){
		if(self::$lockReadWiteType)$name = self::$lockReadWiteType;
		else if($name===self::READ && self::isBeginTransaction())$name = self::WRITE;
		else if(!$name)$name = self::READ;

		if(!isset(self::$_connections[$name])){
			self::_getDatabaseConfig();
			if(isset(self::$_databaseConfig[$name])){
				self::$_connections[$name] = new self::$_databaseConfig[$name]['driver'](self::$_databaseConfig[$name]['host'],self::$_databaseConfig[$name]['username'],self::$_databaseConfig[$name]['password'],self::$_databaseConfig[$name]['dbname'],self::$_databaseConfig[$name]['port'],self::$_databaseConfig[$name]['character'],self::$_databaseConfig[$name]['use_pconnect']);
			}else if(isset(self::$_databaseConfig['driver'])){
				if(self::$_connections)return self::$_connections[$name] = current(self::$_connections);
				self::$_connections[$name] = new self::$_databaseConfig['driver'](self::$_databaseConfig['host'],self::$_databaseConfig['username'],self::$_databaseConfig['password'],self::$_databaseConfig['dbname'],self::$_databaseConfig['port'],self::$_databaseConfig['character'],self::$_databaseConfig['use_pconnect']);
			}else{
				throw new Exception("'$name' database driver is not defined.");
			}
		}
		return self::$_connections[$name];
	}

	/**
	 * @return array<string, Ddm_Db_Interface>
	 */
	public static function getAllConnections(){
		return self::$_connections;
	}

	/**
	 * Get table name
	 * @param string $table
	 * @return string
	 */
	public static function getTable($table){
		self::_getDatabaseConfig();
		return self::$_databaseConfig['tablepre'].$table;
	}

	/**
	 * 获取一个查询构造器实例, 用于查询、修改、删除等操作
	 * @param string|array $name 表名, 也支持 array(别名=>表名) 或 "表名 as 别名"
	 * @param bool $isPrefixIncluded 表名是否已包含前缀
	 * @return Ddm_Db_Builder
	 */
	public static function table($name, $isPrefixIncluded = false){
		list($table,$alias,$isPrefixIncluded) = Ddm_Db_Builder::splitTableAlias($name, $isPrefixIncluded);
		$builder = new Ddm_Db_Builder($isPrefixIncluded ? $table : self::getTable($table),$alias);
		return $builder->setIdentifierQuote(self::getReadConn()->getIdentifierQuote());
	}

	/**
	 * @return int
	 */
	public static function lastInsertId(){
		return self::getWriteConn()->getLastInsertId();
	}

	/**
	 * @return bool
	 */
	public static function beginTransaction(){
		self::$_beginTransaction++;
		return self::$_beginTransaction===1 ? self::getWriteConn()->beginTransaction() : false;
	}

	/**
	 * @return bool
	 */
	public static function commit(){
		self::$_beginTransaction--;
		return self::$_beginTransaction===0 ? self::getWriteConn()->commit() : false;
	}

	/**
	 * @return bool
	 */
	public static function rollBack(){
		self::$_beginTransaction--;
		return self::$_beginTransaction===0 ? self::getWriteConn()->rollBack() : false;
	}

	/**
	 * @param callable $callback
	 * @return mixed
	 */
	public static function transaction($callback){
		$result = null;
		self::beginTransaction();
		try {
			$result = $callback();
			self::commit();
		} catch (Exception $e) {
			self::rollBack();
			throw $e;
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public static function isBeginTransaction(){
		return self::$_beginTransaction>0;
	}

	/**
	 * @return array
	 */
	protected static function _getDatabaseConfig(){
		return self::$_databaseConfig===NULL ? (self::$_databaseConfig = Ddm::getConfig()->getDbConfig()) : self::$_databaseConfig;
	}
}
