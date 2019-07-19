<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    //用户管理
    $router->get('users', 'UsersController@index');
    //商品管理
    $router->get('products', 'ProductController@index');
    $router->get('products/create', 'ProductController@create');
    $router->post('products', 'ProductController@store');
    $router->get('products/{id}/edit', 'ProductController@edit');
    $router->put('products/{id}', 'ProductController@update');

});
