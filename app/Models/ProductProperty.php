<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductProperty extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name', 'value'
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * 关联产品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(){
        return $this->belongsTo(Product::class);
    }
}
