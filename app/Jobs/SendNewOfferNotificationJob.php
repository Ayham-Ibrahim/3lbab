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
        \Log::info("Job [{$this->job->getJobId()}] is starting. Processing offer ID: {$this->offer->id}");

    try {
        $storeName = optional($this->offer->store)->name;
        $fcm = new FcmService();
        $totalUsers = User::role('customer')->count();

        \Log::info("Job [{$this->job->getJobId()}]: Found {$totalUsers} customers to notify.");

        User::role('customer')->with('devices')->chunk(100, function ($users, $page) use ($fcm, $storeName) {
            \Log::info("Job [{$this->job->getJobId()}]: Processing chunk #{$page}. Memory usage: " . (memory_get_usage(true) / 1024 / 1024) . " MB");
            
            foreach ($users as $user) {
                foreach ($user->devices as $device) {
                    try {
                        $fcm->sendNotification(
                            $user,
                            'عرض جديد!',
                            "تم إصدار عرض جديد من متجر {$storeName}",
                            $device->fcm_token,
                            [
                                'offer_id' => (string) $this->offer->id,
                                'store_id' => (string) $this->offer->store_id,
                            ]
                        );
                    } catch (\Throwable $e) {
                        \Log::error("Job [{$this->job->getJobId()}]: Failed to send to user {$user->id} device {$device->id}: " . $e->getMessage());
                    }
                }
            }
            return true; // استمر في الـ chunk
        });

        \Log::info("Job [{$this->job->getJobId()}] has completed successfully.");

    } catch (\Throwable $e) {
        // هذا سيلتقط الأخطاء الفادحة التي تحدث قبل أو بعد الـ chunk
        \Log::error("Job [{$this->job->getJobId()}] FAILED: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        // أعد إلقاء الخطأ لكي تسجله Laravel في failed_jobs
        throw $e;
    }
}
}
