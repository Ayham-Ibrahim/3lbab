<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'image',
        'is_available'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updating(function (Category $category) {
            $originalImage = $category->getOriginal('image');
            $newImage = $category->image;

            if ($originalImage && $originalImage !== $newImage) {
                $pathToDelete = str_replace(asset(''), '', $originalImage);
                Storage::disk('public')->delete($pathToDelete);
            }
        });

        static::deleted(function (Category $category) {
            if ($category->image) {
                $pathToDelete = str_replace(asset(''), '', $category->image);
                Storage::disk('public')->delete($pathToDelete);
            }
        });
    }

    /**
     * Get the full URL for the category image
     *
     * @return string|null
     */
    public function getImageAttribute()
    {
        return $this->attributes['image'] ? asset($this->attributes['image']) : null;
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
     * Scope a query to filter categories based on availability.
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
     * The stores that belong to the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'category_store');
    }

    /**
     * Get all of the products for the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
