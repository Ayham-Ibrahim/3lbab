<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
    Route::middleware('auth:sanctum')->get('admins', [UserController::class, 'getAdmins']);
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('available')->group(function () {
    Route::get('colors', [ColorController::class, 'getAvailable']);
    Route::get('sizes', [SizeController::class, 'getAvailable']);
    Route::get('stores', [StoreController::class, 'getAvailable']);
    Route::get('categories', [CategoryController::class, 'getAvailable']);
    Route::get('products', [ProductController::class, 'getAvailable']);
});

Route::get('stores/{store}/with-categories-products', [StoreController::class, 'showWithCategoriesAndProducts']);
Route::get('products/{product}/details', [ProductController::class, 'showWithDetails']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Color Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('colors', ColorController::class)->except(['index', 'show']);
    Route::patch('colors/{color}/toggle-available', [ColorController::class, 'toggleAvailable']);

    /*
    |--------------------------------------------------------------------------
    | Size Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('sizes', SizeController::class)->except(['index', 'show']);
    Route::patch('sizes/{size}/toggle-available', [SizeController::class, 'toggleAvailable']);

    /*
    |--------------------------------------------------------------------------
    | Store Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('stores', StoreController::class)->except(['index', 'show']);
    Route::patch('stores/{store}/toggle-available', [StoreController::class, 'toggleAvailable']);
    Route::prefix('my')->group(function () {
        Route::get('store', [StoreController::class, 'myStore']);
        Route::put('store', [StoreController::class, 'updateMyStore']);
    });

    /*
    |--------------------------------------------------------------------------
    | Category Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('categories')->group(function () {
        Route::apiResource('/', CategoryController::class)->except(['index', 'show']);
        Route::patch('/{category}/toggle-available', [CategoryController::class, 'toggleAvailable']);
        Route::get('/my', [CategoryController::class, 'myCategories']);
    });

    /*
    |--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    Route::prefix('products')->group(function () {
        Route::prefix('my')->group(function () {
            Route::get('/', [ProductController::class, 'myProducts']);
            Route::post('/', [ProductController::class, 'storeMyProduct']);
        });
        Route::get('form-data', [ProductController::class, 'getProductFormData']);
        Route::delete('images/{image}', [ProductController::class, 'deleteImage']);
        Route::delete('variants/{variant}', [ProductController::class, 'deleteVariant']);
    });
});

/*
|--------------------------------------------------------------------------
| Public Read-only Routes
|--------------------------------------------------------------------------
*/
Route::apiResource('colors', ColorController::class)->only(['index', 'show']);
Route::apiResource('sizes', SizeController::class)->only(['index', 'show']);
Route::apiResource('stores', StoreController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);



//LOG
Route::delete('/reset-log', [LogController::class, 'resetLog']);
Route::get('/logs', [LogController::class, 'getLog']);

