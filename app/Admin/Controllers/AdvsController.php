<?php

namespace App\Admin\Controllers;


use \App\Models\Adv;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class AdvsController extends Controller
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
            ->header('广告位列表')
            ->description('')
            ->body($this->grid());
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
            ->header('编辑')
            ->description('')
            ->body($this->form()->edit($id));
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
            ->header('新增')
            ->description('')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Adv);

        $grid->id('ID');
        $grid->name('广告位名称');
        $grid->width('宽度');
        $grid->height('高度');
        $grid->status('状态')->display(function ($value){
            return $value ? '开启' : '禁用';
        });
        $grid->created_at('创建时间');
        $grid->actions(function ($actions){
            $actions->disableview();
            $actions->append('<a class="grid-row-view" href="advimages/'.$actions->getkey().'"><i class="fa fa-eye"></i></a>');
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
        $show = new Show(Adv::findOrFail($id));

        $show->id('ID');
        $show->name('广告位名称');
        $show->width('宽度');
        $show->height('高度');
        $show->status('状态');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Adv);

        $form->text('name', '广告位名称')->rules('required');
        $form->number('width', '宽度')->rules('required');
        $form->number('height', '高度')->rules('required');
        $form->switch('status', '开启状态')->default(true);

        return $form;
    }

    /**
     * @param $id
     * @return bool|null
     * @throws \Exception
     */
    public function destory($id)
    {
        $adv = new Adv();

        return $adv->where('id', $id)->delete();
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
        $result = Adv::query()
            ->where('name', 'like', '%'.$search.'%')
            ->paginate();

        //把查询出来的数据进行重新组装成laravel-admin需要的格式
        $result->setCollection($result->getCollection()->map(function (Adv $adv){
            return ['id' => $adv->id, 'text' => $adv->name];
        }));

        return $result;
    }

}
