<?php

namespace App\Jobs;

use App\Exceptions\InternalExpection;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    public $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //如果商品订单支付方式不是分期付款,订单未支付,订单状态不是退款中,则不执行后面的逻辑
        if ($this->order->payment_method !== 'installment'
            || !$this->order->paid_at
            || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING){
            return;
        }

        //找不到对应的分期付款订单退出
        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()){
            return;
        }

        //遍历对应分期付款的所有还款计划
        foreach ($installment->items as $item){
            //如果还款计划未支付,或者退款状态为退款成功退款中,跳过
            if (!$item->paid_at || in_array($item->refund_status,[
                    InstallmentItem::REFUND_STATUS_SUCCESS,
                    InstallmentItem::REFUND_STATUS_PROCESSING
                ])){
                continue;
            }

            //调用具体的退款逻辑
            try{
                $this->refundInstallmentItem($item);
            }catch (\Exception $e){
                \Log::warning('分期退款失败'.$e->getMessage(),['installment_item_id' => $item->id]);
                //假如某个还款计划失败了跳过继续执行
                continue;
            }

        }

        $installment->refreshRefundStatus();

    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        //退款单号使用商品订单的退款号与当前还款计划的序号拼接成
        $refundNo = $this->order->refund_no.'_'.$item->sequence;
        //根据支付方式进行对应的还款
        switch ($item->payment_method){
            case 'wechat':
                app('wechat_pay')->refund([
                    'transaction_id'    =>  $item->payment_no,// 这里我们使用微信订单号来退款
                    'total_fee'         =>  $item->total*100,
                    'refund_fee'        =>  $item->total*100,
                    'out_trade_no'      =>  $refundNo, // 退款订单号
                    // 微信支付的退款结果并不是实时返回的，而是通过退款回调来通知，因此这里需要配上退款回调接口地址
                    'notify_url'        =>  ngrok_url('installments.wechat.refund_notify'),
                ]);
                //将还款计划更新为退款中
                $item->update([
                    'refund_status' =>  InstallmentItem::REFUND_STATUS_PROCESSING
                ]);
                break;
            case 'alipay':
                $ret = app('alipay')->refund([
                    'trade_no'  =>  $item->payment_no,
                    'refund_amount' =>  $item->base,
                    'out_request_no'=>  $refundNo
                ]);
                // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                if ($ret->sub_code){
                    //失败
                    $item->update(['refund_status' => InstallmentItem::REFUND_STATUS_FAILED]);
                }else{
                    //成功
                    $item->update(['refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS]);
                }
                break;
            default:
                //健壮代码
                throw new InternalExpection('未知支付方式'.$item->id.$item->payment_method);
                break;
        }
    }

}
