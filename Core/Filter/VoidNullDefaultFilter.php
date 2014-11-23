<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

namespace Net\VoidNull\Core\Filter;

/**
 * Default Filter of VoidNull framework
 *
 * @author Ling<lly365@gmail.com>
 */
class VoidNullDefaultFilter implements \Net\VoidNull\Core\IFilter {
    public function filter($request) {
        $this->header();
    }
    private function header(){
        header('Content-type:text/html;charset=UTF-8');
    }
}
