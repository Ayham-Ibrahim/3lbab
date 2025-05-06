<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code', 'discount_percentage', 'max_uses', 'used_count', 'expires_at'
    ];

    /**
     * Determine if the coupon is still valid for use.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->used_count < $this->max_uses &&
            // check if now is less than expires_at
            (!$this->expires_at || now()->toDateString() <= $this->expires_at);
        }

    /**
     * Increment the usage count for the coupon.
     *
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Calculate the discount amount based on the total.
     *
     * @param float $total The total amount before discount.
     * @return float The calculated discount amount.
     */
    public function calculateDiscount(float $total): float
    {
        return round($total * ($this->discount_percentage / 100), 2);
    }
}
