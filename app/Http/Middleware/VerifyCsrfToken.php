<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //crsf白名单 添加路由不检测csrf
        'payment/alipay/notify',
        'payment/wecaht/notify',
        'payment/wecaht/refund_notify',
        'installment/alipay/notify',
        'installment/wechat/notify',
    ];
}
