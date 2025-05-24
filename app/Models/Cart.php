<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     * The attributes that are appended to the model's array form (included in response).
     *
     * @var array<int, string>
     */
    protected $appends = [
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['items'];

    public function getTotalPriceAttribute(): float
    {
        return (float) DB::table('cart_items')
            ->where('cart_id', $this->id)
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->sum(DB::raw('cart_items.quantity * products.price'));
    }

    /**
     * Get the user that owns the Cart
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the items for the Cart
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id', 'id');
    }

    /**
     * calculate the finale price adter offers
     */
    public function getTotalPriceWithOfferAttribute()
    {
        $this->loadMissing('items.product.currentOffer');

        return $this->items->sum(function ($item) {
            $product = $item->product;
            $offer = $product->currentOffer->first();

            $finalPrice = $offer
                ? round($product->price - ($product->price * $offer->discount_percentage / 100), 2)
                : $product->price;

            return $finalPrice * $item->quantity;
        });
    }
}
