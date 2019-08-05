<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    /**
     * 商品首页
     * @param Request $request
     * @param CategoryService $categoryService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, CategoryService $categoryService)
    {
        $page = $request->input('page', 1);
        $perPage = env('PAGINATE');

        //构建查询
        $params = [
            'index' => 'products',
            'type'  =>  '_doc',
            'body'  =>  [
                'from'  =>  ($page - 1)*$perPage,
                'size'  =>  $perPage,
                'query' =>  [
                    'bool'  =>  [
                        'filter'    =>  [
                            ['term' => ['on_sale' => true]],
                        ],
                    ],
                ]
            ],
        ];

        //是否有提交order参数,如果有就赋值给$order变量
        //是否有order参数，如果有就赋值给order， order控制商品排序规则
        if ($order = $request->input('order')){
            //是否以_asc 或者 _desc结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)){
                //如果字符串的开头是三个字符串之一，说明是一个合法的排序值
                if (in_array($m[1], ['price', 'sold_count', 'rating'])){
                    $params['body']['sort'] = [[$m[1] => $m[2]]];
                }
            }
        }

        //如果有传入category_id字段,并且在数据库中有对应的类目
        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            //如果这是一个父分类 则使用 category_path 来筛选
            if ($category->is_directory){
                //则筛选出该分分类下面的所有子分类的商品
                $params['body']['query']['bool']['filter'][] = [
                    'prefix' => ['category_path' => $category->path.$category->id.'-'],
                ];
            }else{
                //如果不是父分类 直接筛选category_id
                $params['body']['query']['bool']['filter'][] = ['term' => ['category_id' => $category->id]];
            }
        }

        //判断是否有search参数的提交，如果有赋值给$search变量 search参数用于模糊搜索商品
        if ($search = $request->input('search', '')){
            //将搜索词根据空格拆分成数组,并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            foreach ($keywords as $keyword){
                $params['body']['query']['bool']['must'] = [
                    'multi_match' => [
                        'query'     => $keyword,
                        'fields'    => [
                            'title^3',
                            'long_title^2',
                            'category^2',//分类名称
                            'description',
                            'skus_title',
                            'skus_description',
                            'properties_value',
                        ],
                    ],
                ];
            }
        }

        $result = app('es')->search($params);

        //通过collect函数将返回结果转为集合 并通过集合的pluck方法取到返回的商品id数组
        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();

        $products = Product::whereIn('id', $productIds)
            //orderByRaw 可以让我们用原生的sql来给查询结果排序
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))
            ->get();

        //返回一个LengthAwarePaginator对象
        $pager = new LengthAwarePaginator($products, $result['hits']['total'], $perPage, $page, [
            'path' => route('products.index', false),//手动构建分页url
        ]);

        /*//创建查询构造器
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

        //如果有传入category_id字段,并且在数据库中有对应的类目
        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            //如果这是一个父分类
            if ($category->is_directory){
                //则筛选出该分分类下面的所有子分类的商品
                $bulider->whereHas('category', function ($query) use ($category){
                    $query->where('path', 'like', $category->path.$category->id.'-%');
                });
            }else{
                //如果不是父分类直接筛选该分类下面的商品
                $bulider->where('category_id', $category->id);
            }
        }



        $products = $bulider->paginate(env('PAGINATE'));*/

        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category??null,
            // 将类目树传递给模板文件
//            'categoryTree' => $categoryService->getCategoryTree(),
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

        //订单商品的评价列表
        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku'])
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at')
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)
            ->get();

        return view('products.show', ['product' => $product, 'favored' => $favored, 'reviews' => $reviews]);
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

    /**
     * 收藏列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function favorites(Request $request){
        $products = $request->user()->favoriteProducts()->paginate(env('PAGINATE'));
        return view('products.favorites', ['products' => $products]);
    }

}
