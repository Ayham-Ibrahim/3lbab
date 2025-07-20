<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Models\Category;
use App\Services\FcmService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCategoryNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $category;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(Category $category)
    {
        $this->category = $category;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $storeManagers = User::role('storemanager')->with('devices')->get();
        $fcmService = new FcmService();

        $status = $this->category->is_available ? 'متاحة' : 'غير متاحة';
        $title = 'تحديث على التصنيفات';
        $body = "تم تغيير حالة التصنيف ({$this->category->name}) إلى: {$status}";

        foreach ($storeManagers as $user) {
            foreach ($user->devices as $device) {
                try {
                    $fcmService->sendNotification(
                        $user,
                        $title,
                        $body,
                        $device->fcm_token,
                        [
                            'category_id' => (string) $this->category->id,
                            'is_available' => $this->category->is_available ? '1' : '0',
                        ]
                    );
                } catch (\Throwable $e) {
                    \Log::error("فشل إرسال إشعار إلى المستخدم {$user->id} على الجهاز {$device->id}: {$e->getMessage()}");
                    $errorMessage = $e->getMessage();
                    if (str_contains($errorMessage, 'UNREGISTERED') || str_contains($errorMessage, 'NotRegistered')) {
                        \Log::info("Deleting unregistered FCM token for device ID: {$device->id}");
                        $device->delete(); 
                    }
                }
            }
        }
    }
}
