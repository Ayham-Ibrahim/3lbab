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
            \Log::info('arrive to the job');
            $manager = $this->offer->store->manager;
            if (!$manager) {
                Log::warning("Job [{$this->job->getJobId()}] - Could not find a manager for store ID: {$this->offer->store_id}");
                return;
            }

            $fcm = new FcmService();
            $offerDescription = Str::limit($this->offer->description, 30); 

            foreach ($manager->devices as $device) {
                try {
                    $fcm->sendNotification(
                        $manager,
                        'تمت الموافقة على عرضك!',
                        "تهانينا! تمت الموافقة على عرضك '{$offerDescription}' وهو الآن متاح للعملاء.",
                        $device->fcm_token,
                        [
                            'offer_id' => (string) $this->offer->id,
                            'store_id' => (string) $this->offer->store_id,
                            'type' => 'offer_approved' 
                        ]
                    );
                } catch (\Throwable $e) {
                    $errorMessage = $e->getMessage();
                    Log::error("Job [{$this->job->getJobId()}] - Failed to send approval notification to manager {$manager->id} on device {$device->id}: " . $errorMessage);
                    if (str_contains($errorMessage, 'UNREGISTERED')) {
                        Log::info("Job [{$this->job->getJobId()}] - Deleting unregistered device for manager. Device ID: {$device->id}");
                        $device->delete();
                    }
                }
            }
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
