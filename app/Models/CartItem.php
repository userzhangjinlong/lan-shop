<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['amount'];

    /**
     * 声明表面表内没有时间
     * @var bool
     */
    public $timestamps = false;

    /**
     * 购物车数据属于用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(){
        return $this->belongsTo(User::class);
    }

    /**
     * 购物车数据属于sku
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productSku(){
        return $this->belongsTo(ProductSku::class);
    }

}
