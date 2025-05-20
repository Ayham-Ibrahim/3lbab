<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Store;
use App\Models\Coupon;
use App\Services\Service;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class OrderService extends Service {

    /**
     * list orders that belongs to specifiec store
     * @param mixed $status
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>|null
     */
    public function listOrders(?string $status = null) {

        $store = Store::where('manager_id',  Auth::id())->first();
        if (!$store) {
            $this->throwExceptionJson("No store found for this manager.");
        }
        try {
            return Order::with([
                    'store:id,name,logo'
                ])->select('id','user_id','code','total_price','coupon_id','discount_amount','status','store_id','created_at')
                ->where('store_id',$store->id)
                ->filterWithStatus($status)
                ->latest()
                ->get();
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }

    /**
     * checkout my order (for customer)
     * @param int $userId
     * @param mixed $couponCode
     * @throws \Exception
     * @return Order[]|null
     */
    public function checkout(int $userId,?string $couponCode = null)
    {
        $cart = Cart::with('items.product', 'items.productVariant')->where('user_id', $userId)->firstOrFail();
        $orders = [];
        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (!$coupon || !$coupon->isValid()) {
                // throw new \Exception('Invalid or expired coupon.');
                $this->throwExceptionJson('Invalid or expired coupon.',422);

            }
        }

        $groupedItems = $cart->items->groupBy(fn($item) => $item->product->store_id);

        DB::beginTransaction();
        try {
            foreach ($groupedItems as $storeId => $items) {
                $totalBeforeDiscount = $items->sum(fn($item) => $item->quantity * $item->product->price);
                $discount = 0;
                // add the coupon just for the store which has the coupon
                $validCouponForThisStore = $coupon && $coupon->store_id === $storeId;
                if ($coupon && !$validCouponForThisStore) {
                    $discount = 0;
                } elseif ($validCouponForThisStore) {
                    $discount = $coupon->calculateDiscount($totalBeforeDiscount);
                }

                $total = $totalBeforeDiscount - $discount;

                foreach ($items as $item) {
                    $variant = $item->productVariant;
                    if (!$variant || $variant->quantity < $item->quantity) {
                        throw new \Exception("Insufficient quantity for product: " . $item->product->name);
                    }
                }

                $order = Order::create([
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'total_price' => $total,
                    'discount_amount' => $discount,
                    'coupon_id' => $validCouponForThisStore ? $coupon->id : null,
                    'status' => 'pending',
                    'code' => $this->generateOrderCode(),
                ]);
                $orders[] = $order;
                foreach ($items as $item) {
                    $item->productVariant->decrement('quantity', $item->quantity);
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->product->price,
                    ]);
                    $item->delete();
                }

                if ($validCouponForThisStore) {
                    $coupon->incrementUsage();
                }
            }

            DB::commit();
            return $orders;
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }

    /**
     * Get all orders for a given user.
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>|null
     */
    public function getUserOrders(int $userId , ?string $status = null)
    {
        try {
            return Order::with([
                    'store:id,name,logo'
                ])->where('user_id', $userId)
                ->filterWithStatus($status)
                ->without('items')
                ->latest()
                ->get();
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }

    /**
     * Update the status of an order.
     * @param int $orderId
     * @param string $status
     * @return Order|null
     */
    public function updateOrderStatus($order, array $data)
    {
        try {
            $order->update(['status' => $data['status']]);
            return $order;
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }

    }

    /**
     * generate Order code
     * @return string
     */
    protected function generateOrderCode(): string
    {
        $datePart = now()->format('Ymd');
        $randomPart = strtoupper(Str::random(6));
        return "ORD-{$datePart}-{$randomPart}";
    }



}
