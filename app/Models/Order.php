<?php

namespace App\Models;

use App\Models\User;
use App\Models\Store;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'store_id',
        'code',
        'total_price',
        'status',
        'discount_amount',
        'coupon_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_price' => 'float',
        'discount_amount' => 'float',
        'user_id' => 'integer',
        'store_id' => 'integer',
        'coupon_id' => 'integer',
    ];

    /**
     * Summary of appends
     * @var array
     */
    protected $appends = ['total_price_with_offer', 'the_final_price'];


    /**
     * Get the user that owns the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Order>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all the items in the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<OrderItem, Order>
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * the store that has the order
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Store, Coupon>
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope a query to filter orders by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterWithStatus($query,?string $status){
        return $query->when($status, fn($q) => $q->where('status',$status));
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

    /**
     * get The Final Price Attribute
     * @return float|int
     */
    public function getTheFinalPriceAttribute()
    {
        return max(0, $this->total_price_with_offer - $this->discount_amount);
    }
}
