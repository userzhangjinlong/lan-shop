<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adv_images', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 20)->comment('广告图名称');
            $table->integer('adv_id')->commet('广告位主键');
            $table->index('adv_id');
            $table->integer('sort')->comment('排序');
            $table->string('image')->comment('图片地址');
            $table->dateTime('start_at')->comment('开始时间');
            $table->dateTime('end_at')->comment('结束时间');
            $table->boolean('is_show')->default(true)->comment('是否有效');
            $table->string('url')->comment('广告图链接')->default('')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adv_images');
    }
}
