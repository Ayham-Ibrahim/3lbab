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
        $storeManager = Auth::id();
        $store = Store::where('manager_id', $storeManager)->first();
        if (!$store) {
            throw new \Exception("No store found for this manager.");
        }
        try {
            return Coupon::create([
                'store_id'              => $store->id,
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