<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';

    public static $typeMap = [
        self::TYPE_NORMAL => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'image', 'on_sale', 'rating', 'sold_count', 'review_count', 'price', 'type'
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

    /**
     * 图片url返回函数 ImageUrl 驼峰写法控制器内部调用驼峰写法调用或者_下划线调用 商品列表image_rul 或者 imageUrl
     * @return mixed
     */
    public function getImageUrlAttribute(){
        // 如果 image 字段本身就已经是完整的 url 就直接返回
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }
        return \Storage::disk('public')->url($this->attributes['image']);
    }

    /**
     * 定义产品和分类的关联属性
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 一对一众筹商品属性
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function crowdfunding(){
        return $this->hasOne(CrowdfundingProduct::class);
    }

}
