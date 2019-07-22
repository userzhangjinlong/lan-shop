<?php

namespace App\Http\Requests;

use App\Models\ProductSku;

class AddCartRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sku_id'    =>  [
                'required',
                /**
                 * 参数名、参数值和错误回调 对应三个参数
                 */
                function($attribute, $value, $fail){
                    if (!$sku = ProductSku::find($value)){
                        $fail('该商品不存在');
                        return;
                    }
                    if (!$sku->product->on_sale){
                        $fail('该商品未上架');
                        return;
                    }
                    if ($sku->stock === 0){
                        $fail('商品已售完');
                        return;
                    }
                    if ($this->input('amount') > 0 && $sku->stock < $this->input('amount')){
                        $fail('商品库存不足');
                        return;
                    }
                },
            ],
            'amount'    =>  ['required','integer', 'min:1'],
        ];
    }

    /**
     * 自定义属性值
     * @return array
     */
    public function attributes()
    {
        return [
            'amount'    =>  '商品数量',
        ];
    }

    /**
     * 自定义消息
     * @return array
     */
    public function messages()
    {
        return [
            'sku_id.required'   =>  '请选择商品',
        ];
    }

}
