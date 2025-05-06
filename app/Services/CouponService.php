<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Facades\Log;
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
}