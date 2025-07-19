<?php

namespace App\Observers;

use App\Models\Store;
use App\Jobs\SendBroadcastNotificationJob;

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
        //
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
