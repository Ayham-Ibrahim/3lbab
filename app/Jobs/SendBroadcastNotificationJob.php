<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendBroadcastNotificationJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;
    protected string $title;
    protected string $body;
    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $title, string $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fcm = new FcmService();

        User::with('devices')->chunk(500, function ($users) use ($fcm) {
            foreach ($users as $user) {
                foreach ($user->devices as $device) {
                    try {
                        $fcm->sendNotification(
                            $user,
                            $this->title,
                            $this->body,
                            $device->fcm_token,
                            $this->data
                        );
                    } catch (\Throwable $e) {
                        Log::error("فشل إرسال إشعار إلى المستخدم {$user->id} على الجهاز {$device->id}: {$e->getMessage()}");
                        $errorMessage = $e->getMessage();
                        if (str_contains($errorMessage, 'UNREGISTERED') || str_contains($errorMessage, 'NotRegistered')) {
                            \Log::info("Deleting unregistered FCM token for device ID: {$device->id}");
                            $device->delete(); 
                        }
                    }
                }
            }
        });
    }
}
