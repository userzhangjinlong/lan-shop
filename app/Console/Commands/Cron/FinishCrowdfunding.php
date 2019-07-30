<?php

namespace App\Console\Commands\Cron;

use App\Jobs\RefundCrowdfundingOrders;
use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinishCrowdfunding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:finish-crowdfunding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束众筹';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        CrowdfundingProduct::query()
            //众筹结束时间早于当前时间
            ->where('end_at', '<=', Carbon::now())
            //众筹状态为众筹中
            ->where('status', CrowdfundingProduct::STATUS_FUNDING)
            ->get()
            ->each(function(CrowdfundingProduct $crowdfunding){
                //如果众筹目标金额大于实际众筹金额
                if ($crowdfunding->target_amount > $crowdfunding->total_amount){
                    //众筹失败
                    $this->crowdfundingFailed($crowdfunding);
                }else{
                    //众筹成功
                    $this->crowdfundingSucceed($crowdfunding);
                }
            });
    }


    protected function crowdfundingSucceed(CrowdfundingProduct $crowdfunding)
    {
        //只需将众筹状态改为众筹成功即可
        $crowdfunding->update([
            'status'    =>  CrowdfundingProduct::STATUS_SUCCESS,
        ]);
    }

    protected function crowdfundingFailed(CrowdfundingProduct $crowdfunding)
    {
        //将状态改为失败
        $crowdfunding->update([
            'status'    =>  CrowdfundingProduct::STATUS_FAIL,
        ]);

        //调用异步任务执行退款逻辑代码
        dispatch(new RefundCrowdfundingOrders($crowdfunding));

    }

}
