<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

class CartService extends Service // Assuming Service class exists
{
    /**
     * Create a new CartService instance.
     */
    public function __construct()
    {
        // Constructor logic can be added here if needed
    }

    /**
     * Get the user's cart or create it if it doesn't exist.
     * Eager loads necessary item relationships (product images, variant color/size) for efficient display.
     *
     * @param int $userId The ID of the user.
     * @return Cart The user's Cart object with items loaded.
     */
    public function getUserCart(int $userId): Cart
    {
        $cart = Cart::with([
        'items.product.images',
        'items.product.currentOffer',
        'items.productVariant.color',
        'items.productVariant.size'
    ])->firstOrCreate(['user_id' => $userId]);

    // Apply offer pricing
    $cart->items->transform(function ($item) {
        $product = $item->product;
        $offer = $product->currentOffer->first(); // eager loaded

        $finalPrice = $offer
            ? round($product->price - ($product->price * $offer->discount_percentage / 100), 2)
            : $product->price;

        $item->final_price = $finalPrice;
        $item->total_price = $finalPrice * $item->quantity;

        return $item;
    });

    return $cart;
    }

    /**
     * Add a new item to the user's cart or update the quantity of an existing item.
     * Operates within a database transaction to ensure atomicity.
     * Handles potential errors and logs them, rolling back the transaction on failure.
     *
     * @param int   $userId The ID of the user whose cart will be modified.
     * @param array $data   An array containing the item data:
     *                      - `product_id` (int): The ID of the product.
     *                      - `quantity` (int): The quantity to add.
     *                      - `product_variant_id` (int): The ID of the product variant.
     * @return Cart|null The updated Cart object with items loaded, or null on failure before throwing exception.
     */
    public function addItem(int $userId, array $data)
    {
        try {
            $cart = $this->getUserCart($userId);

            DB::beginTransaction();
            $cartItem = $cart->items()
                ->where('product_id', $data['product_id'])
                ->where('product_variant_id', $data['product_variant_id'])
                ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $data['quantity'];
                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                $cart->items()->create([
                    'product_id' => $data['product_id'],
                    'product_variant_id' => $data['product_variant_id'],
                    'quantity' => $data['quantity'],
                ]);
            }
            DB::commit();
            $user = User::with('devices')->find($userId);

            if ($user && $user->devices->isNotEmpty()) {
                $fcmService = new FcmService();
                foreach ($user->devices as $device) {
                    try {
                        $fcmService->sendNotification(
                            $user,
                            'تمت إضافة منتج للسلة',
                            'تمت إضافة منتج جديد إلى سلتك بنجاح. يمكنك مراجعة قسم المشتريات.',
                            $device->fcm_token,
                            [
                                'type' => 'cart',
                                'cart_items_count' => $cart->items()->count(),
                            ]
                        );
                    } catch (\Throwable $e) {
                        \Log::error("فشل إرسال إشعار إلى الجهاز {$device->id} للمستخدم {$user->id}: {$e->getMessage()}");
                    }
                }
            }
            return $cart->load([
                'items.product.images',
                'items.productVariant.color',
                'items.productVariant.size'
            ]);
        } catch (\Throwable $th) {
            Log::error("Error adding item to cart: " . $th->getMessage(), ['exception' => $th, 'user_id' => $userId, 'data' => $data]); // Log context
            DB::rollBack();
            $this->throwExceptionJson();
        }
    }

    /**
     * Remove a specific item from the user's cart.
     * Verifies that the item belongs to the specified user's cart before deletion.
     *
     * @param int       $userId   The ID of the user who owns the cart.
     * @param CartItem  $cartItem The CartItem instance to be removed (typically injected via Route Model Binding).
     * @return Cart The updated Cart object after item removal, with items loaded.
     * @throws AuthorizationException If the item does not belong to the user's cart.
     */
    public function removeItem(int $userId, CartItem $cartItem): Cart
    {
        $userCartId = Cart::where('user_id', $userId)->value('id');

        if (!$userCartId || $cartItem->cart_id !== $userCartId) {
            throw new AuthorizationException('هذا العنصر لا ينتمي لسلة التسوق الخاصة بك.');
        }

        $cartItem->delete();

        return $this->getUserCart($userId);
    }

}
