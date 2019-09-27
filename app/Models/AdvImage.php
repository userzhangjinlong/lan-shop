<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * @param $adv_id
     * @return mixed
     */
    public function AdvImages($adv_id)
    {
        return $this
            ->where('adv_id', $adv_id)
            ->where('is_show', 1)
            ->where('end_at', '>', Carbon::now())
            ->orderBy('sort', 'asc')
            ->select('image')
            ->get();
    }

    /**
     * @return mixed
     */
    public function getImageUrlAttribute()
    {
        return \Storage::disk('public')->url($this->image);
    }
}
