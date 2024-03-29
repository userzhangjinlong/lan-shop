<?php

namespace App\Providers;

use App\Http\ViewComposers\CategoryTreeComposer;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Logger;
use Yansongda\Pay\Pay;
use Elasticsearch\ClientBuilder as ESClientBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //'products.index','products.show' 指定视图显示 *所有视图
        View::composer(['*'], CategoryTreeComposer::class);
        //将carbon对象的diffForHumans返回中文
        Carbon::setLocale('zh');

        //本地环境记录mysql慢日志查询
        if (app()->environment('local')){
            \DB::listen(function ($query){
                \Log::info(Str::replaceArray('?',$query->bindings, $query->sql));
            });
        }

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        /**
         * 往服务容器中注入一个名为alipay的单列对象
         */
        $this->app->singleton('alipay', function (){
            $config = config('pay.alipay');
            //支付回调路由设置
//            $config['notify_url'] = route('payment.alipay.notify'); //服务器回调地址
            //https://requestbin.fullcontact.com 专门用于生成服务器零时使用地址的网址 生成一个48小时的随机网址获取回调地址参数保证功能正常使用gmt_create参数提交正常支付 本地环境
//            $config['notify_url'] = 'http://requestbin.fullcontact.com/1h8n6451'; //服务器回调地址
            $config['notify_url'] = ngrok_url('payment.alipay.notify'); //服务器回调地址
            $config['return_url'] = route('payment.alipay.return'); //前段回调地址
            //判断当前项目运行环境是否为线上环境
            if (app()->environment() !== 'production'){
                $config['mode'] = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            }else{
                $config['log']['level'] = Logger::WARNING;
            }

            // 调用 Yansongda\Pay 来创建一个支付宝支付对象
            return Pay::alipay($config);
        });

        /**
         * 往服务容器中注入一个名为wechat_pay的单列对象
         */
        $this->app->singleton('wechat_pay', function(){
            $config = config('pay.wechat');
            //微信支付成功回调地址
            //            $config['notify_url'] = route('payment.wecaht.notify'); //服务器回调地址
//            $config['notify_url'] = 'http://requestbin.fullcontact.com/1f64x5o1';
            $config['notify_url'] = ngrok_url('payment.alipay.notify');
            if (app()->environment() !== 'production') {
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }

            return Pay::wechat($config);
        });

        /**
         * 注册一个名为 es 的单例对象
         */
        $this->app->singleton('es', function (){
            //从配置文件中读取Elasticsearch的服务器hosts列表
            $bulider = ESClientBuilder::create()->setHosts(config('app.elasticsearch.hosts'));
            //如果是开发环境
            if ($this->app->environment() === 'local'){
                //配置日志,Elasticsearch的请求和返回数据将打印到日志文件中,方便我们调试.
                $bulider->setLogger(app('log')->getMonolog());
            }

            return $bulider->build();
        });

    }
}
