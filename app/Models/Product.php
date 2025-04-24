<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'price',
        'video',
        'is_available',
    ];

    /**
     * Get the full URL for the product video
     *
     * @return string|null
     */
    public function getVideoAttribute()
    {
        return $this->attributes['video'] ? asset($this->attributes['video']) : null;
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
     * Scope a query to filter products by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|array|null  $categoryId (Optional) Filter by category ID or array of IDs. If null, returns all.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory(Builder $query, $categoryId = null): Builder
    {
        return $query->when($categoryId !== null, function ($q) use ($categoryId) {
            if (is_array($categoryId)) {
                return $q->whereIn('category_id', $categoryId);
            }
            return $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope a query to filter products by store.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|array|null  $storeId (Optional) Filter by store ID or array of IDs. If null, returns all.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStore(Builder $query, $storeId = null): Builder
    {
        return $query->when($storeId !== null, function ($q) use ($storeId) {
            if (is_array($storeId)) {
                return $q->whereIn('store_id', $storeId);
            }
            return $q->where('store_id', $storeId);
        });
    }

    /**
     * Get the store that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get the category that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get all of the images for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    /**
     * Get all of the variants for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }
}
