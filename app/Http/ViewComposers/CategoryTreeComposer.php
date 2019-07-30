<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-29
 * Time: 下午2:17
 */

namespace App\Http\ViewComposers;


use App\Services\CategoryService;
use Illuminate\View\View;

class CategoryTreeComposer
{
    /**
     * @var
     */
    protected $categoryService;

    /**
     * 使用依赖注入自动注入我们的service类
     * CategoryTreeComposer constructor.
     * @param CategoryService $categoryService
     */
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * 当渲染指定的模板时，Laravel 会调用 compose 方法
     * @param View $view
     */
    public function compose(View $view)
    {
        //使用view方法注入遍历
        $view->with('categoryTree', $this->categoryService->getCategoryTree());
    }

}