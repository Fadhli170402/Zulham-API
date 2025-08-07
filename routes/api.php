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
Route::get('/ratingsall', [RatingsController::class, 'indexAll']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->middleware('guest')->name('password.email');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->middleware('guest')->name('password.update');

Route::middleware('auth:sanctum', 'role:admin')->group(function () {
    Route::apiResource('/admin/dasboard', adminController::class);
    Route::apiResource('/locations', LocationsController::class);
    Route::apiResource('/Tour', TourController::class);
    Route::apiResource('/media', MediaController::class);
    Route::get('/showRatings', [RatingsController::class, 'getRating']);
    Route::get('/showPengaduan', [adminController::class, 'getPengaduan']);
    Route::delete('/ratings/{id}', [RatingsController::class, 'destroyRatings']);
    Route::get('/showPengaduan/{id}', [adminController::class, 'getPengaduanById']);
    Route::delete('/Media', [adminController::class, 'destroyMedia']);
    Route::get('/Media', [adminController::class, 'showMedia']);
    Route::post('admin/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum', 'role:user')->group(function () {
    Route::apiResource('/users', userController::class);
    Route::apiResource('/ratings', RatingsController::class);
    Route::get('/get-rating', [RatingsController::class, 'getRating']);

    Route::apiResource('/complaints', ComplaintsController::class);
    Route::apiResource('/media', MediaController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/complaints/user/{id}', [ComplaintsController::class, 'getComplaintById']);
});
