<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * 支付宝支付方式新增
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByAlipay(Order $order, Request $request)
    {
        //判断订单是否是自己的
        $this->authorize('own', $order);

        //订单已支付或者关闭
        if ($order->closed || $order->paid_at){
            throw new InvalidRequestException('订单状态异常');
        }

        //调用支付宝网页支付
        return app('alipay')->web([
            'out_trade_no'  =>  $order->no,//订单编号保证在商户端不重复
            'total_amount'  =>  $order->total_amount,//订单金额，单位元支持小数点后两位
            'subject'       =>  '支付 Lan-Shop 商城的订单:'.$order->no,
        ]);
    }

    /**
     * 支付前端回到展示信息
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function alipayReturn()
    {
        //校验提交的参数是否合法
        try{
//            $data =
                app('alipay')->verify();
        } catch (\Exception $e){
            return view('pages.error', ['msg' => '支付数据异常']);
        }

        return view('pages.success', ['msg' => '付款成功']);

        //这里校验支付回到参数如果nginx配置location有？s或者url等之后的参数会报错sign failed 注意这个问题哦
//        dd($data);
    }


    public function alipayNotify(){
        //校验输入参数
        $data = app('alipay')->verify();
        //如果订单状态不是成功或结束，不走下面逻辑
        //所有支付宝交易状态：https://docs.open.alipay.com/59/103672
        if(!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])){
            return app('alipay')->success();
        }

        //$data->out_trade_node拿到订单流水号，并在数据库中查询
        $order = Order::where('no', $data->out_trade_no)->first();
        // 正常来说不太可能出现支付了一笔不存在的订单，这个判断只是加强系统健壮性。
        if (!$order) {
            return 'fail';
        }

        //如果这笔订单状态已经是已支付
        if ($order->paid_at){
            //返回数据给支付宝
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'           =>  Carbon::now(),//支付时间
            'payment_method'    =>  'alipay',//支付方式
            'payment_no'        =>  $data->trade_no,//支付宝订单号
        ]);
        return app('alipay')->success();

        //\Log::debug('Alipay Notify', $data->all());//记录查看支付参数日志
    }
}
