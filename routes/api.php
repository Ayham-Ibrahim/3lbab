<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StoreController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
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
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    Route::patch('categories/{category}/toggle-available', [CategoryController::class, 'toggleAvailable']);

    /*
    |--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->group(function () {
        Route::apiResource('/', ProductController::class)->except(['index', 'show']);

        Route::prefix('my')->group(function () {
            Route::get('/', [ProductController::class, 'myProducts']);
            Route::post('/', [ProductController::class, 'storeMyProduct']);
        });

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
