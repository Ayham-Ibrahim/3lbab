<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Order\UpdateStatusRequest;

class OrderController extends Controller
{
    /**
     * The service class responsible for handling order-related business logic.
     *
     * @var \App\Services\OrderService
     */
    protected $orderService;

    /**
     * Create a new OrderController instance and inject the OrderService.
     *
     * @param \App\Services\OrderService $orderService The service responsible for order operations.
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->success(
            $this->orderService->listOrders($request->query('status')),
        'Orders retrieved successfully',200);
    }

    public function checkout(Request $request)
    {
        return $this->success(
            $this->orderService->checkout(Auth::id(), $request->get('coupon')),
            'Order created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load([
            'items.product.images',
            'items.product.currentOffer',
            'items.productVariant' => fn($q) => $q->withoutGlobalScopes(),,
            'items.productVariant.color',
            'items.productVariant.size',
            'user',
            'store',
        ]);
        $order->items->transform(function ($item) {
        $product = $item->product;
        $offer = $product->currentOffer->first();

        $finalPrice = $offer
            ? round($product->price - ($product->price * $offer->discount_percentage / 100), 2)
            : $product->price;

        $item->final_price = $finalPrice;
        $item->total_price = $finalPrice * $item->quantity;

        return $item;
    });

        return $this->success($order,'Order retrieved successfully',200);
    }

   /**
     * Update the status of a specific order.
     */
    public function updateStatus(UpdateStatusRequest $request,Order $order)
    {
        return $this->success(
            $this->orderService->updateOrderStatus($order, $request->validated()),
            'Order status updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return $this->success(null,'Order deleted successfully',200);
    }

    /**
     * Get all orders for the authenticated user.(for auth user)
     */
    public function myOrders(Request $request)
    {
        return $this->success(
            $this->orderService->getUserOrders(Auth::id(),$request->query('status'))
        );
    }
}
