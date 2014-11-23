<?php

/*
 *  VoidNull PHP Framework.
 *  (C) 2014  VoidNull.net
 */

namespace Net\VoidNull\Core;

/**
 * Pagination interface
 * @author Ling <lly365@gmail.com>
 */
interface IPagination {
    const PAGE_PARAM_NAME = 'page';
    function pageList($template = NULL);
    function prePage();
    function nextPage();
    function lastPage();
    function firstPage();
    function pageCount();
}
