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
        $storeManagers = User::role('storemanager')->whereNotNull('fcm_token')->get();
        $fcmService = new FcmService();

        $status = $this->category->is_available ? 'متاحة' : 'غير متاحة';
        $title = 'تحديث على التصنيفات';
        $body = "تم تغيير حالة التصنيف ({$this->category->name}) إلى: {$status}";

        foreach ($storeManagers as $user) {
            $fcmService->sendNotification(
                $user,
                $title,
                $body,
                $user->fcm_token,
                [
                    'category_id' => (string) $this->category->id,
                    'is_available' => $this->category->is_available ? '1' : '0',
                ]
            );
        }
    }
}
