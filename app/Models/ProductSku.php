<?php

namespace App\Models;

use App\Exceptions\InternalExpection;
use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    protected $fillable = [
        'title', 'description', 'price', 'stock'
    ];

    /**
     * sku 属于单独的一个商品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(){
        return $this->belongsTo(Product::class);
    }

    /**
     * 减去sku库存
     * @param $amount
     * @return int
     * @throws InternalExpection
     */
    public function decreaseStock($amount){
        if ($amount < 0){
            throw new InternalExpection('减库存不可能小于0');
        }

        /**
         * 最终执行的 SQL 类似于 update product_skus set stock = stock - $amount where id = $id and stock >= $amount，
         */
        return $this->newQuery()->where('id', $this->id)->where('stock', '>=', $amount)->decrement('stock', $amount);
    }

    /**
     * 加库存
     * @param $amount
     * @return int
     * @throws InternalExpection
     */
    public function addStock($amount)
    {
        if ($amount <= 0){
            throw new InternalExpection('加库存不可小于0');
        }
        return $this->increment('stock', $amount);

    }
}
