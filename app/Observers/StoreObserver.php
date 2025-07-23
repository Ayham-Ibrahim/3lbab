<?php

namespace App\Observers;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendBroadcastNotificationJob;
use App\Jobs\NotifyManagerOfStoreStatusJob;

class StoreObserver
{
    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store): void
    {
        SendBroadcastNotificationJob::dispatch(
            'متجر جديد متاح الآن!',
            "تم إضافة المتجر: {$store->name} إلى التطبيق",
            [
                'type' => 'new_store',
                'store_id' => $store->id,
            ]
        );
    }

    /**
     * Handle the Store "updated" event.
     */
    public function updated(Store $store): void
    {
        if ($store->isDirty('is_available')) {
            $store->load('manager');

            $status = $store->is_available ? 'available' : 'unavailable';
            Log::info("Store ID {$store->id} status changed to {$status}. Dispatching notification job.");
            
            NotifyManagerOfStoreStatusJob::dispatch($store, $store->is_available);
        }
    }

    /**
     * Handle the Store "deleted" event.
     */
    public function deleted(Store $store): void
    {
        //
    }

    /**
     * Handle the Store "restored" event.
     */
    public function restored(Store $store): void
    {
        //
    }

    /**
     * Handle the Store "force deleted" event.
     */
    public function forceDeleted(Store $store): void
    {
        //
    }
}
