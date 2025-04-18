<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Register route for creating a new user
Route::post('/register', [AuthController::class, 'register']);

// Login route for authenticating a user
Route::post('/login', [AuthController::class, 'login']);

// Logout route for logging out the authenticated user (requires authentication)
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
