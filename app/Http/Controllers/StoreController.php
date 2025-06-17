<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    /**
     * The service class responsible for handling Store-related business logic.
     *
     * @var \App\Services\StoreService
     */
    protected $storeService;

    /**
     * Create a new StoreController instance and inject the StoreService.
     *
     * @param \App\Services\StoreService $storeService The service responsible for Store operations.
     */
    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
        // $this->middleware(['permission:list-stores'])->only('index');
        // $this->middleware(['permission:store-stores'])->only('store');
        // $this->middleware(['permission:show-stores'])->only('show');
        // $this->middleware(['permission:show-myStore'])->only('myStore');
        // $this->middleware(['permission:update-stores'])->only('update');
        // $this->middleware(['permission:update-myStore'])->only('updateMyStore');
        // $this->middleware(['permission:toggle-available-stores'])->only('toggleAvailable');
        // $this->middleware(['permission:delete-stores'])->only('destroy');
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
            Store::select('id', 'name', 'logo', 'is_available')
                ->available($is_available)
                ->get(),
            'Stores retrieved successfully'
        );
    }

    /**
     * Retrieve all available stores.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailable()
    {
        return $this->success(
            Store::select('id', 'name', 'logo')
                ->available(true)
                ->get(),
            'Available Stores retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStoreRequest $request)
    {
        return $this->success(
            $this->storeService->storeStore($request->validated()),
            'Store created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        return $this->success(
            $store->load(['manager:id,name', 'categories:id,name']),
            'Store retrieved successfully'
        );
    }

    public function showWithCategoriesAndProducts($id)
    {
        $store = Store::with([
            'categories' => function ($query) {
                $query->available(true)
                    ->select('categories.id', 'categories.name');
            },
            'categories.products' => function ($query) use ($id) {
                $query->available(true)
                    ->where('store_id', $id)
                    ->select('id', 'category_id', 'store_id', 'name', 'price')
                    ->with(['images' => function ($query) {
                        $query->orderBy('id', 'asc')->take(1);
                    }]);
            }
        ])
            ->available(true)
            ->findOrFail($id);

        return response()->json($store);
    }

    /**
     * Retrieve the authenticated user's store with related categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myStore()
    {
        $myStore = Auth::user()->store;
        return $this->success(
            $myStore ? $myStore->load(['categories:id,name']) : null,
            'Store retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        return $this->success(
            $this->storeService->updateStore($request->validated(), $store),
            'Store updated successfully'
        );
    }

    /**
     * Update the authenticated user's store.
     *
     * @param \App\Http\Requests\Store\UpdateStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMyStore(UpdateStoreRequest $request)
    {
        $myStore = Auth::user()->store;
        return $this->success(
            $this->storeService->updateStore($request->validated(), $myStore),
            'Store updated successfully'
        );
    }

    /**
     * Toggle the availability status of the specified resource.
     *
     * @param  \App\Models\Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailable(Store $store)
    {
        $store->update(['is_available' => !$store->is_available]);
        return $this->success($store, 'The Store has been successfully Toggled');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();
        return $this->success(null, 'Store deleted successfully', 204);
    }

    /**
     * Get store's form data for admin dashboard
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function StoreFormData(Request $request)
    {
        $storeId = $request->get('store');

        $categories = Category::available(true)->select('id', 'name')->get();

        if ($storeId) {
            $store = Store::with(['categories:id,name', 'manager:id,name'])->findOrFail($storeId);

            $admins = collect([
                [
                    'id' => $store->manager->id,
                    'name' => $store->manager->name
                ]
            ]);

            return response()->json([
                'mode' => 'edit',
                'store' => $store,
                'admins' => $admins,
                'categories' => $categories,
            ]);
        }

        $admins = User::role(['admin', 'storeManager'])
            ->whereDoesntHave('store')
            ->select('id', 'name')
            ->get()
            ->map(function ($admin) {
                if ($admin->id === Auth::id()) {
                    $admin->name = 'أنا (' . $admin->name . ')';
                }
                return $admin;
            });

        return response()->json([
            'mode' => 'create',
            'admins' => $admins,
            'categories' => $categories,
        ]);
    }
}
