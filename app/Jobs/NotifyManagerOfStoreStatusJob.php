<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyManagerOfStoreStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    protected Store $store;
    protected bool $isNowAvailable;

    /**
     * Create a new job instance.
     */
    public function __construct(Store $store, bool $isNowAvailable)
    {
        $this->store = $store;
        $this->isNowAvailable = $isNowAvailable;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobId = $this->job?->getJobId() ?? 'N/A';
        Log::info("Job [{$jobId}] - Notifying manager of store status change. Store ID: {$this->store->id}");

        try {
            // نصل إلى المدير مباشرة من خلال علاقة المتجر
            $manager = $this->store->manager;

            if (!$manager) {
                Log::warning("Job [{$jobId}] - Could not find a manager for store ID: {$this->store->id}");
                return;
            }

            // تحديد عنوان ورسالة الإشعار بناءً على الحالة الجديدة
            if ($this->isNowAvailable) {
                $title = 'متجرك أصبح متاحًا!';
                $body = "تهانينا! تمت الموافقة على متجرك '{$this->store->name}' وهو الآن مرئي للعملاء.";
            } else {
                $title = 'تم إيقاف متجرك مؤقتًا';
                $body = "نأسف لإعلامك، تم إيقاف متجرك '{$this->store->name}' مؤقتًا من قبل الإدارة.";
            }

            $fcm = new FcmService();

            foreach ($manager->devices as $device) {
                try {
                    $fcm->sendNotification(
                        $manager,
                        $title,
                        $body,
                        $device->fcm_token,
                        [
                            'store_id' => (string) $this->store->id,
                            'type' => 'store_status_changed'
                        ]
                    );
                } catch (\Throwable $e) {
                    $errorMessage = $e->getMessage();
                    Log::error("Job [{$jobId}] - Failed to send store status notification to manager {$manager->id} on device {$device->id}: " . $errorMessage);

                    if (str_contains($errorMessage, 'UNREGISTERED')) {
                        Log::info("Job [{$jobId}] - Deleting unregistered device for manager. Device ID: {$device->id}");
                        $device->delete();
                    }
                }
            }

            Log::info("Job [{$jobId}] - Successfully sent store status notifications to manager ID: {$manager->id}");

        } catch (\Throwable $e) {
            Log::error("Job [{$jobId}] - FAILED: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}