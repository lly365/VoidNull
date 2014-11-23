<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Core;

/**
 * 主从分离
 *
 * @author Ling <lly365@gmail.com>
 */
class MasterSlave implements IStorage {

    private $slaveStorage = NULL, $masterStorage = NULL;
    //private $storageInstance = NULL;
    //private $storage = NULL;
    private $config = array();
    private $slaveAction = array();
    private $isTrans = FALSE;
    private $usingSlaveStorage = FALSE;
    private $mastConfig = array();
    private $slaveConfig = array();
    private $isOnlyMaster = false;
    private $lastQuery = NULL;

    /**
     * 实例化一个主从分离
     * @param type $config
     */
    function __construct($config) {
        $config = (array) $config;
        $this->config($config);
        $this->slaveAction = array('SELECT');
    }

    /**
     * 析构
     */
    function __destruct() {
        $this->close();
    }

    /**
     * 处理配置信息
     */
    private function processConfig() {
        $masterHost = \Net\VoidNull\VoidNull::config()->getStorageConfig('host.0', \Net\VoidNull\System\Config::IGNORE, 'localhost');
        $masterUser = \Net\VoidNull\VoidNull::config()->getStorageConfig('user.0', \Net\VoidNull\System\Config::IGNORE, 'voidnull');
        $masterPassword = \Net\VoidNull\VoidNull::config()->getStorageConfig('password.0', \Net\VoidNull\System\Config::IGNORE, '');
        $masterDBName = \Net\VoidNull\VoidNull::config()->getStorageConfig('dbname.0', \Net\VoidNull\System\Config::IGNORE, '');
        $masterDriver = \Net\VoidNull\VoidNull::config()->getStorageConfig('driver.0', \Net\VoidNull\System\Config::IGNORE, 'mysqli');
        //$this->storage = $masterDriver;

        $hosts = (array) \Net\VoidNull\VoidNull::config()->getConfig(\Net\VoidNull\System\Config::STORAGE_KEY_NAME . '.host');
        $hostsNum = count($hosts);
        $this->isOnlyMaster = ($hostsNum == 1);
        if (!$this->isOnlyMaster) {
            if ($hostsNum == 2) {
                $slaveIndex = 1;
            } else {
                $slaveIndex = rand(1, $hostsNum - 1);
            }
            //\Net\VoidNull\Util\Debug::varDump($slaveIndex);
            $slaveHost = \Net\VoidNull\VoidNull::config()->getStorageConfig('host', $slaveIndex, $masterHost);
            $slaveUser = \Net\VoidNull\VoidNull::config()->getStorageConfig('user', $slaveIndex, $masterUser);
            $slavePassword = \Net\VoidNull\VoidNull::config()->getStorageConfig('password', $slaveIndex, $masterPassword);
            $slaveDBName = \Net\VoidNull\VoidNull::config()->getStorageConfig('dbname', $slaveIndex, $masterDBName);
            $slaveDriver = \Net\VoidNull\VoidNull::config()->getStorageConfig('driver', $slaveIndex, $masterDriver);
            //$this->storage = $slaveDriver;
        } else {
            $slaveHost = $masterHost;
            $slaveUser = $masterUser;
            $slavePassword = $masterUser;
            $slaveDBName = $masterDBName;
            $slaveDriver = $masterDriver;

            //$this->storage = $masterDriver;
        }

        $this->mastConfig = array(
            'host' => $masterHost,
            'user' => $masterUser,
            'password' => $masterPassword,
            'dbname' => $masterDBName,
            'driver' => $masterDriver,
        );
        $this->slaveConfig = array(
            'host' => $slaveHost,
            'user' => $slaveUser,
            'password' => $slavePassword,
            'dbname' => $slaveDBName,
            'driver' => $slaveDriver,
        );

        //\Net\VoidNull\Util\Debug::varDump($this->mastConfig,  $this->slaveConfig);
    }

    /**
     * 是否使用从存储
     * @return boolean
     */
    function usingSlaveStorage() {
        if (!$this->isTrans) {
            return $this->usingSlaveStorage;
        } else {
            return FALSE;
        }
    }

    /**
     * 判断sql语句是否是一个使用从存储的语句
     * @param string $sql
     * @return boolean
     */
    function isSlaveAction($sql) {
        list($action) = explode(' ', $sql);
        $action = strtoupper($action);
        return in_array($action, $this->slaveAction);
    }

    /**
     * 判断sql语句是否是一个使用主存储的语句
     * @param string $sql
     * @return boolean
     */
    function isMasterAction($sql) {
        return !$this->isSlaveAction($sql);
    }

    /**
     * 开启事务
     */
    public function beginTrans() {
        $this->isTrans = TRUE;
        $this->open();
        $this->masterStorage->beginTrans();
        $this->setLastQuery(FALSE);
    }

    /**
     * 关闭连接
     */
    public function close() {
        if ($this->masterStorage && $this->masterStorage instanceof IStorage && $this->masterStorage->isOpened()) {
            $this->masterStorage->close();
            $this->masterStorage = NULL;
        }
        if ($this->slaveStorage && $this->slaveStorage instanceof IStorage && $this->slaveStorage->isOpened()) {
            $this->slaveStorage->close();
            $this->slaveStorage = NULL;
        }
    }

    /**
     * 提交事务
     */
    public function commit() {
        $this->isTrans = TRUE;
        $this->open();
        $this->masterStorage->commit();
        $this->isTrans = FALSE;
        $this->setLastQuery(FALSE);
    }

    /**
     * 配置信息
     * @param array $config
     */
    public function config(array $config) {
        $this->config = $config;
        $this->processConfig();
    }

    /**
     * 执行增、删、改操作
     * @param string $sql
     * @param mixed $params
     * @return int 受影响的行数或新增的id
     */
    public function execute($sql, $params = NULL) {
        $this->usingSlaveStorage = $this->isSlaveAction($sql);
        $this->open();
        $storage = $this->usingSlaveStorage() ? $this->slaveStorage : $this->masterStorage;
        $args = func_get_args();
        $rs = call_user_func_array(array($storage, 'execute'), $args);
        $this->setLastQuery($this->usingSlaveStorage());
        return $rs;
    }

    /**
     * 获取所有记录
     * @param string $sql
     * @param mixed $params
     * @return array | null 如果有记录，返回2维数组
     */
    public function &getAll($sql, $params = NULL) {
        $this->usingSlaveStorage = $this->isSlaveAction($sql);
        $this->open();
        $storage = $this->usingSlaveStorage() ? $this->slaveStorage : $this->masterStorage;
        $args = func_get_args();
        $rs = call_user_func_array(array($storage, 'getAll'), $args);
        return $rs;
    }

    /**
     * 获取第1行第1列
     * @param string $sql
     * @param mixed $params
     * @return mixed
     */
    public function getOne($sql, $params = NULL) {
        $this->usingSlaveStorage = $this->isSlaveAction($sql);
        $this->open();
        $storage = $this->usingSlaveStorage() ? $this->slaveStorage : $this->masterStorage;
        $args = func_get_args();
        $rs = call_user_func_array(array($storage, 'getOne'), $args);
        return $rs;
    }

    /**
     * 获取第1行
     * @param type $sql
     * @param type $params
     * @return array|null 如果有记录，返回1维数组
     */
    public function &getRow($sql, $params = NULL) {
        $this->usingSlaveStorage = $this->isSlaveAction($sql);
        $this->open();
        $storage = $this->usingSlaveStorage() ? $this->slaveStorage : $this->masterStorage;
        $args = func_get_args();
        $rs = call_user_func_array(array($storage, 'getRow'), $args);
        return $rs;
    }

    /**
     * 连接是否开启
     * @return type
     */
    public function isOpened() {
        return array($this->masterStorage && $this->masterStorage instanceof IStorage && $this->masterStorage->isOpened(), $this->slaveStorage && $this->slaveStorage instanceof IStorage && $this->slaveStorage->isOpened());
    }

    /**
     * 获取最后插入的id
     * @return int
     */
    public function lastInsertedID() {
        if ($this->masterStorage->isOpened()) {
            return $this->masterStorage->lastInsertedID();
        }
        return 0;
    }

    /**
     * 获取最后的查询
     * @return type
     */
    public function lastQuery() {
        return $this->lastQuery;
    }

    /**
     * 打开存储连接
     * @param int $type 1：从存储；0：主存储
     */
    private function openStorage($type) {
        if ($type == 1) {
            //\Net\VoidNull\VoidNull::log('使用从数据库 ' . implode(', ', $this->slaveConfig));
            //return;
            $config = $this->slaveConfig;
            $class = \Net\VoidNull\Util\Common::tryGetValue($config, 'driver', 'Net\VoidNull\Core\Storage\MySQLi');
            if (!($this->slaveStorage && $this->slaveStorage instanceof IStorage)) {
                $this->slaveStorage = new $class($config);
            }
            if (!($this->slaveStorage->isOpened())) {
                $this->slaveStorage->open();
            }
        } else {
            //\Net\VoidNull\VoidNull::log('使用主数据库 ' . implode(', ', $this->mastConfig));
            //return;
            $config = $this->mastConfig;
            $class = \Net\VoidNull\Util\Common::tryGetValue($config, 'driver', 'Net\VoidNull\Core\Storage\MySQLi');
            if (!($this->masterStorage && $this->masterStorage instanceof IStorage)) {
                $this->masterStorage = new $class($config);
            }
            if (!($this->masterStorage->isOpened())) {
                $this->masterStorage->open();
            }
        }
    }

    /**
     * 打开连接
     */
    public function open() {

        if ($this->usingSlaveStorage()) {
            if (!$this->isOnlyMaster) {
                $this->openStorage(1);
                //\Net\VoidNull\VoidNull::log('--===--SLAVE');
            } else {
                $this->openStorage(0);
                $this->slaveStorage = $this->masterStorage;
                //\Net\VoidNull\VoidNull::log('--===--SLAVE WITH MASTER');
            }
        } else {
            $this->openStorage(0);
            //\Net\VoidNull\VoidNull::log('MASTER');
        }
        //\Net\VoidNull\Util\Debug::varDump($this->masterStorage,  $this->slaveStorage);
    }

    /**
     * 执行查询
     * @param type $sql
     * @param type $params
     * @return type
     */
    public function &query($sql, $params = NULL) {
        $this->usingSlaveStorage = $this->isSlaveAction($sql);
        $this->open();
        $storage = $this->usingSlaveStorage() ? $this->slaveStorage : $this->masterStorage;
        $args = func_get_args();
        $rs = call_user_func_array(array($storage, 'query'), $args);
        $this->setLastQuery($this->usingSlaveStorage());
        return $rs;
    }

    /**
     * 设置最后查询的sql
     * @param boolean $isSlave
     */
    private function setLastQuery($isSlave) {
        $storage = $isSlave ? $this->slaveStorage : $this->masterStorage;
        $sql = $storage->lastQuery();
        $this->lastQuery = sprintf('%s - [%s]', $sql, $isSlave ? 'SLAVE' : 'MASTER');
    }

    /**
     * 回滚事务
     */
    public function rollback() {
        $this->isTrans = TRUE;
        $this->open();
        $this->masterStorage->rollback();
        $this->isTrans = FALSE;
        $this->setLastQuery(FALSE);
    }

}
/*$ms = new Core\MasterSlave($config);
      //$ms->beginTrans();
       self::log($ms->lastQuery());
      $rs = $ms->query('select * from test');
      self::log($ms->lastQuery());
      $rs = $ms->execute('UPDATE test SET str=? WHERE id=?', '111111test!',1);
      self::log($ms->lastQuery());
      //$ms->commit();
      // self::log($ms->lastQuery());
      Util\Debug::varDump($ms->isOpened());
      $ms->close();
      var_dump($rs);*/