<?php

use Faker\Generator as Faker;

$factory->define(App\Models\UserAddress::class, function (Faker $faker) {
    $addresses = [
        ["四川省", "成都市", "武侯区"],
        ["四川省", "德阳市", "中江县"],
        ["江苏省", "南京市", "浦口区"],
        ["江苏省", "苏州市", "相城区"],
        ["广东省", "深圳市", "福田区"],
    ];
    $address   = $faker->randomElement($addresses);

    return [
        'province'      => $address[0],
        'city'          => $address[1],
        'district'      => $address[2],
        'address'       => sprintf('第%d街道第%d号', $faker->randomNumber(2), $faker->randomNumber(3)),
        'zip'           => $faker->postcode,
        'contact_name'  => $faker->name,
        'contact_phone' => $faker->phoneNumber,
    ];
});
