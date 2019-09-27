<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModelsAdvsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 40)->comment('广告位名称');
            $table->unsignedInteger('width')->comment('广告位图片宽度');
            $table->unsignedInteger('height')->comment('广告位图片高度');
            $table->boolean('status')->comment('是否禁用');
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
        Schema::dropIfExists('advs');
    }
}
