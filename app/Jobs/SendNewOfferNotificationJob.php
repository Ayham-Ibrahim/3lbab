<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Offer;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class SendNewOfferNotificationJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $offer;

    /**
     * Create a new job instance.
     */
    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $storeName = optional($this->offer->store)->name;
            $fcm = new FcmService();

            User::role('customer')->whereNotNull('fcm_token')->chunk(100, function ($users) use ($fcm, $storeName) {
                foreach ($users as $user) {
                    $fcm->sendNotification(
                        $user,
                        'عرض جديد!',
                        "تم إصدار عرض جديد من متجر {$storeName}",
                        $user->fcm_token,
                        [
                            'offer_id' => (string) $this->offer->id,
                            'store_id' => (string) $this->offer->store_id,
                        ]
                    );
                }
            });
        } catch (\Throwable $e) {
            \Log::error("SendNewOfferNotificationJob failed: " . $e->getMessage());
        }
    }
}
