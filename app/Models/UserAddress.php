<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'province', 'city', 'district', 'address', 'zip', 'contract_name', 'contract_phone', 'last_used_at'
    ];

    /**
     * @var array
     */
    protected $dates = ['last_used_at'];

    /**
     * 一个地址只属于一个用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(){
        return $this->belongsTo(User::class);
    }

    /**
     * 获取全面地址
     * @return string
     */
    public function getFullAddressAttribute(){
        return "{$this->province}{$this->city}{$this->district}{$this->address}";
    }

}
