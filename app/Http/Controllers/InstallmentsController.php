<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    /**
     * 分期订单列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(env('PAGINATE'));


        return view('installments.index', ['installments' => $installments]);
    }

    /**
     * 分期付款详情
     * @param Installment $installment
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Installment $installment)
    {
        $this->authorize('own', $installment);

        //取出当前分期付款的所有还款计划,按还款顺序排序
        $items = $installment->items()->orderBy('sequence')->get();
        return view('installments.show', [
            'installment'   =>  $installment,
            'items'         =>  $items,
            //下一个未完成还款的还款计划
            'nextItem'      =>  $items->where('paid_at', null)->first(),
        ]);
    }

    /**
     * 分期付款支付宝支付
     * @param Installment $installment
     * @return mixed
     * @throws InvalidRequestException
     */
    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->closed){
            throw new InvalidRequestException('对应的商品订单已经关闭');
        }

        if ($installment->status === Installment::STATUS_FINFSHED){
            throw new InvalidRequestException('订单已结清');
        }

        // 获取当前分期付款最近的一个未支付的还款计划
        if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()){
            //如果没有未支付的还款,原则上不可能
            throw new InvalidRequestException('订单已结清');
        }

        //支付宝网页支付
        return app('alipay')->web([
            //支付订单号使用分期流水号+还款计划编号
            'out_trade_no'  =>  $installment->no.'_'.$nextItem->sequence,
            'total_amount'  =>  $nextItem->total,
            'subject'       =>  '支付Lan-Shop 的分期订单:'.$installment->no,
            // 这里的 notify_url 和 return_url 可以覆盖掉在 AppServiceProvider 设置的回调地址
            'notify_url'    =>  ngrok_url('installments.alipay.notify'),
            'return_url'    =>   route('installments.alipay.return')
        ]);

    }

    /**
     * 分期付款支付宝前端回调
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function alipayReturn()
    {
        try{
            app('alipay')->verify();
        }catch (\Exception $exception){
            return view('pages.error', ['msg' => '支付参数异常']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    public function alipayNotify()
    {
        //校验支付宝参数是否正确
        $data = app('alipay')->verify();
        //如果订单支付宝支付状态不是成功或者结束,则不走后续逻辑
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])){
            return app('alipay')->success();
        }

        //拉起支付时使用的支付订单号是由分期流水号+还款计划编号组成的
        //因此可以通过支付订单号来还原出这笔还款是那个分期付款的还款计划
        list($no, $sequence) = explode('_', $data->out_trade_no);
        //根据分期流水号查询对应的分期记录,原则上不会找不到,这里的判断只是增强代码健壮性
        if (!$installment = Installment::query()->where('no', $no)->first()){
            return 'fail';
        }

        //根据还款计划编号查询对应的还款计划,原则上不会找不到增强代码健壮性
        if (!$item = $installment->items()->where('sequence', $sequence)->first()){
            return 'fail';
        }

        //如果这个还款计划的支付状态是已支付,则告知支付宝已支付不继续后续逻辑
        if ($item->paid_at){
            return app('alipay')->success();
        }

        \DB::transaction(function() use ($data, $no, $installment, $item){
            //更新对应的还款计划
            $item->update([
                'paid_at'       =>  Carbon::now(),
                'payment_method'=>  'alipay',
                'payment_no'    =>  $data->trade_no//支付宝订单号
            ]);

            //如果是第一笔还款
            if ($item->sequence === 0){
                //将分期付款的状态改为还款中
                $installment->update(['status' => Installment::STATUS_REPAYING]);
                //将分期付款的订单状态改为已支付
                $installment->order->update([
                    'paid_at'       =>  Carbon::now(),
                    'payment_method'=>  'installment',
                    'payment_no'    =>  $no//支付订单号为分期付款的流水号
                ]);

                //调用已支付时间
                event(new OrderPaid($installment->order));
            }

            //如果是最后一笔还款
            if ($item->sequence === $installment->count -1){
                //将分期订单状态改为已结清
                $installment->update(['status' => Installment::STATUS_FINFSHED]);
            }

        });

        return app('alipay')->success();

    }

}
