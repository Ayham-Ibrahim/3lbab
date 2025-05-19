<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = [];

        if ($user->hasRole('admin')) {

            $stats['stores_total'] = Store::count();
            $stats['stores_available'] = Store::available(true)->count();
            $stats['stores_unavailable'] = Store::available(false)->count();

            $stats['categories_total'] = Category::count();
            $stats['categories_available'] = Category::available(true)->count();
            $stats['categories_unavailable'] = Category::available(false)->count();

            $stats['users_total'] = User::count();
            $stats['users_active'] = User::available(true)->count();
            $stats['users_inactive'] = User::available(false)->count();

            $stats['complaints_total_system'] = Complaint::count();
            $stats['complaints_for_me'] = $user->complaints()->count();
        } elseif ($user->hasRole('storeManager')) {
            $store = $user->store;

            if ($store) {
                $stats['my_store_products_total'] = $store->products()->count();
                $stats['my_store_products_active'] = $store->products()->available(true)->count();
                $stats['my_store_products_inactive'] = $store->products()->available(false)->count();

                $stats['my_store_orders_total'] = $store->orders()->count();

                $acceptedOrderStatuses = [
                    OrderStatus::Processing->value,
                    OrderStatus::Shipped->value,
                    OrderStatus::Completed->value,
                ];
                $stats['my_store_orders_accepted'] = $store->orders()
                    ->whereIn('status', $acceptedOrderStatuses)
                    ->count();

                $stats['my_store_orders_rejected'] = $store->orders()
                    ->filterWithStatus(OrderStatus::Cancelled->value)
                    ->count();

                $stats['my_complaints_total'] = $user->complaints()->count();
                $stats['my_complaints_read'] = $user->complaints()->readStatus(true)->count();
                $stats['my_complaints_unread'] = $user->complaints()->readStatus(false)->count();
            } else {
                $stats['my_store_products_total'] = 0;
                $stats['my_store_products_active'] = 0;
                $stats['my_store_products_inactive'] = 0;
                $stats['my_store_orders_total'] = 0;
                $stats['my_store_orders_accepted'] = 0;
                $stats['my_store_orders_rejected'] = 0;
                $stats['my_complaints_total'] = 0;
                $stats['my_complaints_read'] = 0;
                $stats['my_complaints_unread'] = 0;
            }
        }
        return $this->success($stats);
    }
}
