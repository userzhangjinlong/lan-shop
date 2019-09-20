<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-23
 * Time: 下午3:33
 */

namespace App\Services;


use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InternalExpection;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{
    /**
     * 普通商品订单
     * @param User $user
     * @param UserAddress $address
     * @param $remark
     * @param $items
     * @param CouponCode|null $code
     * @return mixed
     * @throws CouponCodeUnavailableException
     */
    public function store(User $user, UserAddress $address, $remark, $items, CouponCode $code=null)
    {
        //如果传入了优惠券
        if ($code){
            $code->checkAvailable($user);
        }

        //开始事物
        $order = \DB::transaction(function() use ($user, $address, $remark, $items, $code){
            //更新当前地址使用的最后时间
            $address->update(['last_used_at' => Carbon::now()]);

            //创建订单
            $order = new Order([
                'address'   =>  [//将地址信息放入订单中
                    'address'   =>  $address->full_address,
                    'zip'       =>  $address->zip,
                    'contact_name'    =>  $address->contact_name,
                    'contact_phone'    =>  $address->contact_phone,
                ],
                'remark'    =>  $remark,
                'total_amount'    =>    0,
                'type'      =>  Order::TYPE_NORMAL,
            ]);

            //订单关联到当前用户
            $order->user()->associate($user);
            //写入订单数据
            $order->save();

            $totalAmount = 0;
            foreach($items as $data){
                $sku = ProductSku::find($data['sku_id']);
                //创建一个OrderItem并直接与当前订单关联
                /**
                 * 然后遍历传入的商品 SKU 及其数量，$order->items()->make() 方法可以新建一个关联关系的对象
                 * （也就是 OrderItem）但不保存到数据库，这个方法等同于 $item = new OrderItem();
                 * $item->order()->associate($order);
                 */
                $item = $order->items()->make([
                    'amount'    =>  $data['amount'],
                    'price'     =>  $sku->price
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price*$data['amount'];
                //删减sku库存
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }

            if ($code){
                //总金额已经计算出来了,检查是否符合优惠券规则
                $code->checkAvailable($user, $totalAmount);
                //把订单金额修改为优惠券金额
                $totalAmount = $code->getAdjustedPrice($totalAmount);
                //将优惠券与订单关联
                $order->couponCode()->associate($code);
                //增加优惠券用量
                if ($code->changeUsed() <= 0){
                    throw new CouponCodeUnavailableException('优惠券已经被使用完了哟!');
                }
            }
            //更新订单金额
            $order->update(['total_amount' => $totalAmount]);

            //将下单成功的商品从购物车移除
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;

        });

        //dispatch 添加队列异步定时关闭订单任务 延迟队列自动关闭未支付订单
        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }

    /**
     * 众筹商品订单
     * @param User $user
     * @param UserAddress $address
     * @param ProductSku $sku
     * @param $amount
     */
    public function crowdfunding(User $user, UserAddress $address, ProductSku $sku, $amount)
    {
        //开启事物
        $order = \DB::transaction(function() use ($user, $address, $sku, $amount){
            //更新地址最后使用时间
            $address->update(['last_used_at', Carbon::now()]);
            //创建众筹订单
            $order = new Order([
                'address' =>  [
                    'address'       =>  $address->getFullAddressAttribute(),
                    'zip'           =>  $address->zip,
                    'contact_name'  =>  $address->contact_name,
                    'contact_phone' =>  $address->contact_phone
                ],
                'remark'            =>  '',
                'total_amount'      =>  $sku->price*$amount,
                'type'              =>  Order::TYPE_CROWDFUNDING,
            ]);
            //订单关联当前用户
            $order->user()->associate($user);
            //写入数据库
            $order->save();
            //创建一个新的订单项并于sku关联
            $item = $order->items()->make([
                'amount'    =>  $amount,
                'price'     =>  $sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();
            //扣减对应sku库存
            if ($sku->decreaseStock($amount) <= 0){
                throw new InvalidRequestException('商品库存不足');
            }

            return $order;
        });

        //众筹结束时间减去当前时间得到剩余秒数
        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp()-time();
        //剩余秒数与默认订单关闭时间取较小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));
        return $order;
    }

    /**
     * @param User $user
     * @param array $addressData
     * @param ProductSku $sku
     * @return mixed
     *
     * //原本参数UserAddress $address, 修改为address数组
     */
    public function seckill(User $user,  array $addressData, ProductSku $sku)
    {
        $order = \DB::transaction(function () use ($user, $addressData, $sku){
            //更新当前地址的最后使用时间
//            $address->update(['last_used_at' => Carbon::now()]);
            //扣减对应的sku库存
            if ($sku->decreaseStock(1) <= 0){
                throw new InvalidRequestException('该商品库存不足');
            }
            //创建秒杀订单
            $order = new Order([
                'address' => [
//                    'address' => $address->full_address,
                    'address' => $addressData['province'].$addressData['city'].$addressData['district'].$addressData['address'],
//                    'zip'     => $address->zip,
                    'zip'     =>  $addressData['zip'],
//                    'contact_name' => $address->contact_name,
                    'contact_name' => $addressData['contact_name'],
//                    'contact_phone' => $address->contact_phone,
                    'contact_phone' => $addressData['contact_phone'],
                ],
                'remark' => '',
                'total_amount' => $sku->price,
                'type' => Order::TYPE_SECKILL,
            ]);
            //订单关联到当前用户
            $order->user()->associate($user);
            //写入数据库
            $order->save();
            //创建一个新的订单项并且与sku关联
            $item = $order->items()->make([
                'amount' => 1,//秒杀订单限购一份
                'price' => $sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            return $order;
        });
        //秒杀商品的自动关闭时间与普通订单不同
        dispatch(new CloseOrder($order, config('app.seckill_order_ttl')));

        return $order;
    }

    /**
     * 订单退款
     * @param Order $order
     */
    public function refundOrder(Order $order)
    {
        //判断订单的支付方式
        //判断订单的支付方式
        switch ($order->payment_method)
        {
            case 'wechat':
                //微信支付
                $refundNo = Order::getAvailableRefundNo();
                //调用微信退款
                app('wechat_pay')->refund([
                    'out_trade_no'  =>  $order->no,//订单流水号
                    'totla_fee'     =>  $order->total_amount*100,//订单金额分
                    'refund_fee'    =>  $order->total_amount*100,//要退款的金额,分
                    'out_refund_no' =>  $refundNo,//退款单号
                    //微信支付的退款结果不是实时返回的,而是通过退款回调来通知,因此这里需要配置上退款回调借口地址
                    'notify_url'    =>  ngrok_url('payment.wechat.refund_notify') //你的测试支付环境回到地址
//                    'notify_url'    =>   ('payment.wechat.refund_notify'), //你的测试支付环境回到地址 最终正确路由回调退款
                ]);
                //将订单状态改为退款中
                $order->update([
                    'refund_no'     =>  $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                //调用支付宝是咧的refund方法
                $ret = app('alipay')->refund([
                    'out_trade_no'  =>  $order->no,//订单流水号
                    'refund_amount' =>  $order->total_amount,//退款金额
                    'out_request_no'=>  $refundNo//退款订单号
                ]);
                //根据支付宝文档如果退款返回值里面有sub_code字段说嘛退款失败
                if ($ret->sub_code){
                    //将退款失败的保存进extra字段
                    $extra = $order->extra ?: [];
                    $extra['refund_failed_code'] = $ret->sub_code;
                    //将退款的订单标记为退款失败
                    $order->update([
                        'refund_no'         =>  $refundNo,
                        'refund_status'     =>  Order::REFUND_STATUS_FAILED,
                        'extra'             =>  $extra,
                    ]);
                }else{
                    //退款成功
                    $order->update([
                        'refund_no'     =>  $refundNo,
                        'refund_status' =>  Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            case 'installment':
                $order->update([
                    'refund_no'     =>  Order::findAvailableNo(),// 生成退款订单号
                    'refund_status' =>  Order::REFUND_STATUS_PROCESSING,// 将退款状态改为退款中
                ]);
                // 触发退款异步任务
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                // 原则上不可能出现，这个只是为了代码健壮性
                throw new InternalExpection('未知订单支付方式：'.$order->payment_method);
                break;
        }
    }

}