<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
    ];

    /**
     * Get the order that owns the item.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Order, OrderItem>
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, OrderItem>
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ProductVariant, OrderItem>
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
