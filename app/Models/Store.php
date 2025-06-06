<?php

namespace App\Models;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'manager_id',
        'name',
        'description',
        'logo',
        'cover',
        'location',
        'phones',
        'email',
        'facebook_link',
        'instagram_link',
        'youtube_link',
        'whatsup_link',
        'telegram_link',
        'is_available'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'is_available' => 'boolean',
            'manager_id' => 'integer'
        ];
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Delete media files when store is deleted
        static::deleted(function (Store $store) {
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            if ($store->cover) {
                Storage::disk('public')->delete($store->cover);
            }
        });
    }

    /**
     * Get the is_available attribute correctly casted
     *
     * @param  mixed  $value
     * @return bool
     */
    public function getIsAvailableAttribute($value)
    {
        return (bool)$value;
    }

    /**
     * Set the is_available attribute correctly for database
     *
     * @param  mixed  $value
     * @return void
     */
    public function setIsAvailableAttribute($value)
    {
        $this->attributes['is_available'] = $value ? 1 : 0;
    }

    /**
     * Get the full URL for the store logo
     *
     * @return string|null
     */
    public function getLogoAttribute()
    {
        return $this->attributes['logo'] ? asset($this->attributes['logo']) : null;
    }

    /**
     * Get the full URL for the store cover image
     *
     * @return string|null
     */
    public function getCoverAttribute()
    {
        return $this->attributes['cover'] ? asset($this->attributes['cover']) : null;
    }

    /**
     * Scope a query to filter products based on availability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool|null  $isAvailable (Optional) Filter by availability status. If null, returns all.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable(Builder $query, ?bool $isAvailable = null): Builder
    {
        return $query->when($isAvailable !== null, fn($q) => $q->where('is_available', $isAvailable));
    }

    /**
     * Get the user that owns the Store
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * The categories that belong to the Store
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_store');
    }

    /**
     * Get all of the products for the Store
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id', 'id');
    }

    /**
     * the store hase many couponse
     * @return HasMany<Coupon, Store>
     */
    public function coupons(){
        return $this->hasMany(Coupon::class,'store_id', 'id');
    }

    /**
     * Get all of the orders for the Store
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'store_id', 'id');
    }
}
