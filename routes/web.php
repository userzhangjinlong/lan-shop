<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//首页
Route::get('/', 'PagesController@root')->name('root');
//商品列表
Route::get('products', 'ProductsController@index')->name('products.index');
Route::post('seckill_orders', 'OrdersController@seckill')->name('seckill_orders.store')->middleware('random_drop:80'); //百分比限流
Auth::routes();
Route::group(['middleware' => 'auth'], function() {
    //用户邮箱验证
    Route::get('/email_verify_notice', 'PagesController@emailVerifyNotice')->name('email_verify_notice');
    Route::get('/email_verification/verify', 'EmailVerificationController@verify')->name('email_verification.verify');
    Route::get('/email_verification/send', 'EmailVerificationController@send')->name('email_verification.send');
    Route::group(['middleware' => 'email_verified'], function() {
        //用户收货地址
        Route::get('/user_addresses', 'UserAddressController@index')->name('user_addresses.index');
        Route::get('user_addresses/create', 'UserAddressController@create')->name('user_addresses.create');
        Route::post('user_addresses', 'UserAddressController@store')->name('user_addresses.store');
        Route::get('user_addresses/{user_address}', 'UserAddressController@edit')->name('user_addresses.edit');
        Route::put('user_addresses/{user_address}', 'UserAddressController@update')->name('user_addresses.update');
        Route::delete('user_addresses/{user_address}', 'UserAddressController@destroy')->name('user_addresses.destroy');

        //商品收藏
        Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
        Route::delete('products/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');
        Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');

        //购物车
        Route::post('cart', 'CartController@add')->name('cart.add');
        Route::get('cart', 'CartController@index')->name('cart.index');
        Route::delete('cart/{sku}', 'CartController@remove')->name('cart.remove');

        //订单
        Route::post('orders', 'OrdersController@store')->name('orders.store');
        Route::get('orders', 'OrdersController@index')->name('orders.index');
        Route::get('orders/{order}', 'OrdersController@show')->name('orders.show');
        Route::post('orders/{order}/received', 'OrdersController@received')->name('orders.received');
        Route::get('orders/{order}/review', 'OrdersController@review')->name('orders.review.show');
        Route::post('orders/{order}/review', 'OrdersController@sendReview')->name('orders.review.store');
        Route::post('orders/{order}/apply_refund', 'OrdersController@applyRefund')->name('orders.apply_refund');
        Route::post('crowdfunding_orders', 'OrdersController@crowdfunding')->name('crowdfunding_orders.store');
        //支付宝支付测试
        /*Route::get('alipay', function() {
            return app('alipay')->web([
                'out_trade_no' => time(),
                'total_amount' => '0.1',
                'subject' => 'test subject - 测试',
            ]);
        });*/

        //支付
        Route::get('payment/{order}/alipay', 'PaymentController@payByAlipay')->name('payment.alipay');
        Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');
        Route::get('payment/{order}/wechat', 'PaymentController@payByWechat')->name('payment.wechat');
        Route::post('payment/{order}/installment', 'PaymentController@payByInstallment')->name('payment.installment');

        //优惠券
        Route::get('coupon_codes/{code}', 'CouponCodesController@show')->name('coupon_codes.show');

        //分期付款列表
        Route::get('installments', 'InstallmentsController@index')->name('installments.index');
        Route::get('installments/{installment}', 'InstallmentsController@show')->name('installments.show');
        Route::get('installments/{installment}/alipay', 'InstallmentsController@payByAlipay')->name('installments.alipay');
        Route::get('installments/alipay/return', 'InstallmentsController@alipayReturn')->name('installments.alipay.return');
        Route::get('installments/{installment}/wecaht', 'InstallmentsController@payByWechat')->name('installments.wechat');

    });

});
//收藏商品列表和商品详情列表参数冲突解决
Route::get('products/{product}', 'ProductsController@show')->name('products.show');

//支付宝支付服务器回调
Route::post('payment/alipay/notify','PaymentController@alipayNotify')->name('payment.alipay.notify');
//微信支付服务器回调
Route::post('payment/wechat/notify','PaymentController@wechatNotify')->name('payment.wechat.notify');
//微信退款回调
Route::post('payment/wechat/refund_notify', 'PaymentController@wechatRefundNotify')->name('payment.wechat.refund_notify');
//支付宝分期付款服务器回调
Route::post('installment/alipay/notify', 'InstallmentsController@alipayNotify')->name('installments.alipay.notify');
//微信分期付款服务器回调
Route::post('installment/wechat/notify', 'InstallmentsController@wechatNotify')->name('installments.wechat.notify');
//微信分期退款回调
Route::post('installment/wechat/refund_notify', 'InstallmentsController@wechatRefundNotify')->name('installments.wechat.refund_notify');