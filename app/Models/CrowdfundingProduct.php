<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrowdfundingProduct extends Model
{
    //定义众筹的3种状态
    const STATUS_FUNDING = 'funding';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    /**
     * @var array
     */
    public static $statusMap = [
        self::STATUS_FUNDING => '众筹中',
        self::STATUS_SUCCESS => '众筹成功',
        self::STATUS_FAIL => '众筹失败'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'total_amount', 'target_amount', 'user_count', 'status', 'end_at'
    ];

    /**
     * @var array
     */
    protected $dates = ['end_at'];

    /**
     * 不需要时间
     * @var bool
     */
    public $timestamps = false;

    /**
     * 属于一个商品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function getPercentAttribute()
    {
        $value = $this->attributes['total_amount']/$this->attributes['target_amount'];

        return floatval(number_format($value*100, 2, '.', ''));
    }

}
