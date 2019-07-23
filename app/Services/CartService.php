<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-23
 * Time: 下午3:08
 */

namespace App\Services;


use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CartService
{
    /**
     * 获取购物车列表数据
     * @return mixed
     */
    public function get()
    {
        return Auth::user()->CartItems()->with(['productSku.product'])->get();
    }

    /**
     * 新增商品进购物车 业务逻辑代码
     * @param $skuId
     * @param $amount
     * @return CartItem
     */
    public function add($skuId, $amount)
    {
        $user = Auth::user();
        //查询该商品是否已经在购物车中
        if ($item = $user->CartItems()->where('product_sku_id', $skuId)->first()){
            //存在叠加商品数量
            $item->update([
                'amount'    =>  $item->amount+$amount
            ]);
        }else{
            //新增购物车
            $item = new CartItem(['amount'  =>  $amount]);
            $item->user()->associate($user);
            $item->productSku()->associate($skuId);
            $item->save();

        }

        return $item;
    }

    /**
     * 移除购物车
     * @param $skuIds
     */
    public function remove($skuIds)
    {
        //skuId 为数组或单个字符串
        if (!is_array($skuIds)){
            $skuIds = [$skuIds];
        }

        Auth::user()->CartItems()->whereIn('product_sku_id', $skuIds)->delete();

    }
}