<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Http\Requests\Product\StoreProductRequest;

class ProductController extends Controller
{
        /**
     * The service class responsible for handling Product-related business logic.
     *
     * @var \App\Services\ProductService
     */
    protected $productService;

    /**
     * Create a new AboutController instance and inject the ProductService.
     *
     * @param \App\Services\ProductService $productService The service responsible for Product operations.
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['store:id,name','images','category:id,name'])->get();
        return $this->success(
            $products,
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        return $this->success(
            $this->productService->storeProduct($request->validated()),
            'Product created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return $this->success(
            $product->load(['store:id,name','images','category:id,name']),
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        return $this->success(
            $this->productService->updateProduct($request->validated(), $product),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->images()->delete();
        $product->delete();
        return $this->success(null, 'Product deleted successfully', 204);
    }
}
