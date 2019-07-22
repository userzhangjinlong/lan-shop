<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * 商品首页
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        //创建查询构造器
        $bulider = Product::where('on_sale', true);
        //判断是否有search参数的提交，如果有赋值给$search变量 search参数用于模糊搜索商品
        if ($search = $request->input('search')){
            $like = '%'.$search.'%';
            //模糊搜索商品标题 详情 SKU标题 sku描述
            $bulider->where(function ($query) use ($like){
                $query->where('title','like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('skus', function ($query) use ($like){
                        $query->where('title', 'like', $like)->orWhere('description', 'like', $like);
                    });
            });
        }

        //是否有order参数，如果有就赋值给order， order控制商品排序规则
        if ($order = $request->input('order')){
            //是否以_asc 或者 _desc结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)){
                //如果字符串的开头是三个字符串之一，说明是一个合法的排序值
                if (in_array($m[1], ['price', 'sold_count', 'rating'])){
                    $bulider->orderBy($m[1], $m[2]);
                }
            }
        }

        $products = $bulider->paginate(env('PAGINATE'));

        return view('products.index', [
            'products' => $products,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ]
        ]);
    }
}
