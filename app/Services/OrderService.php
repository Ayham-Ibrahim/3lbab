<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use App\Models\Order;
use App\Models\Store;
use App\Models\Coupon;
use App\Services\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;


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
            $user = User::where('id', $userId)->first();

            if ($user  && $user->fcm_token) {
                $fcmService = new FcmService();
                $fcmService->sendNotification(
                    $user,
                    'تم إنشاء طلب جديد',
                    'شكراً لتسوقك معنا، تم تقديم طلبك بنجاح!',
                    $user->fcm_token,
                    [
                        'order_count' => (string) count($orders),
                        'status' => 'pending'
                    ]
                );
            } else {
                Log::warning("User ID {$user->id} has no FCM token.");
            }
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
                $orders = Order::with([
                    'store:id,name,logo',
                    'items.product.currentOffer',

                ])->where('user_id', $userId)
                ->filterWithStatus($status)
                ->latest()
                ->get();
                $orders->each->makeHidden('items');
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
     * Update the status of an order.
     * @param int $orderId
     * @param string $status
     * @return Order|null
     */
    public function updateOrderStatus($order, array $data)
    {
        try {
            $order->update(['status' => $data['status']]);
            $user = User::where('id', $order->user_id)->first();

            if ($user  && $user->fcm_token) {
                $fcmService = new FcmService();
                $success = $fcmService->sendNotification($user,
                'تم تحديث حالة الطلب',
                'طلبك رقم ' . $order->code . ' تم تغييره إلى ' . $order->status,
                $user->fcm_token,
                [
                    'order_id' => $order->id,
                    'status' => $order->status
                ]);
                if ($success) {
                    Log::info("تم إرسال الإشعار إلى المستخدم {$user->id}");
                } else {
                    Log::warning("فشل إرسال الإشعار إلى المستخدم {$user->id}");
                }
            }
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
