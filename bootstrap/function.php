<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-17
 * Time: 上午11:14
 *
 * 自定义公共辅助函数
 */

/**
 * @return mixed
 */
function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}