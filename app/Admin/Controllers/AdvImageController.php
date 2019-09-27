<?php

namespace App\Admin\Controllers;

use App\Models\Adv;
use \App\Models\AdvImage;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use function foo\func;
use Illuminate\Http\Request;

class AdvImageController extends Controller
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
            ->header('广告图管理')
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
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit( $id));
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
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdvImage);

        $grid->id('ID');
        $grid->name('广告名称');
        $grid->adv_id('所属广告位')->display(function ($id){
            $adv = Adv::find($id);
            return $adv->name;
        });
        $grid->sort('排序');
        $grid->is_show('是否显示')->display(function ($value){
            return $value ? '是' : '否';
        });
        $grid->start_at('开始时间');
        $grid->end_at('结束时间');
        $grid->actions(function ($actions){
            $actions->disableview();
            $actions->disableedit();
            $actions->append('<a class="grid-row-view" href="'.$actions->getkey().'/edit"><i class="fa fa-edit"></i></a>');
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
        $show = new Show(AdvImage::findOrFail($id));

        $show->id('Id');
        $show->name('Name');
        $show->adv_id('Adv id');
        $show->start_at('Start at');
        $show->end_at('End at');
        $show->is_show('Is show');
        $show->url('Url');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdvImage);

        $form->text('name', '广告名称')->rules('required');
        $form->select('adv_id', '广告位')->options(function ($id){
            $adv = Adv::find($id);
            if ($adv){
                return [$adv->id => $adv->name];
            }
        })->ajax('/admin/api/advs');

        $form->datetime('start_at', '开始时间')->default(date('Y-m-d H:i:s'))->rules('required|date');
        $form->datetime('end_at', '开始时间')->default(date('Y-m-d H:i:s', strtotime('+1 year')))->rules('required|date');
        $form->image('image', '上传图片')->rules('required|image');
        $form->switch('is_show', '是否显示')->default(1);
        $form->url('url', '广告链接');
        $form->text('sort', '排序')->default(255)->rules('required');

        return $form;
    }

    /**
     * @param AdvImage $advImage
     * @param $adv_id
     * @param $id
     * @return mixed
     */
    public function destory(AdvImage $advImage, $adv_id, $id)
    {
        return $advImage->where('id', $id)->delete();
    }

}
