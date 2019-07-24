<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\OrderItem;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateProductSoldCount implements ShouldQueue
{

    /**
     * Handle the event.
     * Laravel 会默认执行监听器的 handle 方法，触发的事件会作为 handle 方法的参数
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        //从事件中取出对应的订单
        $order = $event->getOrder();

        //预加载商品数据
        $order->load('items.product');

        //循环遍历订单商品
        foreach($order->items as $item){
            $product = $item->product;
            //计算对应商品的销量
            $soldCount = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query){
                    $query->whereNotNull('paid_at');
                })->sum('amount');

            //更新商品销量
            $product->update([
                'sold_count'    =>  $soldCount,
            ]);

        }
    }
}
