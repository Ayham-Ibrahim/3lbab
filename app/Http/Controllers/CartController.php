<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreCartRequest;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * The service class responsible for handling Cart-related business logic.
     *
     * @var \App\Services\CartService
     */
    protected $cartService;

    /**
     * Create a new CartController instance and inject the CartService.
     *
     * @param \App\Services\CartService $categoryService
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function myCart()
    {
        return $this->success(
            $this->cartService->getUserCart(Auth::id())
        );
    }

    public function addToCart(StoreCartRequest $request)
    {
        return $this->success(
            $this->cartService->addItem(Auth::id(), $request->validated()),
            'Item added successfully',
            201
        );
    }

    public function destroyItem(CartItem $cartItem)
    {
        return $this->success(
            $this->cartService->removeItem(Auth::id(), $cartItem),
            'Item Deleted successfully',
            200
        );
    }
}
