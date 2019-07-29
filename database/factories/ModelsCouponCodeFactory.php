<?php

use Faker\Generator as Faker;

$factory->define(App\Models\CouponCode::class, function (Faker $faker) {
    //首先取得一个随机类型
    $type = $faker->randomElement(array_keys(\App\Models\CouponCode::$typeMap));
    //根据类型生成对应折扣
    $value = $type === \App\Models\CouponCode::TYPE_FIXED ? random_int(1,200) : random_int(1,50);

    //如果是固定金额,则最低订单金额必须要比优惠券金额高0.01元
    if ($type === \App\Models\CouponCode::TYPE_FIXED){
        $minAmount = $value+0.01;
    }else{
        //如果是百分比折扣,有50%概率不需要最低订单金额
        if (random_int(0,100)<50){
            $minAmount = 0;
        }else{
            $minAmount = random_int(100,1000);
        }
    }


    return [
        'name'      =>      join('', $faker->words),
        'code'      =>      \App\Models\CouponCode::findAvailableCode(),
        'type'      =>      $type,
        'value'     =>      $value,
        'total'     =>      1000,
        'used'      =>      0,
        'min_amount'=>      $minAmount,
        'not_before'=>      null,
        'not_after' =>      null,
        'enabled'   =>      true,
    ];
});
