@extends('layouts.app')
@section('title', '首页')

@section('content')
    <div class="container">
        <!--二级菜单、图片轮播-->
            <div class="row">
                <div id="carousel-explorer" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">
                        @foreach($banner_list as $k => $v)
                        <li data-target="#carousel-explorer" data-slide-to="{{ $k }}" @if($k == 0) class="active" @endif></li>
                        @endforeach
                    </ol>

                    <div class="carousel-inner" role="listbox">

                        @foreach($banner_list as $k => $v)
                        <div class="item @if($k == 0)  active @endif">
                            <img src="{{ $v->image_url }}" alt="...">
                            <div class="carousel-caption">
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <a class="left carousel-control" href="#carousel-explorer" role="button" data-slide="prev">
                        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="right carousel-control" href="#carousel-explorer" role="button" data-slide="next">
                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
            </div>
        <!--第三块，热卖商品-->
        <div class="row row-goods">
            <div class="col-md-12 title">
                <div class="col-md-5 ">
                    <div class="k-border">
                        <div class="border bor-l">
                            <div class="border bor-r">
                                <div class="bor-m"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 ">热卖商品</div>
                <div class="col-md-5 ">
                    <div class="k-border">
                        <div class="border bor-l">
                            <div class="border bor-r">
                                <div class="bor-m"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <a href="goodsinfo.html"><img src="images/4.jpg" alt=""></a>
            </div>
            <div class="col-md-3">
                <img src="images/5.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/7.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/6.jpg" alt="">
            </div>
        </div>
        <!--第四块，特价商品-->
        <div class="row row-goods">
            <div class="col-md-12 title">
                <div class="col-md-5 ">
                    <div class="k-border">
                        <div class="border bor-l">
                            <div class="border bor-r">
                                <div class="bor-m"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 ">特价商品</div>
                <div class="col-md-5 ">
                    <div class="k-border">
                        <div class="border bor-l">
                            <div class="border bor-r">
                                <div class="bor-m"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <img src="images/4.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/5.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/7.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/6.jpg" alt="">
            </div>
        </div>
        <!--第五块，最新商品-->
        <div class="row row-goods">
            <div class="col-md-12 title">
                <div class="col-md-5 ">
                    <div class="k-border">
                        <div class="border bor-l">
                            <div class="border bor-r">
                                <div class="bor-m"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 ">最新商品</div>
                <div class="col-md-5 ">
                    <div class="k-border">
                        <div class="border bor-l">
                            <div class="border bor-r">
                                <div class="bor-m"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <img src="images/4.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/5.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/7.jpg" alt="">
            </div>
            <div class="col-md-3">
                <img src="images/6.jpg" alt="">
            </div>
        </div>
    </div>

@stop