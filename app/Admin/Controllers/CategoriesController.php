<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('商品分类列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑商品分类')
            ->body($this->form(true)->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('创建商品分类')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category);

        $grid->id('ID')->sortable();
        $grid->name('分类名称');
        $grid->level('层级');
        $grid->is_directory('是否目录')->display(function($value){
            return $value ? '是' : '否';
        });

        $grid->path('分类路径');
        $grid->actions(function($actions){
            //不展示laravel-admin默认的查看按钮
            $actions->disableView();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Category::findOrFail($id));

        $show->id('Id');
        $show->name('Name');
        $show->parent_id('Parent id');
        $show->is_directory('Is directory');
        $show->level('Level');
        $show->path('Path');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($isEditing = false)
    {
        $form = new Form(new Category);

        $form->text('name', '分类名称')->rules('required');

        //如果是编辑的情况
        if ($isEditing){
            //不允许用户修改是否目录和父分类的字段的值
            //用display的方法来展示值.with方法接收一个匿名函数,会把字段值传给匿名函数并把返回值展示出来
            $form->display('is_directory', '是否目录')->with(function ($value){
                return $value ? '是' : '否';
            });
            //支持用符号.来展示关联关系的字段
            $form->display('parent.name', '父级分类');
        }else{
            //定义一个名为是否目录的单选框
            $form->radio('is_directory', '是否目录')->options(['1' => '是', '0' => '否'])->default('0')->rules('required');
            //定义一个父级分类的下拉框
            $form->select('parent_id', '父级分类')->ajax('/admin/api/categories');
        }

        return $form;
    }

    /**
     * 定义下拉框搜索接口
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function apiIndex(Request $request)
    {
        //用户输入的值通过q参数获取
        $search = $request->input('q');
        $result = Category::query()
            ->where('is_directory', boolval($request->input('is_directory', true)))
            ->where('name', 'like', '%'.$search.'%')
            ->paginate();

        //把查询出来的数据进行重新组装成laravel-admin需要的格式
        $result->setCollection($result->getCollection()->map(function (Category $category){
            return ['id' => $category->id, 'text' => $category->getFullNmaeAttribute()];
        }));

        return $result;
    }
}
