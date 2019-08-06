<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;
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

        //新建查询构造器对象,设置只搜索上架商品, 设置分页
        $bulider = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            //调用分类查询构造器
            $bulider->category($category);
        }

        if ($search = $request->input('search', '')){
            $keywords = array_filter(explode(' ', $search));
            //调用搜索关键词构造器
            $bulider->keywords($keywords);
        }

        if ($search || isset($category)){
            //调用查询构造器分面搜索
            $bulider->aggregateProperties();
        }

        //从用户请求参数获取filters
        $propertyFilters = [];
        if ($filterString = $request->input('filters')){
            //将获取到的字符串用符号"|"拆分成数组
            $filterArray = explode('|', $filterString);
            foreach ($filterArray as $filter){
                // 将字符串用符号 : 拆分成两部分并且分别赋值给 $name 和 $value 两个变量
                list($name, $value) = explode(':', $filter);

                //将用户筛选的属性添加到数组中
                $propertyFilters[$name] = $value;

                //调用查询构造器属性筛选
                $bulider->propertyFilter($name, $value);
            }
        }




        //是否有提交order参数,如果有就赋值给$order变量
        //是否有order参数，如果有就赋值给order， order控制商品排序规则
        if ($order = $request->input('order')){
            //是否以_asc 或者 _desc结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)){
                //如果字符串的开头是三个字符串之一，说明是一个合法的排序值
                if (in_array($m[1], ['price', 'sold_count', 'rating'])){
                    //调用构造器排序
                    $bulider->orderBy($m[1], $m[2]);
                }
            }
        }


        $result = app('es')->search($bulider->getParams());

        $properties = [];
        //如果返回结果里有aggregations字段,说明做了分面搜索
        if (isset($result['aggregations'])){
            //使用collect函数将返回值转为集合
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function($bucket){
                    //通过map方法取出我们需要的字段
                    return [
                        'key'   =>  $bucket['key'],
                        'values'=>  collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })
                ->filter(function($property) use ($propertyFilters){
                    //过滤只剩下一个值 或者 已经在筛选属性条件里面的值
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]);
                });
        }

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
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
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
