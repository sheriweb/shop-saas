<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['name','duration_days','price','features'];

    /**
     * @var string[]
     */
    protected $casts = ['features'=>'array'];

    /**
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
