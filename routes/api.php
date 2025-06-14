<?php

use App\Models\User;
use App\Mail\SendResetCode;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogController;
use App\Http\Controllers\UserContoller;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PopupController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;

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
| Forget Password Routes
|--------------------------------------------------------------------------
*/
Route::post('forgot-password', [ForgetPasswordController::class, 'forgotPassword']);
Route::post('reset-password', [ForgetPasswordController::class, 'verifyResetCode']);

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
| Email Verification OTP Routes
|--------------------------------------------------------------------------
*/
Route::prefix('email')->group(function () {
    Route::post('send-verification-otp', [EmailVerificationController::class, 'sendOtp']);
    Route::post('verify-otp', [EmailVerificationController::class, 'verifyOtp']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Update FCM Token Route
    |--------------------------------------------------------------------------
    */
    Route::patch('fcm-token', [AuthController::class, 'updateFcmToken']);

    /*
    |--------------------------------------------------------------------------
    | Send Notification
    |--------------------------------------------------------------------------
    */
    Route::post('send-notification', [NotificationController::class, 'sendAdminBroadcastNotification']);

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
    Route::get('store/form-data', [StoreController::class, 'StoreFormData']);

    /*
    |--------------------------------------------------------------------------
    | Category Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('/categories', CategoryController::class)->except(['index', 'show']);
    Route::patch('categories/{category}/toggle-available', [CategoryController::class, 'toggleAvailable']);
    Route::get('categories/my', [CategoryController::class, 'myCategories']);

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
        Route::delete('images/{image}', [ProductController::class, 'deleteImage']);
        Route::delete('variants/{variant}', [ProductController::class, 'deleteVariant']);
        Route::post('{product}/favourite', [ProductController::class, 'toggleFavourite']);
    });
    Route::get('product/form-data', [ProductController::class, 'getProductFormData']);
    Route::get('/favourites', [ProductController::class, 'getFavourites']);

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('profile')->group(function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/reset-password', [ProfileController::class, 'resetPassword']);
    });

    /*
    |--------------------------------------------------------------------------
    | Cart Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'myCart']);
        Route::post('/items', [CartController::class, 'addToCart']);
        Route::delete('/{cart_item}/items', [CartController::class, 'destroyItem']);
    });

    /*
    |--------------------------------------------------------------------------
    | Complation Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('complation')->group(function () {
        Route::post('/', [ComplaintController::class, 'store']);
        Route::get('/admins', [ComplaintController::class, 'getAdmins']);
        Route::get('/', [ComplaintController::class, 'index']);
        Route::get('/my', [ComplaintController::class, 'managerComplaints']);
        Route::patch('/{id}/is-readed', [ComplaintController::class, 'markAsRead']);
        Route::delete('/', [ComplaintController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Coupon Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('coupon')->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::post('/', [CouponController::class, 'store']);
        Route::put('/{coupon}/update', [CouponController::class, 'update']);
        Route::get('/{coupon}', [CouponController::class, 'show']);
        Route::delete('/{coupon}', [CouponController::class, 'destroy']);
    });


    /*
    |--------------------------------------------------------------------------
    | Order Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::prefix('orders')->group(function () {
        Route::get('/my', [OrderController::class, 'myOrders']);
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::put('/{order}', [OrderController::class, 'updateStatus']);
        Route::delete('/{order}', [OrderController::class, 'destroy']);
    });


    /*
    |--------------------------------------------------------------------------
    | User Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('/users', UserContoller::class);
    Route::patch('users/{user}/toggle-available', [UserContoller::class, 'toggleAvailable']);

    /*
    |--------------------------------------------------------------------------
    | Offer Routes
    |--------------------------------------------------------------------------
    */
    Route::get('offers/get-form-data', [OfferController::class, 'getOfferFormData']);
    Route::apiResource('offers', OfferController::class);

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
// show all offers
Route::get('all-offers', [OfferController::class, 'allOffers']);


/*
|--------------------------------------------------------------------------
| Log Routes
|--------------------------------------------------------------------------
*/
Route::delete('/reset-log', [LogController::class, 'resetLog']);
Route::get('/logs', [LogController::class, 'getLog']);


/*
|--------------------------------------------------------------------------
| Popup Routes
|--------------------------------------------------------------------------
*/
Route::post('popup', [PopupController::class, 'handle'])->middleware('auth:sanctum');
Route::get('popup', [PopupController::class, 'getInfo']);
