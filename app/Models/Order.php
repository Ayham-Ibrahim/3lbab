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
        'total_price',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_price' => 'float',
    ];

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
}
