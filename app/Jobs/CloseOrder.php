<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * 代表这个类需要被放到队列中执行，而不是触发时立即执行
 * Class CloseOrder
 * @package App\Jobs
 */
class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 订单对象
     * @var
     */
    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, $delay)
    {
        $this->order = $order;
        //设置延迟的时间，delay()方法的参数代表多少秒之后执行
        $this->delay($delay);
    }

    /**
     * Execute the job.
     * 定义这个任务类具体的任务逻辑
     * 当队列处理器从队列中取出任务时，会调用handle（）方法
     *
     * @return void
     */
    public function handle()
    {
        //判断对饮的订单是否已经被支付
        //如果已经支付则不需要关闭订单，直接退出
        if ($this->order->paid_at){
            return;
        }

        //开启事物
        \DB::transaction(function (){
            //将订单的closed字段变为true，即关闭订单
            $this->order->update(['closed' => true]);
            //循环遍历订单中的商品sku,将订单中的数量加回到sku中
            foreach($this->order->items as $item){
                $item->productSku->addStock($item->amount);
                //当前订单类型是秒杀订单,并且对应商品是上架且尚未到截止时间
                if ($item->order->type === Order::TYPE_SECKILL && $item->product->on_sale && !$item->product->seckill->is_after_end){
                    //将redis中的库存+1
                    \Redis::incr('seckill_sku_'.$item->productSku->id);
                }
            }

            //关闭订单减少优惠券的使用量
            if ($this->order->couponCode){
                $this->order->couponCode->changeUsed(false);
            }


        });

    }
}
