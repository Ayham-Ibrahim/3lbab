<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
