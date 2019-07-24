<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
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
     * 支付宝支付前端回到展示信息
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


    /**
     * 支付宝服务器回调处理地址
     * @return string
     */
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

        $this->afterPaid($order);

        return app('alipay')->success();

        //\Log::debug('Alipay Notify', $data->all());//记录查看支付参数日志
    }

    /**
     * 微信支付发起
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByWechat(Order $order, Request $request){
        //检测用户
        $this->authorize('own', $order);

        //校验订单状态
        if ($order->paid_at || $order->closed){
            throw new InvalidRequestException('订单状态异常');
        }

        //scan 发起微信扫码支付
        $wechatOrder =  app('wechat_pay')->scan([
            'out_trade_no'  =>  $order->no,//订单支付流水号
            'total_fee'     =>  $order->total_amount*100,//微信支付金额以分为单位所以要×100
            'body'          =>  '支付 Lan-Shop 商城的订单:'.$order->no,
        ]);

        //pc版将支付参数转换为二维码
        // 把要转换的字符串作为 QrCode 的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);

        //将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(),200,['Content-Type' => $qrCode->getContentType()]);

    }

    /**
     * 微信服务器回调支付处理
     * @return string
     */
    public function wechatNotify(){
        //校验支付完成服务器回调参数
        $data = app('wechat_pay')->verify();

        $order = Order::where('no', $data->out_trade_no)->first();
        //订单不存在告知微信
        if (!$order){
            return 'fail';
        }

        //订单已支付
        if ($order->paid_at){
            return app('wechat_pay')->success();
        }

        $order->update([
            'paid_at'           =>  Carbon::now(),
            'payment_method'    =>  'wechat',
            'payment_no'        =>  $data->transaction_id,
        ]);

        $this->afterPaid($order);

        return app('wechat_pay')->success();


    }

    public function afterPaid($order){
        event(new OrderPaid($order));
    }

}
