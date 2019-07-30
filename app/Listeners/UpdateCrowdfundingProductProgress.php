<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCrowdfundingProductProgress implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        $order = $event->getOrder();

        //如果订单类型不是众筹订单不处理
        if ($order->type !== Order::TYPE_CROWDFUNDING){
            return;
        }

        //众筹商品信息
        $crowdfunding = $order->items[0]->product->crowdfunding;
        $data = Order::query()
            ->where('type', Order::TYPE_CROWDFUNDING)
            //已支付
            ->whereNotNull('paid_at')
            ->whereHas('items', function($query) use ($crowdfunding){
                //订单里面包含了本众筹商品
                $query->where('product_id', $crowdfunding->product_id);
            })
            ->first([
                //订单总金额
                \DB::raw('sum(total_amount) as total_amount'),
                //取出去重的支持用户数
                \DB::raw('count(distinct(user_id)) as user_count'),
            ]);

        $crowdfunding->update([
            'total_amount' =>   $data->total_amount,
            'user_count'   =>   $data->user_count
        ]);

    }
}
