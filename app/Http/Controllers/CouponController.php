<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Store;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Services\CouponService;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;

class CouponController extends Controller
{
    /**
     * The service class responsible for handling Coupon-related business logic.
     *
     * @var \App\Services\CouponService
     */
    protected $couponService;

    /**
     * Create a new CouponController instance and inject the CouponService.
     *
     * @param \App\Services\CouponService $couponService The service responsible for coupon operations.
     */
    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $store = Store::where('manager_id',Auth::id())->first();
        return $this->success(
            Coupon::where('store_id', $store->id)->get(),
            'Coupons retrieved successfully',200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCouponRequest $request)
    {
        return $this->success(
            $this->couponService->storeCoupon($request->validated()),
            'Coupons created successfully',200
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon)
    {
        return $this->success(
            $coupon,
            'Coupon retrieved successfully',200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request,Coupon $coupon)
    {
        return $this->success(
            $this->couponService->updateCoupon($coupon,$request->validated()),
            'Coupons updated successfully',200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return $this->success(null,'Coupon deleted successfully',200);
    }
}
