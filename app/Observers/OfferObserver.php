<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Offer;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendNewOfferNotificationJob;
use App\Jobs\NotifyStoreManagerOfOfferApprovalJob;

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
            $offer->load(['store.manager']);
            \Log::info("Offer ID {$offer->id} approved. Dispatching jobs.");
            // المهمة 1: إرسال إشعار جماعي للعملاء
            SendNewOfferNotificationJob::dispatch($offer);
            \Log::info('arrive to the job');
            NotifyStoreManagerOfOfferApprovalJob::dispatch($offer);
        }
    }

    
}
