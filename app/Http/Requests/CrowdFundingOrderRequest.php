<?php

namespace App\Http\Requests;

use App\Models\CrowdfundingProduct;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrowdFundingOrderRequest extends Request
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
                function($attribute, $value, $fail){
                    if (!$sku = ProductSku::find($value)){
                        return $fail('商品信息异常');
                    }

                    if ($sku->product->type !== Product::TYPE_CROWDFUNDING){
                        return $fail('商品不支持众筹');
                    }

                    if (!$sku->product->on_sale){
                        return $fail('商品未上架');
                    }

                    if ($sku->product->crowdfunding->status !== CrowdfundingProduct::STATUS_FUNDING){
                        return $fail('众筹商品活动已结束');
                    }

                    if ($sku->stock === 0){
                        return $fail('商品已售完');
                    }

                    if ($this->input('amount') > 0 && $sku->stock < $this->input('amount')){
                        return $fail('库存不足');
                    }
                },
            ],
            'amount'    =>  ['required', 'integer', 'min:1'],
            'address_id'=>  [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
