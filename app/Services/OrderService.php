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
        $user = Auth::user();
        try {
            if ($user->hasRole('super admin') || $user->hasRole('admin')) {

                    $orders = Order::with([
                        'store:id,name,logo',
                        'items.product.currentOffer',

                    ])
                    ->filterWithStatus($status)
                    ->latest()
                    ->get();
                    return $orders;
            }
            $store = Store::where('manager_id',  Auth::id())->first();
            if (!$store) {
                $this->throwExceptionJson("No store found for this manager.");
            }
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
        $cart = Cart::with('items.product','items.product.currentOffer', 'items.productVariant')->where('user_id', $userId)->firstOrFail();
        $orders = [];
        $coupon = null;

        // 1. التحقق من الكوبون إن وُجد
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (!$coupon || !$coupon->isValid()) {
                $this->throwExceptionJson('Invalid or expired coupon.', 422);
            }
        }

        // 2. تجميع العناصر حسب المتجر
        $groupedItems = $cart->items->groupBy(fn($item) => $item->product->store_id);

        // 3. إجمالي السلة قبل الكوبون (مع الأخذ بالاعتبار الخصومات على مستوى المنتج مسبقًا)
        $cartTotalBeforeDiscount = $cart->items->sum(fn($item) => $item->final_price * $item->quantity);

        // 4. حساب الخصم من الكوبون إن وُجد
        $totalDiscount = $coupon ? $coupon->calculateDiscount($cartTotalBeforeDiscount) : 0;

        DB::beginTransaction();
        try {
            foreach ($groupedItems as $storeId => $items) {
                // 5. إجمالي هذا المتجر قبل الكوبون
                $storeTotalBeforeCoupon = $items->sum(fn($item) => $item->final_price * $item->quantity);

                // 6. تحديد نسبة خصم الكوبون لهذا المتجر
                $storeDiscount = $cartTotalBeforeDiscount > 0
                    ? round(($storeTotalBeforeCoupon / $cartTotalBeforeDiscount) * $totalDiscount, 2)
                    : 0;

                $total = $storeTotalBeforeCoupon - $storeDiscount;

                // 7. تحقق من الكمية
                foreach ($items as $item) {
                    $variant = $item->productVariant;
                    if (!$variant || $variant->quantity < $item->quantity) {
                        throw new \Exception("Insufficient quantity for product: " . $item->product->name);
                    }
                }

                // 8. إنشاء الطلب
                $order = Order::create([
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'total_price' => $total,
                    'discount_amount' => $storeDiscount,
                    'coupon_id' => $coupon?->id,
                    'status' => 'pending',
                    'code' => $this->generateOrderCode(),
                ]);
                $orders[] = $order;

                // 9. إنشاء عناصر الطلب وتحديث الكمية
                foreach ($items as $item) {
                    $item->productVariant->decrement('quantity', $item->quantity);

                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->final_price,
                    ]);

                    $item->delete();
                }
            }

            // 10. تحديث عدد استخدامات الكوبون
            if ($coupon) {
                $coupon->incrementUsage();
            }

            DB::commit();

            // 11. إرسال إشعار للمستخدم
            $user = User::with('devices')->find($userId);

            if ($user && $user->devices->isNotEmpty()) {
                $fcmService = new FcmService();
                foreach ($user->devices as $device) {
                    try {
                        $fcmService->sendNotification(
                            $user,
                            'تم إنشاء طلب جديد',
                            'شكراً لتسوقك معنا، تم تقديم طلبك بنجاح! يمكنك متابعة الطلب من قائمة الطلبات.',
                            $device->fcm_token,
                            [
                                'order_count' => (string) count($orders),
                                'status' => 'pending'
                            ]
                        );
                    } catch (\Throwable $e) {
                        \Log::error("فشل إرسال إشعار إلى الجهاز {$device->id} للمستخدم {$user->id}: {$e->getMessage()}");
                    }
                }
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
            $user = User::with('devices')->find($order->user_id);

            if ($user && $user->devices->isNotEmpty()) {
                $fcmService = new FcmService();
                foreach ($user->devices as $device) {
                    try {
                        $success = $fcmService->sendNotification(
                            $user,
                            'تم تحديث حالة الطلب',
                            'طلبك رقم ' . $order->code . ' تم تغييره إلى ' . $order->status,
                            $device->fcm_token,
                            [
                                'order_id' => $order->id,
                                'status' => $order->status
                            ]
                        );

                        if ($success) {
                            Log::info("تم إرسال الإشعار إلى الجهاز {$device->id} للمستخدم {$user->id}");
                        } else {
                            Log::warning("فشل إرسال الإشعار إلى الجهاز {$device->id} للمستخدم {$user->id}");
                        }
                    } catch (\Throwable $e) {
                        Log::error("حدث خطأ أثناء إرسال إشعار إلى الجهاز {$device->id} للمستخدم {$user->id}: {$e->getMessage()}");
                    }
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
