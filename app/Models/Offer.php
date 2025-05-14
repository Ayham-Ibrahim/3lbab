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
        'description','store_id','discount_percentage','image','starts_at','ends_at',
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
}
