<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /**
     * 支付成功更新订单销量 评分等事件
     * @param $order
     */
    public function afterPaid($order){
        event(new OrderPaid($order));
    }

    /**
     * 微信退款回调接口
     * @param Request $request
     * @return string
     */
    public function wechatRefundNotify(Request $request)
    {
        //给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        $data = app('wechat_pay')->verify(null, true);

        //没有找到对应的订单,保证代码健壮性
        if (!$order = Order::where('no', $data['out_trade_no'])->first()){
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS'){
            //微信返回seccess 退款成功,修改订单退款成功状态
            $order->update([
                'refund_status'     =>      Order::REFUND_STATUS_SUCCESS,
            ]);
        }else{
            //退款失败
            $extra = $order->extra ?: [];
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status'     =>  Order::REFUND_STATUS_FAILED,
                'extra'             =>  $extra,
            ]);
        }

        app('wechat_pay')->success();
    }

    public function payByInstallment(Order $order, Request $request)
    {
        //判断用户是否属于当前用户
        $this->authorize('own', $order);
        //订单已支付或者已关闭
        if ($order->paid_at || $order->closed){
            throw new InvalidRequestException('订单状态异常');
        }

        //订单不满足最低分期要求
        if ($order->total_amount < config('app.min_installment_amount')){
            throw new InvalidRequestException('订单金额低于分期最低金额');
        }

        $this->validate($request,[
            'count'     =>  ['required', Rule::in(array_keys(config('app.installment_fee_rate')))]
        ]);

        //删除同一笔商品订单发起过其他的状态是未支付的分期付款,避免同一笔订单有多个分期付款
        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();

        $count = $request->input('count');
        // 创建一个新的分期付款对象
        $installment = new Installment([
            //总本金为商品订单总金额
            'total_amount'  =>  $order->total_amount,
            //分期期数
            'count'     =>  $count,
            //配置中取出对应期数的费率
            'fee_rate'  =>  config('app.installment_fee_rate')[$count],
            //从配置中取出逾期费率
            'fine_rate' =>  config('app.installment_fine_rate')
        ]);

        //关联新增用户
        $installment->user()->associate($request->user());
        //关联订单
        $installment->order()->associate($order);
        $installment->save();

        //第一期的还款截止日期为明天凌晨0点
        $dueDate = Carbon::tomorrow();
        //计算每一期的本金
        $base = big_number($order->total_amount)->divide($count)->getValue();
        //计算每一期的手续费
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();
        //根据用户选择还款期数创建对应的还款计划
        for ($i = 0; $i < $count; $i++){
            //最后一期的本金需要用总本金减去前面几期的本金
            if ($i === $count -1){
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count-1));
            }

            $installment->items()->create([
                'sequence'  =>  $i,
                'base'      =>  $base,
                'fee'       =>  $fee,
                'due_date'  =>  $dueDate
            ]);

            //还款截止日期加30天
            $dueDate = $dueDate->copy()->addDays(30);

        }

        return $installment;

    }

}
