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

	private static $_connections = array();
	private static $_databaseConfig = NULL;
	private static $_beginTransaction = 0;
	public static $lockReadWiteType = NULL;

	public function __clone(){
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

	public static function unLockReadWite(){
		self::$lockReadWiteType = NULL;
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
	 * @param string $name 'read' or 'write'
	 * @return Ddm_Db_Interface
	 */
	public static function getConn($name){
		if(self::$lockReadWiteType)$name = self::$lockReadWiteType;
		else if(self::isBeginTransaction())$name = self::WRITE;
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
	 * @return array
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
	 * @return Ddm_Db_Select
	 */
	public static function getSelect(){
		return new Ddm_Db_Select();
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
