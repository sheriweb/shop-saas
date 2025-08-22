<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Shop extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name','owner_name','email','phone','whatsapp_number','address','trial_ends_at','status','logo_path'
    ];

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasActiveSubscription(): bool
    {
        // Trial valid?
        if ($this->trial_ends_at && Carbon::parse($this->trial_ends_at)->isFuture()) {
            return true;
        }

        // Active subscription with end_date today or in the future
        return $this->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();
    }

    /**
     * @return HasMany
     */
    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsappAccount::class);
    }

    /**
     * @return HasMany
     */
    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
    }
}
