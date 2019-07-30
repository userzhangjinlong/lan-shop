<?php

namespace App\Jobs;

use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefundCrowdfundingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    protected $crowdfunding;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CrowdfundingProduct $crowdfunding)
    {
        $this->crowdfunding = $crowdfunding;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //如果众筹的状态不是失败不执行退款,
        if ($this->crowdfunding->status !== CrowdfundingProduct::STATUS_FAIL){
             return;
        }

        //将定时任务中的退款失败代码加入到这儿
        /*$orderService = app(OrderService::class);
        //查询出所有参与了此众筹订单
        Order::query()
            //类型为众筹
            ->where('type', Order::TYPE_CROWDFUNDING)
            //已支付
            ->whereNotNull('paid_at')
            ->whereHas('items', function($query){
                $query->where('product_id', $this->crowdfunding->product_id);
            })
            ->get()
            ->each(function (Order $order) use ($orderService){
                //调用订单退款逻辑
                $orderService->refundOrder($order);
            });*/

        // 将定时任务中的众筹失败退款代码移到这里
        $orderService = app(OrderService::class);

        Order::query()
            ->where('type', Order::TYPE_CROWDFUNDING)
            ->whereNotNull('paid_at')
            ->whereHas('items', function ($query) {
                $query->where('product_id', $this->crowdfunding->product_id);
            })
            ->get()
            ->each(function (Order $order) use ($orderService) {
                $orderService->refundOrder($order);
            });
    }
}
