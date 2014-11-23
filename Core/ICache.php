<?php
/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Core;

/**
 *
 * @author Ling <lly365@gmail.com>
 */
interface ICache {
    function &get($key);
    function set($key, $value, $expire);
    function isExpired($key);
    function isExists($key);
    function remove($key);
    function clear($keys = NULL);
}
