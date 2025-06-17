<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendCategoryNotificationJob;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;

class CategoryController extends Controller
{
    /**
     * The service class responsible for handling Category-related business logic.
     *
     * @var \App\Services\CategoryService
     */
    protected $categoryService;

    /**
     * Create a new CategoryController instance and inject the CategoryService.
     *
     * @param \App\Services\CategoryService $categoryService
     */
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
        $this->middleware(['permission:list-categories'])->only('index');
        $this->middleware(['permission:show-categories'])->only('show');
        // $this->middleware(['permission:store-categories'])->only('store');
        $this->middleware(['permission:update-categories'])->only('update');
        $this->middleware(['permission:delete-categories'])->only('destroy');
        $this->middleware(['permission:toggle-available-categories'])->only('toggleAvailable');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $is_available = ($request->input('is_available') === null)
            ? null
            : ($request->input('is_available') == 'true' ? 1 : 0);

        return $this->success(
            Category::select('id', 'name', 'image', 'is_available')
                ->available($is_available)
                ->get(),
            'Categories retrieved successfully'
        );
    }

    /**
     * Display a listing of the current manager's categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myCategories()
    {
        $user_id = Auth::id();
        $store = Store::where('manager_id', $user_id)->first();

        return $this->success(
            Category::select('id', 'name', 'image', 'is_available')
                ->store($store ? $store->id : null)
                ->get(),
        );
    }

    /**
     * Retrieve all available categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailable()
    {
        return $this->success(
            Category::select('id', 'name', 'image', 'is_available')
                ->available(true)
                ->get(),
            'Available Sizes retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        return $this->success(
            $this->categoryService->storeCategory($request->validated()),
            'Category created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $data = $category->load('products');
        return $this->success(
            $data,
            'Category retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        return $this->success(
            $this->categoryService->updateCategory($request->validated(), $category),
            'Category updated successfully'
        );
    }

    /**
     * Toggle the availability status of the specified resource.
     *
     * @param  \App\Models\Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailable(Category $category)
    {
        $category->update(['is_available' => !$category->is_available]);
        SendCategoryNotificationJob::dispatch($category);
        return $this->success($category, 'The Category has been successfully Toggled');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return $this->success(
            null,
            'Category deleted successfully',
            204
        );
    }
}
