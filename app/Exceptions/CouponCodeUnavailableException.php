<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Throwable;

class CouponCodeUnavailableException extends Exception
{
    /**
     * CouponCodeUnavailableException constructor.
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = "", int $code = 403)
    {
        parent::__construct($message, $code);
    }

    /**
     *  当这个异常被触发时，会调用 render 方法来输出给用户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function render(Request $request)
    {
        //如果用户通过Api请求,则返回JSON格式的错误信息
        if ($request->expectsJson()){
            return response()->json(['msg' => $this->message], $this->code);
        }

        //否则返回上一页并带上错误信息
        return redirect()->back()->withErrors(['coupon_code' => $this->message]);
    }

}
