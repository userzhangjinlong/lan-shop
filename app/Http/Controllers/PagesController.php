<?php

namespace App\Http\Controllers;

use App\Models\AdvImage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function root()
    {
        $advimages = new AdvImage();
        $banner_list = $advimages->AdvImages(1);


        return view('pages.root', ['banner_list' => $banner_list]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function emailVerifyNotice(Request $request)
    {
        return view('pages.email_verify_notice');
    }
}
