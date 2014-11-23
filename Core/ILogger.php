<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Core;

/**
 * Logger interface
 * @author Ling <lly365@gmail.com>
 */
interface ILogger {
    const INFO = 1;
    const WARN = 2;
    const ERROR = 8;
    const DEBUG = 4;
    function log($type, $msg);
    function info($msg);
    function error($msg);
    function warn($msg);
    function debug($msg);
    function show($type = NULL);
}
