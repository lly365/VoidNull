<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

namespace Net\VoidNull\System;

/**
 * Request
 *
 * @author Ling<lly365@gmail.com>
 */
class Request {
    
    const ROUTE_TYPE_URL = 0;
    const ROUTE_TYPE_PATHINFO = 1;
    const ROUTE_TYPE_SEGMENT = 2;
    const ROUTE_TYPE_MIXED = 3;

    /**
     * 获取当前URI。如：/index.php/a/b/c/d?id=999&ddd=555
     * @return String
     */
    public
    static function getUri() {
        return self::getServerValue('REQUEST_URI');
    }

    /**
     * 获取当前PathInfo。如：/a/b/c/d
     * @return String
     */
    public static function getPathInfo() {
        return self::getServerValue('PATH_INFO');
    }

    /**
     * 获取当前文件名。如：/index.php
     * @see getUri()
     * @see getPHPSelf()
     * @return String
     */
    public static function getScriptName() {
        return self::getServerValue('SCRIPT_NAME', '/index.php');
    }

    /**
     * 获取当前执行的PHP。如：/index.php/a/b/c/d
     * @return String
     */
    public static function getPHPSelf() {
        return self::getServerValue('PHP_SELF', self::getScriptName());
    }

    /**
     * 获取请求方式
     * @return String
     */
    public static function getRequestMethod() {
        return self::getServerValue('REQUEST_METHOD');
    }

    public static function getUrlParams() {
        return $_GET;
    }

    public static function getPostParams() {
        return $_POST;
    }

    public function route($type) {
        switch ($type) {
            case self::ROUTE_TYPE_PATHINFO:
                $this->routePathInfo();
                break;
            case self::ROUTE_TYPE_SEGMENT:
                $this->routeSegment();
                break;
            case self::ROUTE_TYPE_MIXED:
                $this->routeMixed();
                break;
            default:
                $this->routeUrl();
                break;
        }
    }

    private static function getServerValue($key, $default = '') {
        return \Net\VoidNull\Util\Common::tryGetValue($_SERVER, $key, $default);
    }

    private function routeMixed() {
        $pathInfo = (array)($this->mixedCastToArray());
        //var_dump($pathInfo);
        $getParams = (array)self::getUrlParams();
        //var_dump($getParams);
        
        $moduleGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'm', 'www');
        $controllerGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'c', 'index');
        $actionGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'a', 'index');


        $modulePathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'm', $moduleGetParam);
        $controllerPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'c', $controllerGetParam);
        $actionPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'a', $actionGetParam);

        $pathInfo = array_merge($getParams, $pathInfo);
        $pathInfo['m'] = $modulePathInfo;
        $pathInfo['c'] = $controllerPathInfo;
        $pathInfo['a'] = $actionPathInfo;

        $_GET = $pathInfo;
        $url[] = $pathInfo['m'];
        $url[] = $pathInfo['c'];
        $url[] = $pathInfo['a'];
        unset($pathInfo['m']);
        unset($pathInfo['c']);
        unset($pathInfo['a']);
        $url = implode('/', $url);
        $arr = array();
        foreach ($pathInfo as $k => $val) {
            $arr[] = $k;
            $arr[] = $val;
        }
        $url = sprintf('%s/%s', $url, implode('/', $arr));
        if (!empty($getParams)) {
            header('location: ' . self::getScriptName() . '/' . $url);
            exit;
        }

    }

    private function routeUrl() {
        $pathInfo = (array)$this->pathInfoCastToArray();
        //var_dump($pathInfo);
        $getParams = (array)self::getUrlParams();
        //var_dump($getParams);

        $modulePathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'm', 'www');
        $controllerPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'c', 'index');
        $actionPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'a', 'index');

        $moduleGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'm', $modulePathInfo);
        $controllerGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'c', $controllerPathInfo);
        $actionGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'a', $actionPathInfo);


        $getParams = array_merge($pathInfo, $getParams);
        $getParams['m'] = $moduleGetParam;
        $getParams['c'] = $controllerGetParam;
        $getParams['a'] = $actionGetParam;

        $_GET = $getParams;
        $url = http_build_query($getParams);
       // echo $url;
        if (!empty($pathInfo)) {
            header('location: ' . self::getScriptName() . '?' . $url);
            exit;
        }
    }

    private function routeSegment() {
        $pathInfo = (array)($this->segmentCastToArray());
        //var_dump($pathInfo);
        $getParams = (array)self::getUrlParams();
        //var_dump($getParams);

        $moduleGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'm', 'www');
        $controllerGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'c', 'index');
        $actionGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'a', 'index');


        $modulePathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'm', $moduleGetParam);
        $controllerPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'c', $controllerGetParam);
        $actionPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'a', $actionGetParam);


        //echo '<pre>';
        //var_dump($pathInfo);

        // var_dump($getParams);
        $pathInfo[0] = $modulePathInfo;
        $pathInfo[1] = $controllerPathInfo;
        $pathInfo[2] = $actionPathInfo;
        unset($pathInfo['m']);
        unset($pathInfo['c']);
        unset($pathInfo['a']);
        unset($getParams['m']);
        unset($getParams['c']);
        unset($getParams['a']);
        foreach ($getParams as $value) {
            $pathInfo[] = $value;
        }
        ksort($pathInfo);
        //var_dump($pathInfo);
        $_GET = $pathInfo;
        $url = (implode('/', $pathInfo));
        if (!empty($getParams)) {
            header('location: ' . self::getScriptName() . '/' . $url);
            exit;
        }
    }

    private function routePathInfo() {
        $pathInfo = (array)$this->pathInfoCastToArray();
        //var_dump($pathInfo);
        $getParams = (array)self::getUrlParams();
        //var_dump($getParams);

        $moduleGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'm', 'www');
        $controllerGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'c', 'index');
        $actionGetParam = \Net\VoidNull\Util\Common::tryGetValue($getParams, 'a', 'index');


        $modulePathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'm', $moduleGetParam);
        $controllerPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'c', $controllerGetParam);
        $actionPathInfo = \Net\VoidNull\Util\Common::tryGetValue($pathInfo, 'a', $actionGetParam);

        $pathInfo = array_merge($getParams, $pathInfo);
        $pathInfo['m'] = $modulePathInfo;
        $pathInfo['c'] = $controllerPathInfo;
        $pathInfo['a'] = $actionPathInfo;

        $_GET = $pathInfo;
        $url = array();
        foreach ($pathInfo as $key => $value) {
            $url[] = $key;
            $url[] = $value;
        }
        $url = implode('/', $url);
        if (!empty($getParams)) {
            header('location: ' . self::getScriptName() . '/' . $url);
            exit;
        }

    }

    private function pathInfoCastToArray() {
        $pathInfo = self::getPathInfo();
        if (!$pathInfo) {
            return null;
        }
        $pathInfo = trim($pathInfo, '/');
        //var_dump($pathInfo);
        $arr = explode('/', $pathInfo);
        //var_dump($arr);
        if (!$arr) {
            return null;
        }
        $len = count($arr);
        $return = array();
        for ($i = 0; $i < $len; $i += 2) {
            $key = \Net\VoidNull\Util\Common::tryGetValue($arr, $i, $i);
            $value = \Net\VoidNull\Util\Common::tryGetValue($arr, $i + 1, '');
            if ($value) {

                $return[$key] = $value;
            }
        }
        return $return;
    }

    private function mixedCastToArray() {
        $pathInfo = self::getPathInfo();
        if (!$pathInfo) {
            return null;
        }
        $pathInfo = trim($pathInfo, '/');
        $arr = explode('/', $pathInfo);
        if (!$arr) {
            return null;
        }
        $len = count($arr);
        $return = array();
        if (!empty($arr[0])) {
            $return['m'] = $arr[0];
            unset($arr[0]);
        }
        if (!empty($arr[1])) {
            $return['c'] = $arr[1];
            unset($arr[1]);
        }
        if (!empty($arr[2])) {
            $return['a'] = $arr[2];
            unset($arr[2]);
        }
        if ($len <= 3) {
            return $return;
        }
        $keys = array_keys($arr);
        for ($i = 0, $j = count($keys); $i < $j; $i += 2) {
            $keyKey = \Net\VoidNull\Util\Common::tryGetValue($keys, $i, NULL);
            $valueKey = \Net\VoidNull\Util\Common::tryGetValue($keys, $i + 1);
            $key = \Net\VoidNull\Util\Common::tryGetValue($arr, $keyKey);
            $value = \Net\VoidNull\Util\Common::tryGetValue($arr, $valueKey);
            if ((!empty($key)) && (!empty($value))) {
                $return[$key] = $value;
            }
        }
        return $return;
    }

    private function segmentCastToArray() {
        $pathInfo = self::getPathInfo();
        if (!$pathInfo) {
            return null;
        }
        $pathInfo = trim($pathInfo, '/');
        $arr = explode('/', $pathInfo);
        if (!$arr) {
            return null;
        }
        $len = count($arr);
        $return = array();

        for ($i = 0; $i < $len; $i++) {
            $key = $i;
            $value = \Net\VoidNull\Util\Common::tryGetValue($arr, $i, '');
            if (in_array($key, array(0, 1, 2))) {
                $keys = array('m', 'c', 'a');
                $key = $keys[$key];
            } else {
                //  $key -= 3;
            }
            if ($value) {
                $return[$key] = $value;
            }
        }
        return $return;
    }


}
