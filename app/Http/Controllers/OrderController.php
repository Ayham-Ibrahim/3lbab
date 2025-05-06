<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;

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
    public function index()
    {
        //
    }

    public function checkout()
    {
        try {
            $order = $this->orderService->checkout(Auth::id());

            return response()->json([
                'message' => 'Order created successfully.',
                'data' => $order->load('items.product', 'items.productVariant')
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Checkout failed.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

   /**
     * Update the status of a specific order.
     */
    public function updateStatus(Request $request, int $orderId)
    {
        return $this->success(
            $this->orderService->updateOrderStatus($orderId, $request->validated()),
            'Order status updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    /**
     * Get all orders for the authenticated user.
     */
    public function myOrders()
    {
        return $this->success(
            $this->orderService->getUserOrders(Auth::id())
        );
    }
}
