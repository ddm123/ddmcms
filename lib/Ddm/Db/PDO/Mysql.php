<?php
class Ddm_Db_PDO_Mysql implements Ddm_Db_Interface
{
    /** @var PDO */
    protected $_pdo = NULL;

    /** @var int */
    protected $_querynum = 0;

    /**
     * @param string $ser
     * @param string $un
     * @param string $pw
     * @param string|null $db
     * @param string|null $port
     * @param string|null $character
     * @param bool $pconnect
     */
    public function __construct($ser, $un, $pw, $db = NULL, $port = NULL, $character = NULL, $pconnect = false)
    {
        $dsn = 'mysql:host='.$ser;
        if ($port !== NULL && $port !== '') $dsn .= ';port='.$port;
        if ($db !== NULL && $db !== '') $dsn .= ';dbname='.$db;
        if ($character !== NULL && $character !== '') $dsn .= ';charset='.$character;

	    $this->_pdo = new PDO($dsn, $un, $pw, array(PDO::ATTR_PERSISTENT => (bool)$pconnect, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true));
    }

    /**
     * @param string $databaseName
     * @return Ddm_Db_Interface
     */
    public function setDatabase($databaseName)
    {
        $this->_pdo->exec('USE `' . $databaseName . '`');
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
     * @param string $table
     * @param array $row
     * @param bool $useIgnore
     * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
     * @return int
     */
    public function insert($table, array $row, $useIgnore = false, array $OnDuplicateFields = array())
    {
        if (!$row) return 0;

        $fields = array_keys($row);
        $fieldsCount = count($fields);
        $sql = 'INSERT';
        if ($useIgnore) $sql .= ' IGNORE';
        $sql .= ' INTO ' . $table . ' (`' . implode('`,`', $fields) . '`)';
        $sql .= ' VALUES (';

        $params = array();
        for ($i = 0; $i < $fieldsCount; $i++) {
            if ($row[$fields[$i]] === NULL) {
                $sql .= 'NULL';
            } else if ($row[$fields[$i]] === true) {
                $sql .= '1';
            } else if ($row[$fields[$i]] === false) {
                $sql .= '0';
            } else if ($row[$fields[$i]] instanceof Ddm_Db_Expression) {
                $sql .= $row[$fields[$i]];
            } else {
                $sql .= '?';
                $params[] = $row[$fields[$i]];
            }
            if ($i < $fieldsCount - 1) {
                $sql .= ',';
            }
        }
        $sql .= ')';
        if ($OnDuplicateFields) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $updates = array();
            foreach ($OnDuplicateFields as $field => $value) {
                if (is_int($field)) {
                    $updates[] = "`$value`=VALUES(`$value`)";
                } else if ($value === NULL) {
                    $updates[] = "`$field`=NULL";
                } else if ($value === true) {
                    $updates[] = "`$field`=1";
                } else if ($value === false) {
                    $updates[] = "`$field`=0";
                } else if ($value instanceof Ddm_Db_Expression) {
                    $updates[] = "`$field`=$value";
                } else {
                    $updates[] = "`$field`=?";
                    $params[] = $value;
                }
            }
            $sql .= implode(', ', $updates);
        }
        $sth = $this->query($sql, $params);
        return $sth->rowCount();
    }

    /**
     * 新增多条记录
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

        $fields = array_keys($rows[0]);
        $sql = 'INSERT';
        if ($useIgnore) $sql .= ' IGNORE';
        $sql .= ' INTO ' . $table . ' (`' . implode('`,`', $fields) . '`) VALUES ';

        $params = array();
        $values = array();

        foreach ($rows as $row) {
            if (!$row) continue;

            $placeholders = array();
            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : NULL;

                if ($value === NULL) {
                    $placeholders[] = 'NULL';
                } else if ($value === true) {
                    $placeholders[] = '1';
                } else if ($value === false) {
                    $placeholders[] = '0';
                } else if ($value instanceof Ddm_Db_Expression) {
                    $placeholders[] = (string)$value;
                } else {
                    $placeholders[] = '?';
                    $params[] = $value;
                }
            }
            $values[] = '(' . implode(',', $placeholders) . ')';
        }

        if (!$values) return 0;
        $sql .= implode(',', $values);

        if ($OnDuplicateFields) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $dups = array();
            foreach ($OnDuplicateFields as $field => $value) {
                if (is_int($field)) {
                    $dups[] = "`$value`=VALUES(`$value`)";
                } else if ($value === NULL) {
                    $dups[] = "`$field`=NULL";
                } else if ($value === true) {
                    $dups[] = "`$field`=1";
                } else if ($value === false) {
                    $dups[] = "`$field`=0";
                } else if ($value instanceof Ddm_Db_Expression) {
                    $dups[] = "`$field`=$value";
                } else {
                    $dups[] = "`$field`=?";
                    $params[] = $value;
                }
            }
            $sql .= implode(',', $dups);
        }

        $sth = $this->query($sql, $params);
        return $sth->rowCount();
    }

    /**
     * 释放结果内存
     * @param PDOStatement $result
     * @return Ddm_Db_PDO_Mysql
     */
    public function freeResult($result)
    {
        if ($result instanceof PDOStatement) {
            $result->closeCursor();
        }
        return $this;
    }

    /**
     * 返回当前连接的MySQL的版本
     * @return string|null
     */
    public function getMysqlVersion()
    {
        $result = $this->query('SELECT VERSION() AS version');
        $row = $result->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : null;
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
     * @return string
     */
    public function getIdentifierQuote()
    {
        return '`';
    }

    /**
     * @param string $log
     * @return void
     */
    protected function _errorLog($log)
    {
        if (defined('SITE_ROOT')) {
            $f = SITE_ROOT.'/data/errors/mysql/mysql-errors.txt';
            $logstring = date('Y-m-d H:i:s')."\r\n-----------------------------------";
            $logstring .= "\r\nURL: ".$_SERVER['REQUEST_URI']."\r\nIP: ".$_SERVER['REMOTE_ADDR']."\r\nUSER_AGENT: ".(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'None')."\r\nMETHOD: ".$_SERVER['REQUEST_METHOD'];
            if(!empty($_POST))$logstring .= "\r\nPOST_DATA: ".print_r($_POST,true);
            if(empty($_SERVER['QUERY_STRING']) && !empty($_GET))$logstring .= "\r\nGET_DATA: ".print_r($_GET,true);
            $logstring .= "\r\n$log";

            if(is_file($f)){
                if (filesize($f) > 4194304) {
                    rename($f, SITE_ROOT.'/data/errors/mysql/mysql-errors-'.date('YmdHis').'.txt');
                } else {
                    Ddm::getHelper('core')->saveFile($f, "\r\n".$logstring, FILE_APPEND);
                    return;
                }
            }

            Ddm::getHelper('core')->saveFile($f, $logstring);
        }
    }
}
