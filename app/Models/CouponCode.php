<?php

namespace App\Models;

use App\Exceptions\CouponCodeUnavailableException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CouponCode extends Model
{
    //用常量的方式定义支持的优惠券类型
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    /**
     * @var array
     */
    public static $typeMap = [
        self::TYPE_FIXED => '固定金额',
        self::TYPE_PERCENT => '比例',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name', 'code', 'type', 'value', 'total', 'used', 'min_amount', 'not_before', 'not_after', 'enabled',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'enabled'   =>  'boolean',
    ];

    /**
     * 指明两个字段是日期
     * @var array
     */
    protected $dates = ['not_before', 'not_after'];

    /**
     * @var array
     */
    protected $appends = ['description'];

    /**
     * 生成指定长度优惠券码
     * @param int $length
     * @return string
     */
    public static function findAvailableCode($length = 16){
        do{
            //生成优惠券码
            $code = strtoupper(Str::random($length));
        }while(self::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * 返回制定格式折扣数据
     * @return string
     */
    public function getDescriptionAttribute(){
        $str = '';

        if ($this->min_amount > 0){
            $str = '满'.str_replace('.00', '', $this->min_amount);
        }

        if ($this->type === self::TYPE_PERCENT){
            return $str.'优惠'.str_replace('.00', '', $this->value).'%';
        }

        return $str.'减'.str_replace('.00', '', $this->value);

    }

    /**
     * 检测优惠券信息
     * @param User $user
     * @param null $orderAmount
     * @throws CouponCodeUnavailableException
     */
    public function checkAvailable(User $user, $orderAmount = null){
        if (!$this->enabled){
            throw new CouponCodeUnavailableException('优惠券不存在');
        }
        if ($this->total - $this->used <= 0){
            throw new CouponCodeUnavailableException('优惠券已被使用完');
        }
        if ($this->not_before && $this->not_before->gt(Carbon::now())){
            throw new CouponCodeUnavailableException('优惠券还未到开始使用时间');
        }
        if ($this->not_after && $this->not_after->lt(Carbon::now())){
            throw new CouponCodeUnavailableException('优惠券已经到期');
        }
        if (!is_null($orderAmount) && $orderAmount < $this->min_amount){
            throw  new CouponCodeUnavailableException('订单金额不满足该优惠券的最低金额');
        }

        $used = Order::where('user_id', $user->id)
            ->where('coupon_code_id', $this->id)
            ->where(function($query){
                $query->where(function ($query){
                    $query->whereNotNull('paid_at')->where('closed', false);
                })->orWhere(function($query){
                    $query->whereNotNull('paid_at')->where('refund_status','!=',Order::REFUND_STATUS_SUCCESS);
                });
            })
            ->exists();

        if ($used){
            throw new CouponCodeUnavailableException('你已经使用过该优惠券了');
        }

    }

    /**
     * 返回使用了优惠券之后的金额
     * @param $orderAmount
     * @return mixed|string
     */
    public function getAdjustedPrice($orderAmount){
        //固定金额
        if ($this->type === self::TYPE_FIXED){
            return max(0.01, $orderAmount-$this->value);
        }

        return number_format($orderAmount*(100-$this->value)/100, 2, '.', '');
    }

    /**
     * 改变优惠券使用数量
     * @param bool $increase
     * @return int
     */
    public function changeUsed($increase = true){
        //传入true表示新增用量,否则减少用量
        if ($increase){
            //与检查sku库存类似,这里需要检查当前用量是否已经超过总量
            return $this->where('id', $this->id)->where('used', '<', $this->total)->increment('used');
        }else{
            return $this->decrement('used');
        }
    }

}
