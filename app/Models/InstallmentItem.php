<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Moontoast\Math\BigNumber;

class InstallmentItem extends Model
{
    const REFUND_STATUS_PENDING = 'pending';
    const REFUND_STATUS_PROCESSING = 'processing';
    const REFUND_STATUS_SUCCESS = 'success';
    const REFUND_STATUS_FAILED = 'failed';

    /**
     * @var array
     */
    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING => '未退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS => '退款成功',
        self::REFUND_STATUS_FAILED => '退款失败'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'sequence', 'base', 'fee', 'fine', 'due_date', 'paid_at', 'payment_method', 'payment_no', 'refund_status'
    ];

    protected $dates = ['due_date', 'paid_at'];

    /**
     * 关联分期信息
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function installment(){
        return $this->belongsTo(Installment::class);
    }

    /**
     * 创建一个访问器，返回当前还款计划需还款的总金额
     * @return string
     */
    public function getTotalAttribute(){
        //小数点需要用bcmath扩展提供的函数
        $total = big_number($this->base)->add($this->fee);
        if (!is_null($this->fine)){
            $total->add($this->fine);
        }

        return $total->getValue();
    }

    /**
     * 获取当前还款是否逾期
     * @return bool
     */
    public function getLsOverdueAttribute(){
        return Carbon::now()->gt($this->due_date);
    }

}
