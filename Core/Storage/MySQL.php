<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Core\Storage;

/**
 * Storage of MySQL
 * @deprecated
 * @author Ling <lly365@gmail.com>
 */
class MySQL implements \Net\VoidNull\Core\IStorage {

    private $db = NULL;
    private $config = array();
    private $lastQuery = NULL;

    function __construct(array $config = NULL) {
        if ($config) {
            $this->config($config);
        }
        //$this->open();
        
    }

    function __destruct() {
        $this->close();
    }

    public function beginTrans() {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        //mysql_query('SET AUTOCOMMIT=0',  $this->db);
        
        mysql_query('start transaction',  $this->db);
        $this->lastQuery = 'start transaction';
    }

    public function close() {
        if ($this->isOpened()) {
            mysql_close($this->db);
            $this->db = NULL;
        }
    }

    public function commit() {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        mysql_query('COMMIT',  $this->db);
        $this->lastQuery = 'COMMIT';
        //mysql_query('SET AUTOCOMMIT=1',  $this->db);
    }

    public function execute($sql, $params = NULL) {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        $result = null;
        $args = func_get_args();
        $sql = call_user_func_array(array($this,'prepareStatement'), $args);
        $query = mysql_query($sql, $this->db);
        if(!$query){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not query sql');
        }
        $row = mysql_affected_rows($this->db);
        $id = mysql_insert_id($this->db);
        $result = $id ?: $row;
        $this->lastQuery = $sql;
        return $result ?: 0;
    }

    public function &getAll($sql, $params = NULL) {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        $rs = call_user_func_array(array($this, 'query'), func_get_args());
        return $rs;
    }

    public function getOne($sql, $params = NULL) {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        $rs = NULL;
        $args = func_get_args();
        $result = call_user_func_array(array($this,'getRow'), $args);
        if($result && is_array($result)){
            $rs = array_shift($result);
            unset($result);
        }
        return $rs;
    }

    public function &getRow($sql, $params = NULL) {
         if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        $result = null;
        $args = func_get_args();
        $sql = call_user_func_array(array($this,'prepareStatement'), $args);
        $query = mysql_query($sql, $this->db);
        if(!$query){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not query sql');
        }
        if($row = mysql_fetch_assoc($query)){
            $result = $row;
        }
        mysql_free_result($query);
        $this->lastQuery = $sql;
        return $result;
    }

    public function open() {
        if($this->isOpened()){
            return;
        }
        $host = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'host', 'localhost');
        $user = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'user', 'voidnull');
        $password = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'password', '');
        $dbname = \Net\VoidNull\Util\Common::tryGetValue($this->config, 'dbname', 'voidnull');
      
        $this->db = @mysql_connect($host, $user, $password);
        if (!$this->db) {
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not open the connect, using: '.$user.' at '.$host);
        }
        if(!mysql_select_db($dbname, $this->db)){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not use the database:'.$dbname);
        }
        if(!mysql_query('SET NAMES UTF8', $this->db)){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not set this connect\'s encode');
        }
    }

    private function prepareStatement(){
        $args = func_get_args();
        $sql = array_shift($args);
        if($args){
            $args = array_map(array($this, 'escapeParam'),$args);
            foreach ($args as $arg){
                $sql = preg_replace('/\?/', "'$arg'", $sql, 1);
            }
        } 
        return $sql;
    }
    public function &query($sql, $params = NULL) {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        $result = null;
        $args = func_get_args();
        $sql = call_user_func_array(array($this,'prepareStatement'), $args);
        $query = mysql_query($sql, $this->db);
        if(!$query){
            throw new \Net\VoidNull\Core\Exception\StorageException('Can not query sql');
        }
        while($row = mysql_fetch_assoc($query)){
            $result[] = $row;
        }
        mysql_free_result($query);
        $this->lastQuery = $sql;
        return $result;
    }

    public function rollback() {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
         mysql_query('ROLLBACK', $this->db);
         $this->lastQuery = 'ROLLBACK';
        //mysql_query('SET AUTOCOMMIT=1', $this->db);
    }

    public function config(array $config) {
        $this->config = $config;
    }

    public function isOpened() {
        return ($this->db && is_resource($this->db));
    }

    private function escapeParam($param){
        return $this->isOpened() ?  mysql_real_escape_string($param, $this->db) : mysql_real_escape_string($param);
    }

    public function lastInsertedID() {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        
        return $this->getOne('SELECT last_insert_id()');
    }

    public function lastQuery() {
        if(!$this->isOpened()){
            throw new \Net\VoidNull\Core\Exception\StorageException('Connect is not open');
        }
        return $this->lastQuery;
    }

}

/*$mysql = new Core\Storage\MySQL(array('host'=>'localhost','user'=>'root','password'=>'abc,123'));
       $mysql->open();
       //$rs = $mysql->query('select * from test where id<>? and str like ?', 10, "test2%");
      // $rs = $mysql->getAll('select * from test where id<>? and str like ?', 10, "test%");
        //$rs = $mysql->getRow('select * from test where id<>? and str like ?', 10, "test%");
       //$rs = $mysql->getOne('select * from test where id<>? and str like ?', 10, "test%");
       //$rs = $mysql->execute('INSERT INTO test VALUES(NULL,?,?)', 'test3', 'test3333');
       //$rs = $mysql->execute('UPDATE test SET str=? WHERE id=?','test3333', 3);
       $mysql->beginTrans();
       $a = $mysql->execute('update test set str=? where id=?', 1111, 1);
       $b = $mysql->execute('update test set str=? where id=?', 3333,3);
       if($a && $b){
           $mysql->commit();
       } else {
       $mysql->rollback();
       }
       $mysql->close();
        * 
        */