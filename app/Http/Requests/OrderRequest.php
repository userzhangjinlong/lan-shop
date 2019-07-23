<?php

namespace App\Http\Requests;

use App\Models\ProductSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //判断用户提交的地址id是否存在数据库并属于当前用户
            //后面这个条件非常重要,否则恶意用户可以用不同的地址ID不断提交订单来遍历出平台所有用户的收货地址
            'address_id'        =>      ['required', Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id)],
            'items'             =>      ['required', 'array'],
            'items.*.sku_id'    =>      [
                //检查items数组下每一个数组的sku_id参数
                'required',
                function($attribute, $value, $fail){
                    if (!$sku = ProductSku::find($value)){
                        $fail('该商品不存在');
                        return;
                    }
                    if (!$sku->product->on_sale){
                        $fail('商品未上架');
                        return;
                    }
                    if ($sku->stock === 0){
                        $fail('商品已售空');
                        return;
                    }
                    // 获取当前索引
                    preg_match('/items\.(\d+)\.sku_id/', $attribute, $m);
                    $index  = $m[1];
                    // 根据索引找到用户所提交的购买数量
                    $amount = $this->input('items')[$index]['amount'];
                    if ($amount > 0 && $amount > $sku->stock) {
                        $fail('该商品库存不足');
                        return;
                    }
                },
            ],
            'items.*.amount'    =>  ['required', 'integer', 'min:1'],
        ];
    }
}
