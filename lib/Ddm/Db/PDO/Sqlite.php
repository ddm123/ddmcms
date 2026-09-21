<?php
/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

class Ddm_Db_PDO_Sqlite implements Ddm_Db_Interface
{
    /** @var PDO */
    protected $_pdo = NULL;

    /** @var int */
    protected $_querynum = 0;

    /**
     * @param string $ser 数据库文件路径(或 :memory:), 如果为空则使用 $db
     * @param string $un 忽略
     * @param string $pw 忽略
     * @param string|null $db 数据库文件路径(当 $ser 为空时使用)
     * @param string|null $port 忽略
     * @param string|null $character 忽略
     * @param bool $pconnect 忽略(SQLite不支持持久连接)
     */
    public function __construct($ser = NULL, $un = NULL, $pw = NULL, $db = NULL, $port = NULL, $character = NULL, $pconnect = false)
    {
        $path = ($ser !== NULL && $ser !== '') ? $ser : $db;
        if ($path === NULL || $path === '') {
            $path = ':memory:';
        }

        $this->_pdo = new PDO('sqlite:'.$path, NULL, NULL, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ));
    }

    /**
     * @param string $databaseName 忽略(SQLite以文件为单位, 无多库概念)
     * @return Ddm_Db_Interface
     */
    public function setDatabase($databaseName)
    {
        return $this;
    }

    /**
     * @return Ddm_Db_Interface
     */
    public function close($dblink = false)
    {
        //$this->_pdo = NULL;
        return $this;
    }

    /**
     * @param string $string
     * @param int $type
     * @return string|false
     */
    public function quote($string, $type = PDO::PARAM_STR)
    {
        return $this->_pdo->quote($string, $type);
    }

    /**
     * @param string $sql
     * @param array $params
     * @param int $fetchMode
     * @return PDOStatement|false
     */
    public function query($sql, array $params = array(), $fetchMode = PDO::FETCH_ASSOC)
    {
        try {
            $this->_querynum++;

            if ($params) {
                $sth = $this->_pdo->prepare($sql);
                $sth->setFetchMode($fetchMode);
                $sth->execute($params);
                return $sth;
            }

            return $this->_pdo->query($sql, $fetchMode);
        } catch (Exception $e) {
            $this->_errorLog($e->getMessage().PHP_EOL.'SQL: '.$sql.($params ? PHP_EOL.'Params: '.var_export($params, true) : ''));
            throw $e;
        }
    }

    /**
     * @param PDOStatement $res
     * @return array|false
     */
    public function fetch($res)
    {
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return int 最后一次插入的自增ID
     */
    public function getLastInsertId()
    {
        return $this->_pdo->lastInsertId();
    }

    /**
     * 新增一条记录
     *
     * 注意: SQLite不支持MySQL的 ON DUPLICATE KEY UPDATE,
     * 当传入 $OnDuplicateFields 时使用 INSERT OR REPLACE 实现“存在则替换”,
     * 语义与MySQL略有差异(冲突时整行被删除后重新插入)。
     *
     * @param string $table
     * @param array $row
     * @param bool $useIgnore
     * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
     * @return int
     */
    public function insert($table, array $row, $useIgnore = false, array $OnDuplicateFields = array())
    {
        if (!$row) return 0;

        return $this->_doInsert($table, array($row), $useIgnore, $OnDuplicateFields);
    }

    /**
     * 新增多条记录
     *
     * @param string $table
     * @param array $rows
     * @param bool|array $useIgnore
     * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
     * @return int
     */
    public function insertMultiple($table, array $rows, $useIgnore = false, array $OnDuplicateFields = array())
    {
        if (!$rows) return 0;

        if (is_array($useIgnore) && !$OnDuplicateFields) {
            $OnDuplicateFields = $useIgnore;
            $useIgnore = false;
        }

        if (!isset($rows[0]) || !is_array($rows[0])) {
            return $this->insert($table, $rows, $useIgnore, $OnDuplicateFields);
        }

        return $this->_doInsert($table, $rows, $useIgnore, $OnDuplicateFields);
    }

    /**
     * 释放结果内存
     * @param PDOStatement $result
     * @return Ddm_Db_PDO_Sqlite
     */
    public function freeResult($result)
    {
        if ($result instanceof PDOStatement) {
            $result->closeCursor();
        }
        return $this;
    }

    /**
     * 返回当前连接的SQLite版本
     * @return string|null
     */
    public function getMysqlVersion()
    {
        $result = $this->query('SELECT sqlite_version() AS version');
        $row = $result->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['version'] : null;
    }

    /**
     * @return int 一共执行多少次SQL命令
     */
    public function getQueryCount()
    {
        return $this->_querynum;
    }

    /**
     * 开始一个事务
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->_pdo->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        return $this->_pdo->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack()
    {
        return $this->_pdo->rollBack();
    }

    /**
     * SQLite标识符使用双引号
     * @return string
     */
    public function getIdentifierQuote()
    {
        return '"';
    }

    /**
     * 执行单条或多条INSERT
     * @param string $table
     * @param array $rows 二维数组
     * @param bool $useIgnore
     * @param array $OnDuplicateFields
     * @return int
     */
    protected function _doInsert($table, array $rows, $useIgnore, array $OnDuplicateFields)
    {
        $fields = array_keys($rows[0]);
        $params = array();
        $valueRows = array();

        foreach ($rows as $row) {
            if (!$row) continue;

            $placeholders = array();
            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : NULL;
                $placeholders[] = $this->_valuePlaceholder($value, $params);
            }
            $valueRows[] = '('.implode(',', $placeholders).')';
        }

        if (!$valueRows) return 0;

        $quotedFields = array();
        foreach ($fields as $field) {
            $quotedFields[] = $this->_quote($field);
        }

        $sql = $this->_insertPrefix($useIgnore, $OnDuplicateFields).' INTO '.$this->_quote($table)
            .' ('.implode(',', $quotedFields).') VALUES '.implode(',', $valueRows);

        $sth = $this->query($sql, $params);
        return $sth->rowCount();
    }

    /**
     * 把一个值转成SQL占位片段, 并把需要绑定的值追加到 $params
     * @param mixed $value
     * @param array $params
     * @return string
     */
    protected function _valuePlaceholder($value, array &$params)
    {
        if ($value === NULL) return 'NULL';
        if ($value === true) return '1';
        if ($value === false) return '0';
        if ($value instanceof Ddm_Db_Expression) return (string)$value;
        $params[] = $value;
        return '?';
    }

    /**
     * 根据参数决定INSERT前缀(OR IGNORE / OR REPLACE)
     * @param bool $useIgnore
     * @param array $OnDuplicateFields
     * @return string
     */
    protected function _insertPrefix($useIgnore, array $OnDuplicateFields)
    {
        if ($OnDuplicateFields) return 'INSERT OR REPLACE';
        if ($useIgnore) return 'INSERT OR IGNORE';
        return 'INSERT';
    }

    /**
     * 给表名/字段名加上双引号
     * @param string $identifier
     * @return string
     */
    protected function _quote($identifier)
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    /**
     * @param string $log
     * @return void
     */
    protected function _errorLog($log)
    {
        if (defined('SITE_ROOT')) {
            $f = SITE_ROOT.'/data/errors/sqlite/sqlite-errors.txt';
            $logstring = date('Y-m-d H:i:s')."\r\n-----------------------------------";
            $logstring .= "\r\nURL: ".$_SERVER['REQUEST_URI']."\r\nIP: ".$_SERVER['REMOTE_ADDR']."\r\nUSER_AGENT: ".(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'None')."\r\nMETHOD: ".$_SERVER['REQUEST_METHOD'];
            if(!empty($_POST))$logstring .= "\r\nPOST_DATA: ".print_r($_POST,true);
            if(empty($_SERVER['QUERY_STRING']) && !empty($_GET))$logstring .= "\r\nGET_DATA: ".print_r($_GET,true);
            $logstring .= "\r\n$log";

            if(is_file($f)){
                if (filesize($f) > 4194304) {
                    rename($f, SITE_ROOT.'/data/errors/sqlite/sqlite-errors-'.date('YmdHis').'.txt');
                } else {
                    Ddm::getHelper('core')->saveFile($f, "\r\n".$logstring, FILE_APPEND);
                    return;
                }
            }

            Ddm::getHelper('core')->saveFile($f, $logstring);
        }
    }
}
