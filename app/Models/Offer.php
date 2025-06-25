<?php

namespace App\Models;

use App\Models\Store;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'description',
        'store_id',
        'discount_percentage',
        'image',
        'starts_at',
        'ends_at',
        'is_available',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'store_id' => 'integer',
    ];

    /**
     * the products that the offer applied to it
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Product, Offer>
     */
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * the store that own the offer
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Store, Offer>
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * toggle button for availability
     * @return void
     */
    public function toggleAvailability(): void
    {
        $this->is_available = !$this->is_available;
        $this->save();
    }

    /**
     * Get the full URL for the offer image
     *
     * @return string|null
     */
    public function getImageAttribute()
    {
        return $this->attributes['image'] ? asset($this->attributes['image']) : null;
    }
}
