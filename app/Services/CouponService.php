<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;

class CouponService extends Service
{
    /**
     * store new coupon
     * @param mixed $data
     * @return Coupon|null
     */
    public function storeCoupon($data)
    {
        try {
            return Coupon::create([
                'code'                  => $data['code'],
                'discount_percentage'   => $data['discount_percentage'],
                'max_uses'              => $data['max_uses'],
                'expires_at'            => $data['expires_at'],
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }

    /**
     * update a coupon
     * @param mixed $data
     * @throws \Exception
     * @return Coupon|null 
     */
    public function updateCoupon(Coupon $coupon,array $data)
    {
        try {
            $coupon->update(array_filter([
                'code'                  => $data['code'] ?? $coupon->code,
                'discount_percentage'   => $data['discount_percentage'] ?? $coupon->discount_percentage,
                'max_uses'              => $data['max_uses'] ?? $coupon->max_uses,
                'expires_at'            => $data['expires_at'] ?? $coupon->expires_at,
            ]));
            return $coupon;
        } catch (\Throwable $th) {
            Log::error($th);
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }
}