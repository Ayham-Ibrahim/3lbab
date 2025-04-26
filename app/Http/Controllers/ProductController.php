<?php

namespace App\Http\Controllers;

use App\Models\Size;
use App\Models\User;
use App\Models\Color;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\StoreMyProductRequest;

class ProductController extends Controller
{
    /**
     * The service class responsible for handling Product-related business logic.
     *
     * @var \App\Services\ProductService
     */
    protected $productService;

    /**
     * Create a new ProductController instance and inject the ProductService.
     *
     * @param \App\Services\ProductService $productService The service responsible for Product operations.
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
        $this->middleware(['permission:list-products'])->only('index');
        $this->middleware(['permission:list-my-products'])->only('myProducts');
        $this->middleware(['permission:store-products'])->only('store');
        $this->middleware(['permission:store-my-products'])->only('storeMyProduct');
        $this->middleware(['permission:show-products'])->only('show');
        $this->middleware(['permission:update-products'])->only('update');
        $this->middleware(['permission:delete-products'])->only('destroy');
        $this->middleware(['permission:delete-product-images'])->only('deleteImage');
        $this->middleware(['permission:delete-product-variants'])->only('deleteVariant');
        $this->middleware(['permission:manage-products'])->only('getProductFormData');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $is_available = $request->input('is_available') == 'true' ? 1 : 0;

        return $this->paginate(
            Product::with(['store:id,name', 'images', 'category:id,name'])
                ->available($is_available)
                ->store(($request->input('store')))
                ->category(($request->input('category')))
                ->paginate(),
            'Products retrieved successfully'
        );
    }

    /**
     * Display a listing of the current manager's products
     *
     * @param Request $request The HTTP request containing filter parameters
     * @return \Illuminate\Http\JsonResponse
     */
    public function myProducts(Request $request)
    {
        $is_available = $request->input('is_available') == 'true' ? 1 : 0;

        return $this->paginate(
            Product::with(['store:id,name', 'images', 'category:id,name'])
                ->whereHas('store', function ($q) {
                    $q->where('manager_id', Auth::id());
                })
                ->available($is_available)
                ->store(($request->input('store')))
                ->category(($request->input('category')))
                ->paginate(),
            'Products retrieved successfully'
        );
    }

    /**
     * Retrieve all available sizes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailable(Request $request)
    {
        return $this->paginate(
            Product::with(['images'])
                ->select('id', 'name', 'price')
                ->available(true)
                ->store(($request->input('store')))
                ->category(($request->input('category')))
                ->paginate(),
            'Available Sizes retrieved successfully'
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
     * Store a new product for the current manager's store
     *
     * @param StoreMyProductRequest $request The validated HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeMyProduct(StoreMyProductRequest $request)
    {
        $data = $request->validated();
        $user_id = Auth::id();
        $store = Store::where('manager_id',$user_id)->first();
        $data['store_id'] = $store->id;

        return $this->success(
            $this->productService->storeProduct($data),
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
            $product->load(['store:id,name', 'images', 'category:id,name']),
            'Product retrieved successfully'
        );
    }

    /**
     * Display the specified product with full details including variants
     *
     * @param int $id The product ID
     * @return ProductResource
     */
    public function showWithDetails($id)
    {
        $product = Product::with([
            'category:id,name',
            'store:id,name',
            'images:id,product_id,image',
            'variants' => function ($query) {
                $query->with([
                    'color' => function ($q) {
                        $q->available(true)->select('id', 'name', 'hex_code');
                    },
                    'size' => function ($q) {
                        $q->available(true)->select('id', 'size_code');
                    }
                ]);
            }
        ])
            ->available(true)
            ->findOrFail($id);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
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
        $product->variants()->delete();
        $product->delete();
        return $this->success(null, 'Product deleted successfully', 200);
    }

    /**
     * Delete a product image
     *
     * @param ProductImage $image The product image model instance
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(ProductImage $image)
    {
        $image->delete();
        return $this->success(null, 'Product Image deleted successfully', 200);
    }

    /**
     * Delete a product variant
     *
     * @param ProductVariant $variant The product variant model instance
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariant(ProductVariant $variant)
    {
        $variant->delete();
        return $this->success(null, 'Product Variant deleted successfully', 200);
    }

    /**
     * get product's form data for admin dashboard
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getProductFormData(Request $request)
    {
        $productId = $request->get('product_id');

        $colors = Color::available(true)->select('id', 'name','hex_code')->get();
        $sizes = Size::available(true)->select('id', 'type','size_code')->get();
        $stores = Store::available(true)->select('id', 'name','logo')->get();
        $categories = Category::available(true)->select('id', 'name','image')->get();

        if ($productId) {
            $product = Product::with(['images', 'variants.color', 'variants.size', 'store', 'category'])
                ->findOrFail($productId);

            return response()->json([
                'mode' => 'edit',
                'product' => $product,
                'colors' => $colors,
                'sizes' => $sizes,
                'stores' => $stores,
                'categories' => $categories,
            ]);
        }

        return response()->json([
            'mode' => 'create',
            'colors' => $colors,
            'sizes' => $sizes,
            'stores' => $stores,
            'categories' => $categories,
        ]);
    }
}
