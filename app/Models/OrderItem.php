<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'amount', 'price', 'rating', 'review', 'reviewed_at'
    ];

    /**
     * @var array
     */
    protected $dates = [
        'reviewed_at',
    ];

    /**
     * 用于声明模型数据创建时不生成时间
     * public $timestamps = false; 代表这个模型没有 created_at 和 updated_at 两个时间戳字段。
     * @var bool
     */
    public $timestamps = false;

    /**
     * 定义对应关联属性
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(){
        return $this->belongsTo(Product::class);
    }

    /**
     * 定义对应关联属性
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productSku(){
        return $this->belongsTo(ProductSku::class);
    }

    /**
     * 定义对应关联属性
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order(){
        return $this->belongsTo(Order::class);
    }

}
