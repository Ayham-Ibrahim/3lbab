<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class OrderService extends Service {

    public function checkout(int $userId,?string $couponCode = null)
    {
        $cart = Cart::with('items.product', 'items.productVariant')->where('user_id', $userId)->firstOrFail();

        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (!$coupon || !$coupon->isValid()) {
                throw new \Exception('Invalid or expired coupon.');
            }
        }

        $groupedItems = $cart->items->groupBy(fn($item) => $item->product->store_id);

        DB::beginTransaction();
        try {
            foreach ($groupedItems as $storeId => $items) {
                $totalBeforeDiscount = $items->sum(fn($item) => $item->quantity * $item->product->price);
                $discount = 0;

                if ($coupon && $coupon->store_id !== $storeId) {
                    throw new \Exception("Coupon not valid for this store.");
                }

                if ($coupon) {
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
                    'coupon_id' => $coupon?->id,
                    'status' => 'pending',
                ]);

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

                if ($coupon) {
                    $coupon->incrementUsage();
                }
            }

            DB::commit();
            return true;
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
    public function getUserOrders(int $userId)
    {
        try {
            return Order::with('items.product', 'items.productVariant')
                ->where('user_id', $userId)
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
    public function updateOrderStatus(int $orderId, string $data)
    {
        try {
            $order = Order::findOrFail($orderId);
            $order->update(['status' => $data['status']]);
    
            return $order->fresh(['items.product', 'items.productVariant']);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
 
    }




}