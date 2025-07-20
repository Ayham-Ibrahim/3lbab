<?php

namespace App\Jobs;

use App\Models\Offer;
use Illuminate\Support\Str;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


class NotifyStoreManagerOfOfferApprovalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
     /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60; // 60 ثانية أكثر من كافية

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
        Log::info("Job [{$this->job->getJobId()}] - Notifying store manager for approved offer ID: {$this->offer->id}");

        try {
            $manager = $this->offer->store->manager;

            if (!$manager) {
                Log::warning("Job [{$this->job->getJobId()}] - Could not find a manager for store ID: {$this->offer->store_id}");
                return;
            }

            $fcm = new FcmService();
            $offerDescription = Str::limit($this->offer->description, 30); // لجعل الرسالة قصيرة

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

            Log::info("Job [{$this->job->getJobId()}] - Successfully sent approval notifications to manager ID: {$manager->id}");

        } catch (\Throwable $e) {
            Log::error("Job [{$this->job->getJobId()}] - FAILED: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}