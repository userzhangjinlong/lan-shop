<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncOneProductToEs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    protected $product;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     *异步任务单个商品跟新或者新增进Elasticsearch中
     * @return void
     */
    public function handle()
    {
        $data = $this->product->toESArray();
        app('es')->index([
            'id'    =>  $data['id'],
            'index' =>  'products',
            'type'  =>  '_doc',
            'body'  =>  $data
        ]);

    }
}
