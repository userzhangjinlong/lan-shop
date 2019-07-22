<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
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

    /**
     * 商品详情
     * @param Product $product
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws InvalidRequestException
     */
    public function show(Product $product, Request $request){
        if (!$product->on_sale){
            throw new InvalidRequestException('商品未上架');
        }

        $favored=false;
        //用户未登录时返回的是null，已登录时返回的是对应用户对象
        if ($user = $request->user()){
            //当前用户已登录，从当前用户已收藏的商品中搜索id为当前id的商品
            //boolval() 函数用于将值转换为布尔值
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        return view('products.show', ['product' => $product, 'favored' => $favored]);
    }

    /**
     * 收藏商品
     * @param Product $product
     * @param Request $request
     * @return array
     */
    public function favor(Product $product, Request $request){
        $user = $request->user();

        if ($user->favoriteProducts()->find($product->id)){
            return [];
        }

        $user->favoriteProducts()->attach($product);
        return [];
    }

    /**
     * 取消收藏
     * @param Product $product
     * @param Request $request
     * @return array
     */
    public function disfavor(Product $product, Request $request){
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }

    public function favorites(Request $request){
        $products = $request->user()->favoriteProducts()->paginate(env('PAGINATE'));
        return view('products.favorites', ['products' => $products]);
    }

}
