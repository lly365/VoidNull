<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014 VoidNull.net
 */

namespace Net\VoidNull\Util;

/**
 * Debug
 *
 * @author Ling
 */
class Debug {
    static function varDump(){
        $param_arr = func_get_args();
        echo '<pre class="var_dump">';
        call_user_func_array('var_dump', $param_arr);
        echo '</pre>';
    }
}
