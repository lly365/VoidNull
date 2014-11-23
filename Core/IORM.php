<?php
/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */
namespace Net\VoidNull\Core;

/**
 * ORM interface
 * @author Ling<lly365@gmail.com>
 */
interface IORM {

    const LEFT_JOIN = 'LEFT';
    const RIGHT_JOIN = 'RIGHT';
    const INNER_JOIN = 'INNER';
    const CORSS_JOIN = 'CORSS';
    const SELECT_ALL = 1;
    const SELECT_ROW = 2;
    const SELECT_ONE = 3;
    const LOGIC_DELETE = 1;
    const PHYSICS_DELETE = 1;

    function table($table, $alias = NULL);

    function fields($fields);

    function where($sql, $params = NULL);

    function order($order);

    function limit($number, $offset = NULL);

    function join($table, $alias = NULL, $method = IORM::INNER_JOIN);

    function on($on, $params = NULL);

    function group($group);

    function having($having, $params = NULL);

    function select($mode = IORM::SELECT_ALL);

    function save($data);

    function add($data);

    function edit($data, $id = NULL);

    function delete($id, $mode = IORM::LOGIC_DELETE);

    function count($field = '*');
}
