<?php

namespace App\Admin\Controllers;

use App\Models\CouponCode;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CouponCodesController extends Controller
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
            ->header('优惠券列表')
            ->body($this->grid());
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑优惠券')
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
            ->header('新增优惠券')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CouponCode);

        //安创建时间导学
        $grid->model()->orderBy('created_at', 'desc');
        $grid->id('ID')->sortable();
        $grid->name('名称');
        $grid->code('优惠码');
        $grid->description('描述');
//        $grid->type('类型')->display(function($value){
//            return CouponCode::$typeMap[$value];
//        });

        /*//根据不同的折扣类型用对应的方式来展示
        $grid->value('折扣')->display(function($value){
            return $this->type === CouponCode::TYPE_FIXED ? '￥'.$value : $value.'%';
        });

        $grid->min_amount('最低金额');
        $grid->total('总量');
        $grid->used('已用');*/
        $grid->column('usage', '用量')->display(function ($value) {
            return "{$this->used} / {$this->total}";
        });
        $grid->enabled('是否启用')->display(function ($value){
            return $value ? '是' : '否';
        });
        $grid->created_at('创建时间');
        $grid->actions(function ($actions){
            $actions->disableView();
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CouponCode);

        $form->display('id', 'ID');
        $form->text('name', '名称')->rules('required');
        //对于优惠码 code 字段，我们的第一个校验规则是 nullable，允许用户不填，不填的情况优惠码将由系统生成
        $form->text('code', '优惠码')->rules(function($form){
            //如果$form->model()->id不为空,代表是编辑操作
            if ($id = $form->model()->id){
                //编辑
                return 'nullable|unique:coupon_codes,code,'.$id.'.id';
            }else{
                //新增
                return 'nullable|unique:coupon_codes';
            }
        });
        $form->radio('type', '类型')->options(CouponCode::$typeMap)->rules('required')->default(CouponCode::TYPE_FIXED);
        //对于折扣 value 字段，我们的校验规则是一个匿名函数，当我们的校验规则比较复杂，或者需要根据用户提交的其他字段来判断时就可以用匿名函数的方式来定义校验规则。
        $form->text('value', '折扣')->rules(function($form){
            if (request()->input('type') === CouponCode::TYPE_PERCENT){
                //如果选择了百分比折扣类型,那么折扣范围只能是1~99
                return 'required|numeric|between:1,99';
            }else{
                //否则大于0.01即可
                return 'required|numeric|min:0.01';
            }
        });
        $form->text('total', '总量')->rules('required|numeric|min:0');
        $form->decimal('min_amount', '最低金额')->rules('required|numeric|min:0');
        $form->datetime('not_before', '开始时间');
        $form->datetime('not_after', '结束时间');
        $form->radio('enabled', '启用')->options(['1'=>'是','0'=>'否']);
        //$form->saving() 方法用来注册一个事件处理器，在表单的数据被保存前会被触发，这里我们判断如果用户没有输入优惠码，就通过 findAvailableCode() 来自动生成
        $form->saving(function (Form $form){
            if (!$form->code){
                $form->code = CouponCode::findAvailableCode();
            }
        });

        return $form;
    }
}
