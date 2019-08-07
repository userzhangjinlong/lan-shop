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
        'title', 'long_title', 'description', 'image', 'on_sale', 'rating', 'sold_count', 'review_count', 'price', 'type'
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

    /**
     * 关联属性
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function properties(){
        return $this->hasMany(ProductProperty::class);
    }

    /**
     * 返回分组了的属性集合
     * @return mixed
     */
    public function getGroupedPropertiesAttribute(){
        return $this->properties
            //按照属性名聚合,返回的集合的key是属性名,value是包含该属性名的所有属性集合
            ->groupBy('name')
            ->map(function ($properties){
                // 使用 map 方法将属性集合变为属性值集合
                return $properties->pluck('value')->all();
            });
    }

    /**
     * 将商品需要被搜索到的信息存入Elasticsearch中
     * @return array
     */
    public function toESArray()
    {
        //只取出需要的字段
        $arr = array_only($this->toArray(), [
            'id',
            'type',
            'title',
            'category_id',
            'long_title',
            'on_sale',
            'rating',
            'sold_count',
            'review_count',
            'price',
        ]);

        //如果商品有类目, 则category字段为类目名数组,否则为空字符串
        $arr['category'] = $this->category ? explode('-', $this->category->getFullNmaeAttribute()) : '';
        //类目path字段
        $arr['category_path'] = $this->category ? $this->category->path : '';
        //strip_tags函数可以去除html标签
        $arr['description'] = strip_tags($this->description);
        //只取出需要的sku字段
        $arr['skus'] = $this->skus->map(function (ProductSku $sku){
            return array_only($sku->toArray(),['title', 'description', 'price']);
        });
        //只取出需要的属性字段
        $arr['properties'] = $this->properties->map(function (ProductProperty $property){
//            return array_only($property->toArray(), ['name', 'value']);
            //对应地增加一个 search_value 字段,用符号:将属性名和属性值拼接起来
            return array_merge(array_only($property->toArray(),['name', 'value']), [
                'search_value' => $property->name.':'.$property->value,
            ]);
        });

        return $arr;
    }

    /**
     * 返回以id排序的sql操作
     * @param $query
     * @param $ids
     * @return mixed
     */
    public function scopeByIds($query, $ids)
    {
        return $query->whereIn('id', $ids)->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $ids)));
    }

}
