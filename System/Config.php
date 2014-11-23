<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

namespace Net\VoidNull\System;

/**
 * Config
 *
 * @author Ling <lly365@gmail.com>
 */
class Config {

    const SEPARATOR = '.';
    const STORAGE_KEY_NAME = 'database';
    const DATABASE_KEY_NAME = Config::STORAGE_KEY_NAME;
   
    const IGNORE = -1;

    private $config = array();

    function __construct($config) {
        $this->loadConfig($config, FALSE);
    }
    function getAllConfig(){
        return $this->config;
    }
    function loadConfig($config, $append = TRUE){
         if (!$config) {
            return;
        }
        if (is_string($config) && file_exists($config)) {
            $config = require_once $config;
        }
        if ($config && is_array($config)) {
            if(!$append){
                $this->config = $config;
            } else {
                $this->config = array_merge($this->config, $config);
            }
        }
    }

    /**
     * 获取配置项
     * @param string $keyPath 配置项的名称，可以是key path
     * @param mixed $default 配置项不存在时的默认值
     * @return mixed
     */
    function getConfig($keyPath, $default = NULL) {
        return KeyPath::getKeyPathValue($this->config, $keyPath, $default, Config::SEPARATOR);
    }
    private function getItemConfig($mainKey,$keyPath, $item = Config::IGNORE, $default = NULL){
         if (!substr_count($keyPath, Config::SEPARATOR) && $item >= 0) {
            $keyPath .= '.' . $item;
        }
        $keyPath = sprintf('%s.%s', $mainKey, $keyPath);
        return $this->getConfig($keyPath, $default);
    }
    /**
     * 获取用于存储的配置信息
     * <ul>
     * <li>getStorageConfig('host')</li>
     * <li>getStorageConfig('host', 0)</li>
     * <li>getStorageConfig('host', 0, 'localhost')</li>
     * <li>getStorageConfig('host.0')</li>
     * <li>getStorageConfig('host.0', Config::IGNORE, 'localhost')</li>
     * </ul>
     * @param string $keyPath
     * @param int $item
     * @param mixed $default
     * @return mixed
     */
    function getStorageConfig($keyPath, $item = Config::IGNORE, $default = NULL) {
       return $this->getItemConfig(Config::STORAGE_KEY_NAME, $keyPath, $item, $default);
    }
    /**
     * getStorageConfig的别名，获取用于数据库的配置信息
     * @see getStorageConfig
     * @param type $keyPath
     * @param type $item
     * @param type $default
     * @return type
     */
    function getDatabaseConfig($keyPath, $item = Config::IGNORE, $default = NULL) {
        return $this->getStorageConfig($keyPath, $item, $default);
    }

}
