<?php

use App\Models\locations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\userController;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\adminController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\ComplaintsController;
use App\Http\Controllers\ForgotPasswordController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum', 'role:admin')->group(function () {
    Route::apiResource('/admin/dasboard', adminController::class);
    Route::apiResource('/locations', LocationsController::class);
    // Route::apiResource('/locations/{id}', LocationsController::class);
    Route::apiResource('/Tour', TourController::class);
    Route::post('admin/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum', 'role:user')->group(function () {
    Route::apiResource('/users', userController::class);
    Route::apiResource('/ratings', RatingsController::class);
    // Route::apiResource('/ratings/{id}', RatingsController::class);
    Route::apiResource('/complaints', ComplaintsController::class);
    Route::apiResource('/media', MediaController::class);
    // Route::get('/users/{id}', [userController::class, 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
