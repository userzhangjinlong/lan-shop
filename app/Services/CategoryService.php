<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-29
 * Time: 下午1:56
 */

namespace App\Services;


use App\Models\Category;

class CategoryService
{
    /**
     * 递归方法
     * @param null $parentId 代表要获取子类目的父类目 ID，null 代表获取所有根类目
     * @param null $allCategories 代表数据库中所有的类目，如果是 null 代表需要从数据库中查询
     * @return Category[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getCategoryTree($parentId = null, $allCategories = null)
    {
        if (is_null($allCategories)){
            //从数据库中一次性取出所有类目
            $allCategories = Category::all();
        }

        return $allCategories
            //从所有类目中挑选出父类目ID为$parentId的分类
            ->where('parent_id', $parentId)
            //遍历这些类目,并用返回值构建一个新的集合
            ->map(function(Category $category) use ($allCategories){
                $data = ['id' => $category->id, 'name' => $category->name];
                //如果当前分类不是父分类,则直接返回
                if (!$category->is_directory){
                    return $data;
                }
                //否则递归调用本方法,将返回的值放入children字段中
                $data['children'] = $this->getCategoryTree($category->id, $allCategories);

                return $data;
            });
    }
}