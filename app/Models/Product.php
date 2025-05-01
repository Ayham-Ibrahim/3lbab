<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_available' => 'boolean',
        'category_id' => 'integer',
        'store_id' => 'integer',
    ];

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
     * Scope to filter products available in specific store(s) (with availability check)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array|null $storeId Store ID or array of IDs. Null to just check store availability.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailableInStore(Builder $query, $storeId = null): Builder
    {
        return $query->whereHas('store', function ($q) use ($storeId) {
            $q->available(true)
                ->when($storeId, fn($q) => $q->where('id', $storeId));
        });
    }

    /**
     * Scope to filter products available in specific category(ies) (with availability check)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array|null $categoryId Category ID or array of IDs. Null to just check category availability.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailableInCategory(Builder $query, $categoryId = null): Builder
    {
        return $query->whereHas('category', function ($q) use ($categoryId) {
            $q->available(true)
                ->when($categoryId, fn($q) => $q->where('id', $categoryId));
        });
    }

    /**
     * Scope to search products by name (partial match)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $search Search term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByName(Builder $query, string $search = null): Builder
    {
        return $query->when($search, fn($q) => $q->where('name', 'like', "%$search%"));
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

    /**
     * Get all of the favourites for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class, 'product_id', 'id');
    }

    /**
     * The favouriteBy that belong to the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favouriteBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourites', 'product_id', 'user_id');
    }

    /**
     * Check if product is favourited by current user
     */
    public function getIsFavouritedAttribute()
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->favourites()->where('user_id', Auth::id())->exists();
    }
}
