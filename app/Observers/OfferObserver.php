<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Offer;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendNewOfferNotificationJob;

class OfferObserver
{
    /**
     * Handle the Offer "created" event.
     */
    public function created(Offer $offer): void
    {
        $admins = User::role('admin')->with('devices')->get();
        $storeName = optional($offer->store)?->name ?? '';

        $fcm = new FcmService();

        foreach ($admins as $admin) {
            foreach ($admin->devices as $device) {
                try {
                    $fcm->sendNotification(
                        $admin,
                        'عرض جديد بانتظار النشر',
                        "تم إضافة عرض جديد من متجر {$storeName}. الرجاء مراجعته.",
                        $device->fcm_token,
                        [
                            'offer_id' => (string) $offer->id,
                            'store_id' => (string) $offer->store_id,
                        ]
                    );
                } catch (\Throwable $e) {
                    \Log::error("فشل إرسال إشعار إلى الأدمن {$admin->id} على الجهاز {$device->id}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Handle the Offer "updated" event.
     */
    public function updated(Offer $offer): void
    {
        if ($offer->isDirty('is_available') && $offer->is_available) {
            SendNewOfferNotificationJob::dispatch($offer->load('store'));
        }
    }

    /**
     * Handle the Offer "deleted" event.
     */
    public function deleted(Offer $offer): void
    {
        //
    }

    /**
     * Handle the Offer "restored" event.
     */
    public function restored(Offer $offer): void
    {
        //
    }

    /**
     * Handle the Offer "force deleted" event.
     */
    public function forceDeleted(Offer $offer): void
    {
        //
    }
}
