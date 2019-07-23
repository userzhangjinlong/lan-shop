<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-23
 * Time: 下午3:33
 */

namespace App\Services;


use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{
    public function store(User $user, UserAddress $address, $remark, $items)
    {
        //开始事物
        $order = \DB::transaction(function() use ($user, $address, $remark, $items){
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
                'total_amount'    =>    0
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
}