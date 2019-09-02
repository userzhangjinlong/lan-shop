<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\CrowdFundingOrderRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SeckillOrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * 提交订单
     *
     * 利用 Laravel 的自动解析功能注入 $orderService 类
     * @param OrderRequest $request
     * @param OrderService $orderService
     * @return mixed
     */
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $coupon = null;

        //提交了优惠码
        if ($code = $request->input('coupon_code')){
            $coupon = CouponCode::where('code', $code)->first();
            if (!$coupon){
                throw new CouponCodeUnavailableException('优惠券不存在');
            }
        }

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'), $coupon);
    }

    /**
     * 提交众筹订单
     * @param CrowdFundingOrderRequest $request
     * @param OrderService $orderService
     */
    public function crowdfunding(CrowdFundingOrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $sku = ProductSku::find($request->input('sku_id'));
        $address = UserAddress::find($request->input('address_id'));
        $amount = $request->input('amount');

        return $orderService->crowdfunding($user, $address, $sku, $amount);
    }

    /**
     * 提交秒杀订单
     * @param SeckillOrderRequest $request
     * @param OrderService $orderService
     * @return mixed
     */
    public function seckill(SeckillOrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $sku = ProductSku::find($request->input('sku_id'));
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->seckill($user, $address, $sku);
    }

    /**
     * 订单列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $orders = Order::query()
            // 使用 with 方法预加载，避免N + 1问题
            ->with(['items.product','items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(env('PAGINATE'));

        return view('orders.index', ['orders' => $orders]);

    }

    /**
     * 订单详情
     *
     * 这里的 load() 方法与上一章节介绍的 with() 预加载方法有些类似，称为 延迟预加载，不同点在于 load() 是在已经查询出来的模型上调用
     *
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Order $order, Request $request){
        //检查权限只有当前用户能查看当前用户自己的信息
        $this->authorize('own', $order);
        return view('orders.show', ['order' => $order->load(['items.product', 'items.productSku'])]);
    }

    /**
     * 确认收货
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function received(Order $order, Request $request)
    {
        //校验权限
        $this->authorize('own', $order);

        //判断订单的发货状态是否为已发货
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED){
            throw new InvalidRequestException('发货状态异常');
        }

        //更改状态已收货
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        // 返回订单信息
        return $order;

    }

    /**
     * 订单评价页面
     * @param Order $order
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function review(Order $order)
    {
        //校验权限
        $this->authorize('own', $order);

        //判断是否已支付
        if (!$order->paid_at){
            throw new InvalidRequestException('订单尚未支付,还不能进行评价哟!');
        }

        //使用load方法加载关联数据,避免N+1性能问题
        return view('orders.review', ['order' => $order->load(['items.productSku','items.product'])]);

    }

    /**
     * 提交评价
     * @param Order $order
     * @param SendReviewRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sendReview(Order $order, SendReviewRequest $request)
    {
        //校验全新啊
        $this->authorize('own', $order);

        if (!$order->paid_at){
            throw new InvalidRequestException('订单尚未支付,不可进行评价');
        }

        //判读订单是否已评价
        if ($order->reviewed){
            throw new InvalidRequestException('订单已经评价,不可重复提交');
        }

        $reviews = $request->input('reviews');

        //开启事物
        \DB::transaction(function() use ($reviews, $order){
            //遍历用户提交的数据
            foreach($reviews as $review){
                $orderItem = $order->items()->find($review['id']);

                //保存评价和评分
                $orderItem->update([
                    'rating'        =>  $review['rating'],
                    'review'        =>  $review['review'],
                    'reviewed_at'   =>  Carbon::now(),
                ]);

                //将订单标记已评价
                $order->update(['reviewed'  =>  true]);
            }

            //触发事件更新评价数量和评分
            event(new OrderReviewed($order));

        });

        return redirect()->back();
    }


    /**
     * 申请退款
     * @param Order $order
     * @param ApplyRefundRequest $request
     * @return Order
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function applyRefund(Order $order, ApplyRefundRequest $request)
    {
        //校验用户
        $this->authorize('own', $order);

        //判断订单是否已付款
        if (!$order->paid_at){
            throw new InvalidRequestException('订单尚未支付,不能发起退款请求');
        }

        if ($order->type === Order::TYPE_CROWDFUNDING){
            throw new InvalidRequestException('众筹订单不支持退款');
        }

        //判断退款状态是否正确
        if ($order->refund_status !== Order::REFUND_STATUS_PENDING){
            throw new InvalidRequestException('订单已经申请过退款,请勿重复申请');
        }

        //将用户输入的退款理由当道订单的extra字段中
        $extra = $order->extra ?: [];
        $extra['refund_reason'] = $request->input('reason');
        //将订单状态改为已退款
        $order->update([
            'refund_status' =>  Order::REFUND_STATUS_APPLIED,
            'extra' =>  $extra,
        ]);

        return $order;
    }

}
