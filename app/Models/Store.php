<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
     * Get the full URL for the store logo
     *
     * @return string|null
     */
    public function getLogoAttribute()
    {
        return $this->attributes['logo'] ? asset(Storage::url($this->attributes['logo']) ): null;
    }

    /**
     * Get the full URL for the store cover image
     *
     * @return string|null
     */
    public function getCoverAttribute()
    {
        return $this->attributes['cover'] ? asset(Storage::url($this->attributes['cover'])) : null;
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
}
