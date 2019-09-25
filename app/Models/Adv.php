<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adv extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name', 'width', 'height',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function advImage()
    {
        return $this->hasMany(AdvImage::class, 'adv_id');
    }

}
