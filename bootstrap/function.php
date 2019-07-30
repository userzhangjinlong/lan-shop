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

function ngrok_url($routeName, $parameters = [])
{
    //开发环境并且配置了NGORK_URL
    if (app()->environment('local') && $url = config('app.ngrok_url')){
        //route 第三个参数代表是否绝对路径
        return $url.route($routeName, $parameters, false);
    }

    return route($routeName, $parameters);
}