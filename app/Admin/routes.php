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

    //广告管理
    $router->get('advs', 'AdvsController@index');
    $router->get('advs/create', 'AdvsController@create');
    $router->post('advs', 'AdvsController@store');
    $router->get('advs/{id}/edit', 'AdvsController@edit');
    $router->put('advs/{id}', 'AdvsController@update');
    $router->delete('advs/{id}', 'AdvsController@destory');
    $router->get('api/advs', 'AdvsController@apiIndex');

    //广告图片管理
    $router->get('advimages/{advid}', 'AdvImageController@index');
    $router->get('advimages/{advid}/create', 'AdvImageController@create');
    $router->post('advimages/{advid}', 'AdvImageController@store');

    //商品管理
    $router->get('products', 'ProductController@index');
    $router->get('products/create', 'ProductController@create');
    $router->post('products', 'ProductController@store');
    $router->get('products/{id}/edit', 'ProductController@edit');
    $router->put('products/{id}', 'ProductController@update');

    //订单管理
    $router->get('orders', 'OrdersController@index')->name('admin.orders.index');
    $router->get('orders/{order}', 'OrdersController@show')->name('admin.orders.show');
    $router->post('orders/{order}/ship','OrdersController@ship')->name('admin.orders.ship');
    $router->post('orders/{order}/refund', 'OrdersController@handleRefund')->name('admin.orders.handle_refund');

    //优惠券
    $router->get('coupon_codes', 'CouponCodesController@index');
    $router->get('coupon_codes/create', 'CouponCodesController@create');
    $router->post('coupon_codes', 'CouponCodesController@store');
    $router->get('coupon_codes/{id}/edit','CouponCodesController@edit');
    $router->put('coupon_codes/{id}','CouponCodesController@update');
    $router->delete('coupon_codes/{id}', 'CouponCodesController@destroy');

    //商品分类
    $router->get('categories', 'CategoriesController@index');
    $router->get('categories/create', 'CategoriesController@create');
    $router->get('categories/{id}/edit', 'CategoriesController@edit');
    $router->post('categories', 'CategoriesController@store');
    $router->put('categories/{id}', 'CategoriesController@update');
    $router->delete('categories/{id}', 'CategoriesController@destory');
    $router->get('api/categories', 'CategoriesController@apiIndex');

    //众筹商品
    $router->get('crowdfunding_products', 'CrowdfundingProductsController@index');
    $router->get('crowdfunding_products/create', 'CrowdfundingProductsController@create');
    $router->get('crowdfunding_products/{id}/edit', 'CrowdfundingProductsController@edit');
    $router->post('crowdfunding_products', 'CrowdfundingProductsController@store');
    $router->put('crowdfunding_products/{id}', 'CrowdfundingProductsController@update');

    //秒杀商品
    $router->get('seckill_products', 'SeckillProductsController@index');
    $router->get('seckill_products/create', 'SeckillProductsController@create');
    $router->post('seckill_products', 'SeckillProductsController@store');
    $router->get('seckill_products/{id}/edit', 'SeckillProductsController@edit');
    $router->put('seckill_products/{id}', 'SeckillProductsController@update');

});
