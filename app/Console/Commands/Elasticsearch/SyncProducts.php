<?php

namespace App\Console\Commands\Elasticsearch;

use App\Models\Product;
use Illuminate\Console\Command;

class SyncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:sync-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将商品数据同步到 Elasticsearch';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //获取Elasticsearch对象
        $es = app('es');

        Product::query()
        //预加载sku,属性避免N+1问题
        ->with(['skus', 'properties'])
        //使用chunkbyid避免一次性加载过多数据
        ->chunkById('100', function ($products) use ($es){
            $this->info(sprintf('正在同步 ID 范围为 %s 至 %s 的商品', $products->first()->id, $products->last()->id));

            //初始化请求
            $req = ['body' => []];
            //遍历商品
            foreach ($products as $product){
                //将商品组装转为Elasticsearch需要的数据
                $data = $product->toESArray();

                $req['body'][] = [
                    'index' =>  [
                        '_index' => 'products',
                        '_type'  => '_doc',
                        '_id'     => $data['id'],
                    ],
                ];
                $req['body'][] = $data;
            }

            try{
                //bulk方法批量创建
                $es->bulk($req);
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }

        });

        $this->info('同步完成');

    }
}
