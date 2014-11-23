<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

namespace Net\VoidNull\System;

/**
 * Application
 *
 * @author Ling<lly365@gmail.com>
 */
class Application {

    private $request;

    function __construct(Request $request) {
        $this->request = $request;
    }

    function run() {
        $module = $this->request->getModuleName();
        $controller = $this->request->getControllerName();
        $action = $this->request->getActionName();
        
        echo sprintf('%s/%s:%s%s', $module,  ucfirst($controller), strtolower($action), ucfirst(strtolower($this->request->getMethod())));
    }

}
