<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

namespace Net\VoidNull\System;

/**
 * KeyPath
 *
 * @author Ling<lly365@gmail.com>
 */
class KeyPath {
    const SEPARATOR = '.';
    static function getKeyPathValue($source, $keyPath, $default = NULL, $separator = KeyPath::SEPARATOR){
        if(!($source && $keyPath)){
            return $default;
        }
        $keys = explode($separator, $keyPath);
        $result = $source;
        do{
            $key = array_shift($keys);
            //echo "key: $key<br>";
            $result = \Net\VoidNull\Util\Common::tryGetValue($result, $key, $default);
            //\Net\VoidNull\Util\Debug::varDump($result);
        }while($keys && is_array($keys));
        return $result;
    }
}
