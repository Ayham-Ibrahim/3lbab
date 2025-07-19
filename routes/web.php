<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/link', function () {
    try {
            Artisan::call('storage:link');

            return response()->json([
                'success' => true,
                'message' => 'Storage link created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating storage link: ' . $e->getMessage()
            ], 500);
        }
});


Route::get('/clear-cache', function() {
    try {
        Artisan::call('config:clear'); 
        Artisan::call('config:cache'); 
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        return '<h1>Cache Cleared and Recached Successfully!</h1><p>You can now delete this route from web.php</p>';
    } catch (Exception $e) {
        return '<h1>Error:</h1><p>' . $e->getMessage() . '</p>';
    }
});