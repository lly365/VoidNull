<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Core;

/**
 * Storage interface
 * @author Ling<lly365@gmail.com>
 */
interface IStorage {

    function open();

    function isOpened();

    function close();

    function &query($sql, $params = NULL);

    function execute($sql, $params = NULL);

    function beginTrans();

    function rollback();

    function commit();

    function &getAll($sql, $params = NULL);

    function &getRow($sql, $params = NULL);

    function getOne($sql, $params = NULL);

    /**
     * 配置
     * @param array $config
     */
    function config(array $config);
    function lastQuery();
    function lastInsertedID();
}
