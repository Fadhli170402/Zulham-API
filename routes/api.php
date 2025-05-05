<?php

use App\Http\Controllers\adminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\userController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum', 'role:admin')->group(function () {
    Route::apiResource('/admin/dasboard', adminController::class);
    Route::post('admin/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum', 'role:user')->group(function () {
    Route::apiResource('/users', userController::class);
    // Route::get('/users/{id}', [userController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
