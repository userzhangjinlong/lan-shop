<?php

namespace App\Admin\Controllers;

use App\Exceptions\InternalExpection;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRefundRequest;
use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class OrdersController extends Controller
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
            ->header('订单列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show(Order $order, Content $content)
    {
        return $content
            ->header('订单详情')
            ->body(view('admin.orders.show', ['order' => $order]));
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
            ->header('Edit')
            ->description('description')
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
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);

        //只展示支付了的订单并且默认按支付时间倒叙排序
        $grid->model()->whereNotNull('paid_at')->orderBy('created_at','desc');
//        $grid->model()->orderBy('created_at','desc');

        $grid->no('订单流水号');
        //展示关联关系的字段时，使用column方法
        $grid->column('user.name', '买家');
        $grid->total_amount('总金额')->sortable();
        $grid->paid_at('支付时间')->sortable();
        $grid->ship_status('物流')->display(function($value){
            return Order::$shipStatusMap[$value];
        });
        $grid->refund_status('退款状态')->display(function($value){
            return Order::$refundStatusMap[$value];
        });

        //禁用创建按钮后台不需要创建订单
        $grid->disableCreateButton();

        $grid->actions(function($actions){
            //禁用删除和编辑按钮
            $actions->disableDelete();
            $actions->disableEdit();
        });

        $grid->tools(function($tools){
            //禁用批量删除按钮
            $tools->batch(function($batch){
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Order::findOrFail($id));

        $show->id('Id');
        $show->no('No');
        $show->user_id('User id');
        $show->address('Address');
        $show->total_amount('Total amount');
        $show->remark('Remark');
        $show->paid_at('Paid at');
        $show->payment_method('Payment method');
        $show->payment_no('Payment no');
        $show->refund_status('Refund status');
        $show->refund_no('Refund no');
        $show->closed('Closed');
        $show->reviewed('Reviewed');
        $show->ship_status('Ship status');
        $show->ship_data('Ship data');
        $show->extra('Extra');
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
        $form = new Form(new Order);

        $form->text('no', 'No');
        $form->number('user_id', 'User id');
        $form->textarea('address', 'Address');
        $form->decimal('total_amount', 'Total amount');
        $form->textarea('remark', 'Remark');
        $form->datetime('paid_at', 'Paid at')->default(date('Y-m-d H:i:s'));
        $form->text('payment_method', 'Payment method');
        $form->text('payment_no', 'Payment no');
        $form->text('refund_status', 'Refund status')->default('pending');
        $form->text('refund_no', 'Refund no');
        $form->switch('closed', 'Closed');
        $form->switch('reviewed', 'Reviewed');
        $form->text('ship_status', 'Ship status')->default('pending');
        $form->textarea('ship_data', 'Ship data');
        $form->textarea('extra', 'Extra');

        return $form;
    }

    /**
     * 订单发货
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws InvalidRequestException
     */
    public function ship(Order $order, Request $request)
    {
        //判断当前订单是否已支付
        if (!$order->paid_at){
            throw new InvalidRequestException('订单尚未付款');
        }

        //判断当前订单状态是否为未发货
        if ($order->ship_status !== Order::SHIP_STATUS_PENDING){
            throw new InvalidRequestException('订单已发货');
        }

        //众筹订单发货逻辑
        if ($order->type === Order::TYPE_CROWDFUNDING && $order->items[0]->product->crowdfunding->status !== CrowdfundingProduct::STATUS_SUCCESS){
            throw new InvalidRequestException('众筹订单需要在众筹成功之后发货哦!');
        }

        //laravel5.5之后validate方法可以返回校验过的值
        $data = $this->validate($request, [
            'express_company' =>  ['required'],
            'express_no'    =>  ['required'],
        ],[],[
            'express_company'   =>  '物流公司',
            'express_no'        =>  '运单号',
        ]);

        //将订单状态改为已发货,保存物流信息
        $order->update([
            'ship_status'   =>  Order::SHIP_STATUS_DELIVERED,
            // 我们在 Order 模型的 $casts 属性里指明了 ship_data 是一个数组
            // 因此这里可以直接把数组传过去
            'ship_data'     =>  $data
        ]);
        //返回上一页
        return redirect()->back();
    }

    public function handleRefund(Order $order, HandleRefundRequest $request, OrderService $orderService)
    {
        //判断订单状态是否正确
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED){
            throw new InvalidRequestException('订单退款状态异常');
        }

        //是否同意退款
        if ($request->input('agree')){
            //同意退款
            $extra = $order->extra ?: [];
            //清空拒绝退款理由
            unset($extra['refund_disagree_reason']);
            $order->update([
                'extra' =>  $extra,
            ]);

            $orderService->refundOrder($order);
        }else{
            //不同意
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');

            //将订单的状态改为未退款
            $order->update([
                'refund_status'     =>  Order::REFUND_STATUS_PENDING,
                'extra'             =>  $extra,
            ]);
        }

        return $order;
    }
}

