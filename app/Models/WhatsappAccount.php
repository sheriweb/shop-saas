<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappAccount extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['shop_id','provider','api_key','phone_number','meta'];

    /**
     * @var string[]
     */
    protected $casts = ['meta'=>'array'];

    /**
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
