<?php

namespace App\Observers;

use App\Models\Complaint;
use App\Services\FcmService;

class ComplaintObserver
{
    /**
     * Handle the Complaint "created" event.
     */
    public function created(Complaint $complaint): void
    {
        $manager = $complaint->manager;

        if ($manager && $manager->devices()->exists()) {
            $fcmService = new FcmService();

            foreach ($manager->devices as $device) {
                try {
                    $fcmService->sendNotification(
                        $manager,
                        'تم استلام شكوى جديدة',
                        'قام ' . $complaint->customer->name . ' بإرسال شكوى إليك.',
                        $device->fcm_token,
                        [
                            'type' => 'complaint',
                            'complaint_id' => (string) $complaint->id,
                            'from_user_id' => (string) $complaint->customer_id,
                        ]
                    );
                } catch (\Throwable $e) {
                    \Log::error("فشل إرسال إشعار إلى الجهاز {$device->id} للمدير {$manager->id}: {$e->getMessage()}");
                }
            }
        }
    }



}
