<?php

namespace App\Console\Commands\Cron;

use App\Models\Installment;
use App\Models\InstallmentItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateInstallmentFine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:calculate-installment-fine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算分期付款逾期费';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        InstallmentItem::query()
            //预加载避免N+1问题
            ->with(['installment'])
            ->whereHas('installment', function ($query){
                //对应的分期状态还在还款中
                $query->where('status', Installment::STATUS_REPAYING);
            })
            //还款截止日期在当前时间之前 表示逾期的
            ->where('due_date', '<=', Carbon::now())
            //尚未还款
            ->whereNull('paid_at')
            //使用chunkById避免一次性查询太多记录
            ->chunkById('1000',function($items){
                //遍历查询出来的还款计划
                foreach ($items as $item){
                    //通过carbon对象直接得到逾期天数
                    $overdueDays = Carbon::now()->diffInDays($item->due_date);
                    //本金与手续费之和
                    $base = big_number($item->base)->add($item->fee)->getValue();
                    //计算逾期费用
                    $fine = big_number($base)
                        ->multiply($overdueDays)
                        ->multiply($item->installment->fine_rate)
                        ->divide(100)
                        ->getValue();
                    //避免逾期费高于本金与手续费之和,使用compareTo方法来判断
                    //如果$fine大于$base,则compareTo会返回1 相等返回0 小于返回-1
                    $fine = big_number($fine)->compareTo($base) === 1 ? $base : $fine ;
                    $item->update([
                        'fine'  =>  $fine
                    ]);
                }
            });

    }
}
