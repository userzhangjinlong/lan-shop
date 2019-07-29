<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CouponCodesController extends Controller
{
    /**
     * 显示优惠券信息
     * @param $codes
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($codes, Request $request)
    {
        //判断优惠券是否存在
        if (!$record = CouponCode::where('code', $codes)->first()){
            abort(404);
        }

        $record->checkAvailable($request->user());
        return $record;
    }
}
