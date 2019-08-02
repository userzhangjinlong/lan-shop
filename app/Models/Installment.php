<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_REPAYING = 'repaying';
    const STATUS_FINFSHED = 'finished';

    /**
     * @var array
     */
    public static $statusMap = [
        self::STATUS_PENDING => '未执行',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_FINFSHED => '已完成'
    ];

    protected $fillable = [
        'no', 'total_amount', 'count', 'fee_rate', 'fine_rate', 'status'
    ];

    /**
     * 监听创建模型自动创建
     */
    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        //监听模型创建事件,在写入数据之前触发
        static::creating(function($model){
            //如果模型的no字段为空
            if (!$model->no){
                //调用 findAvailableNo 生成分期流水号
                $model->no = static::findAvailableNo();
            }
            //如果生成失败,终止订单创建
            if (!$model->no){
                return false;
            }
        });
    }

    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(){
        return $this->belongsTo(User::class);
    }

    /**
     * 关联订单
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order(){
        return $this->belongsTo(Order::class);
    }

    /**
     * 关联多个还款计划
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(){
        return $this->hasMany(InstallmentItem::class);
    }

    /**
     * 分期流水订单号生成
     * @return bool|string
     * @throws \Exception
     */
    public static function findAvailableNo(){
        //分期流水号前缀
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++){
            //随机生成6位数字
            $no = $prefix.str_pad(random_int(0,999999), 6, '0', STR_PAD_LEFT);

            //判断是否已经存在
            if (!static::query()->where('no', $no)->exists()){
                return $no;
            }

        }
        \Log::warning(sprintf('find installment no failed'));

        return false;
    }

    public function refreshRefundStatus(){
        $allSuccess = true;
        //重新加载items保证与数据库同步
        $this->load(['items']);
        foreach ($this->items as $item){
            if ($item->paid_at && $item->refund_status !== InstallmentItem::REFUND_STATUS_SUCCESS){
                $allSuccess = false;
                break;
            }
        }

        if ($allSuccess){
            $this->order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        }

    }

}