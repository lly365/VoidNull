<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull;

defined('VN_DS') || define('VN_DS', DIRECTORY_SEPARATOR);
defined('__DIR__') || define('__DIR__', dirname(__FILE__));
defined('VN_FRAMEWORK_ROOT') || define('VN_FRAMEWORK_ROOT', __DIR__ . VN_DS);
defined('VN_ROOT') || define('VN_ROOT', VN_FRAMEWORK_ROOT . '..' . VN_DS);

/**
 * VoidNull main class
 *
 * @author Ling <lly365@gmail.com>
 */
class VoidNull {

    private static $config;
    private static $request;
    private static $app;

    static function config() {
        return self::$config;
    }

    static function setConfig(System\Config $config) {
        self::$config = $config;
    }

    private static function autoloadDirs() {
        return array(
            'System' . VN_DS,
            'Core' . VN_DS,
            'Core' . VN_DS . 'Exception' . VN_DS,
            'Core' . VN_DS . 'Storage' . VN_DS,
            'Util' . VN_DS,
        );
    }

    private static function init() {
        self::createConfig();
        self::filters();
    }

    private static function createConfig() {
        $configFile = require_once VN_ROOT . 'config.php';
        self::$config = new System\Config($configFile);
        //self::$config->loadConfig($c);
    }

    private static function filters() {
        $filters = self::config()->getConfig('filter');
        if ($filters && is_array($filters)) {
            foreach ($filters as $filterClass) {
                $filterObj = new $filterClass();
                if ($filterObj && $filterObj instanceof Core\IFilter) {
                    call_user_func_array(array($filterObj, 'filter'), array(self::$request));
                } else {
                    unset($filterObj);
                    continue;
                }
            }
        }
    }

    private static function app() {
        if (!(self::$app && self::$app instanceof System\Application)) {
            self::$app = new System\Application(self::$request);
        }
        return self::$app;
    }

    static function startup() {
        if (!(self::$request && self::$request instanceof System\Request)) {
            self::$request = new System\Request();
        }
        self::init();
        self::app()->run();
        self::$request->route(self::config()->getConfig('route'));
    }

    static function log($msg) {
        echo $msg, '<br>';
    }

    static function autoloader($className) {
        $dirs = self::autoloadDirs();
        $className = str_replace(array('/', '\\'), VN_DS, $className);
        $classExists = false;
        foreach ($dirs as &$dir) {
            $classFileName = str_replace('Net' . VN_DS . 'VoidNull' . VN_DS . $dir, '', $className);
            $classFileName = VN_FRAMEWORK_ROOT . $dir . $classFileName . '.php';
            $classExists = file_exists($classFileName);
            if ($classExists) {
                require_once $classFileName;
                self::log('loaded - ' . $classFileName . '   ');
                break;
            }
        }
        if (!$classExists) {
            self::log($className . ' is not exists');
        }
    }

}

spl_autoload_register(array('Net\VoidNull\VoidNull', 'autoloader'));
