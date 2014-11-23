<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Util;

/**
 * Utils of Common
 *
 * @author Ling <lly365@gmail.com>
 */
class Common {

    const VALUE_NOTEMPTY = 0;
    const VALUE_ISSET = 1;

    static function  isNotEmpty($obj, $method = Common::VALUE_NOTEMPTY) {
        
        
        return ($method == Common::VALUE_ISSET) ? isset($obj) : (!empty($obj));
    }
    static function isEmpty($obj, $method = Common::VALUE_NOTEMPTY) {
        return !self::isEmpty($obj, $method);
    }

    static function tryGetValue($source, $item, $default = NULL, $method = Common::VALUE_NOTEMPTY) {
      
        $hasItem = false;
        if (is_object($source)) {
            $hasItem = property_exists($source, $item) && self::isNotEmpty($source->$item);
            return $hasItem ? $source->$item : $default;
        }
        if (is_array($source)) {
           
            $hasItem = array_key_exists($item, $source) && self::isNotEmpty($source[$item], $method);
           
            return $hasItem ? $source[$item] : $default;
        }
        return $source;
    }

}
