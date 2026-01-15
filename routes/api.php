<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // User Management
    Route::apiResource('users', UserController::class);

    // Avatar Management
    Route::post('users/{user}/avatar', [UserController::class, 'uploadAvatar'])->name('users.avatar.upload');
    Route::delete('users/{user}/avatar', [UserController::class, 'deleteAvatar'])->name('users.avatar.delete');
});
