<?php

namespace Net\VoidNull\Core\Storage;

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

/**
 * storage of MySQLi
 *
 * @author Ling<lly365@gmail.com>
 */
class MySQLi implements \Net\VoidNull\Core\IStorage {

    private $db = NULL;
    private $config = array();
    private $lastQuery = NULL;
    private $stmt = NULL;
    private $lastInsertId = 0;

    function __construct(array $config = NULL) {
        if ($config) {
            $this->config($config);
        }
    }

    public function beginTrans() {
         $this->db->autocommit(FALSE);
         $this->lastQuery = 'SET AUTOCOMMIT=0';
    }

    public function close() {
        if ($this->stmt && $this->stmt instanceof \mysqli_stmt) {
            $this->stmt->close();
            $this->stmt = NULL;
        }
        if ($this->db && $this->db instanceof \mysqli) {
            $this->db->close();
            $this->db = NULL;
        }
    }

    public function commit() {
        $this->db->commit();
        $this->db->autocommit(TRUE);
        $this->lastQuery = 'COMMIT;SET AUTOCOMMIT=1';
    }

    public function config(array $config) {
        $this->config = $config;
    }

    public function execute($sql, $params = NULL) {
        $args = func_get_args();
        call_user_func_array(array($this, 'prepareQuery'), $args);
        $affected_rows = $this->stmt->affected_rows;
        $insert_id = $this->stmt->insert_id;
        $this->lastInsertId = $insert_id;
        return $insert_id ? : $affected_rows;
    }

    public function &getAll($sql, $params = NULL) {
        $args = func_get_args();
        $rs = call_user_func_array(array($this, 'query'), $args);
        return $rs;
    }

    public function getOne($sql, $params = NULL) {
        $args = func_get_args();
        $rs = call_user_func_array(array($this, 'getRow'), $args);
        if ($rs) {
            if (defined('VN_STORAGE_GET_ROW_AS_2D') && VN_STORAGE_GET_ROW_AS_2D) {
                $rs = array_shift($rs);
            }
            $rs = array_shift($rs);
        }
        return $rs;
    }

    public function &getRow($sql, $params = NULL) {
        $args = func_get_args();
        call_user_func_array(array($this, 'prepareQuery'), $args);
        $result = $this->stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (defined('VN_STORAGE_GET_ROW_AS_2D') && VN_STORAGE_GET_ROW_AS_2D) {
                $rs[] = $row;
            } else {
                $rs = $row;
            }
        }
        $this->stmt->free_result();
        return $rs;
    }

    public function isOpened() {
        return ($this->db && $this->db instanceof \mysqli);
    }

    public function lastInsertedID() {
        return $this->lastInsertId;
    }

    public function lastQuery() {
        return $this->lastQuery;
    }

    public function open() {
        if ($this->isOpened()) {
            return;
        }
        $host = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'host', 'localhost');
        $user = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'user', 'voidnull');
        $password = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'password', '');
        $dbname = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'dbname', 'voidnull');
        $hostAndPort = explode(':', $host);
        $host = \Net\VoidNull\Util\Common::tryGetValue($hostAndPort, 0, 'localhost');
        $port = \Net\VoidNull\Util\Common::tryGetValue($hostAndPort, 1, 3306);
        $port = intval($port);
       var_dump($host,$user,$password,$port,$dbname);
        //die;
        $this->db = new \mysqli($host, $user, $password, $dbname, $port);
        if (mysqli_connect_error()) {
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not connect to ' . $host);
        }
    }

    private function prepareQuery() {
        $args = func_get_args();
        $sql = trim(array_shift($args));
        if (preg_match('/^[DELETE|UPDATE]/i', $sql)) {
            if (!$args) {
                throw new \Net\VoidNull\Core\Exception\StorageException('Please specify param(s) for DELETE or UPDATE.');
            }
        }
        if(!$this->stmt = $this->db->prepare($sql)){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not prepare SQL');
        }
        $lastQuery = $sql;
        if ($args) {
            $params = $args;
            $type = array();

            foreach ($params as &$param) {
                $paramType = 's';
                if (is_int($param) || is_long($param)) {
                    $paramType = 'i';
                } else if (is_float($param) || is_double($param)) {
                    $paramType = 'd';
                }
                $type[] = $paramType;
                $newParams[] = &$param;
                $lastQuery = preg_replace('/\?/', '\'' . mysqli_real_escape_string($this->db, $param) . '\'', $lastQuery, 1);
            }
            $type = implode('', $type);
            array_unshift($newParams, $type);
            //var_dump($newParams);
            //return;
            if(!call_user_func_array(array($this->stmt, 'bind_param'), $newParams)){
                 throw new \Net\VoidNull\Core\Exception\StorageException('Can not bind params to SQL');
            }
        }
        if(!$this->stmt->execute()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not execute SQL');
        }
        $this->lastQuery = $lastQuery;
    }

    public function &query($sql, $params = NULL) {
        $args = func_get_args();
        call_user_func_array(array($this, 'prepareQuery'), $args);
        $result = $this->stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rs[] = $row;
        }
        $this->stmt->free_result();
        return $rs;
    }

    public function rollback() {
        if (!$this->isOpened()) {
            throw new \Net\VoidNull\Core\Exception\StorageException('storage is not open');
        }
         $this->db->rollback();
        $this->db->autocommit(TRUE);
        $this->lastQuery = 'ROLLBACK;SET AUTOCOMMIT=0';
    }

}
/* $config = array('host' => 'localhost:3306', 'user' => 'root', 'password' => '', 'dbname' => 'voidnull');
        $db = new Core\Storage\MySQLi($config);
        $db->open();
        //$rs = $db->query('select * from test WHERE id>? ',0);
        //$rs = $db->query('select * from test WHERE id>? and str like ?',0,'test%');
        //$rs = $db->execute('INSERT INTO test VALUES(NULL,?,?)', 'test6','test666666,666');
        //$rs = $db->execute('UPDATE test SET str=? WHERE id=?', 'test5', 5);
        //$rs = $db->execute('delete from test where id=?', 5);
        // $rs = $db->execute('delete from test where id=6 and delete=1');
        //$rs = $db->getAll('select * from test WHERE id>? and str like ?',0,'test%');
        // defined('VN_STORAGE_GET_ROW_AS_2D') || define('VN_STORAGE_GET_ROW_AS_2D', TRUE);
        //$rs = $db->getRow('select * from test WHERE id>? and str like ?',0,'test%');
        //$rs = $db->getOne('select * from test WHERE id>? and str like ?',0,'test%');
        $db->beginTrans();
        $result1 = $db->execute('UPDATE test SET str=? WHERE id=?', 'test5', 1);
        $result2 = $db->execute('delete from test where id=?', 1);
        if ($result1 !== FALSE && $result2 !== FALSE) {
            $db->commit();
        } else {
            $db->rollback();
        }
        var_dump($db->lastQuery());
        //Util\Debug::varDump($rs);*/