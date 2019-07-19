<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'image', 'on_sale', 'rating', 'sold_count', 'review_count', 'price'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'on_sale'   =>  'boolean',
    ];

    /**
     * 一个商品对应多个sku
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skus(){
        return $this->hasMany(ProductSku::class);
    }

}
