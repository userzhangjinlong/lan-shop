<?php

namespace App\Http\Requests;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SeckillOrderRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //优化秒杀sql速率 业务逻辑
            /*'address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id)
            ],*/
            'address.province'      => 'required',
            'address.city'          => 'required',
            'address.district'      => 'required',
            'address.address'       => 'required',
            'address.zip'           => 'required',
            'address.contact_name'  => 'required',
            'address.contact_phone' => 'required',
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail){
                    /*if (!$sku = ProductSku::find($value)){
                        return $fail('该商品不存在');
                    }*/
                    //库存改为从redis中取出
                    $stock = \Redis::get('seckill_sku_'.$value);
                    //如果是null代表这个sku不是秒杀商品
                    if (is_null($stock)){
                        return $fail('该商品不存在');
                    }

                    if ($stock < 1){
                        return $fail('该商品已售完');
                    }

                    /*if ($sku->product->type != Product::TYPE_SECKILL){
                        return $fail('该商品不支持秒杀');
                    }*/
                    // 大多数用户在上面的逻辑里就被拒绝了
                    // 因此下方的 SQL 查询不会对整体性能有太大影响
                    $sku = ProductSku::find($value);
                    if ($sku->product->seckill->is_before_start){
                        return $fail('秒杀活动尚未开始');
                    }

                    if ($sku->product->seckill->is_after_end){
                        return $fail('秒杀活动已结束');
                    }

                    if (!$sku->product->on_sale){
                        return $fail('商品尚未上架');
                    }

                    //新增判断是否登录
                    if (!$user = Auth::user()){
                        throw new InvalidRequestException('请先登录');
                    }

                    if (!$user->email_verified_at){
                        throw new InvalidRequestException('请先验证邮箱');
                    }

                    if ($order = Order::query()
                        //筛选出当前用户的订单
                        ->where('user_id', $this->user()->id)
                        ->whereHas('items', function ($query) use ($value){
                            //筛选出包含当前sku的订单
                            $query->where('product_sku_id', $value);
                        })
                        ->where(function ($query){
                            //已支付订单
                            $query->whereNotNull('paid_at')
                                //或者未关闭的订单
                            ->orWhere('closed', false);
                        })->first()){
                        if ($order->paid_at){
                            return $fail('你已经抢购了该商品');
                        }

                        return $fail('你已经下单了该商品,请到订单页面支付');
                    }

                },
            ],
        ];
    }
}
