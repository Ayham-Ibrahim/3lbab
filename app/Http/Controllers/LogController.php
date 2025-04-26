<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    /**
     * Get the log file content.
     */
    public function getLog()
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return response()->json(['message' => 'Log file does not exist.'], 404);
        }

        $logContent = File::get($logPath);

        return response()->json(['log' => $logContent], 200);
    }

    /**
     * Delete the log file and regenerate it.
     */
    public function resetLog()
    {
        $logPath = storage_path('logs/laravel.log');

        // Check if log file exists
        if (File::exists($logPath)) {
            File::delete($logPath); // Delete the log file
        }

        // Recreate log file
        Log::info('Log file has been reset.');

        return response()->json(['message' => 'Log file deleted and regenerated.'], 200);
    }
}
