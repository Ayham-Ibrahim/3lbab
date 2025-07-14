<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class CleanOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications older than 2 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = Notification::where('created_at', '<', now()->subDays(2))->delete();
        $this->info("Deleted $deleted notifications.");
    }
}
