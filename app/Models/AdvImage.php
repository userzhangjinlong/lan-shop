<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvImage extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name', 'start_at', 'end_at', 'is_show', 'url'
    ];

    /**
     * @var array
     */
    protected $dates = ['start_at', 'end_at'];

    /**
     * @var array
     */
    protected $casts = [
        'is_show' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adv()
    {
        return $this->belongsTo(Adv::class);
    }
}
