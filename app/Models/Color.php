<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'hex_code',
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
     * Get the is_available attribute correctly casted
     *
     * @param  mixed  $value
     * @return bool
     */
    public function getIsAvailableAttribute($value)
    {
        return (bool) $value;
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
     * Scope a query to filter colors based on availability.
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
     * Check if the color is associated with any product variants.
     * @return bool
     */
    public function isAssociatedWithVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Get all of the variants for the Color
     * @return Builder<ProductVariant>
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'color_id', 'id')->active();
    }
}
