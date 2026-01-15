<?php

use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
    ]);
})->name('home');

Route::get('dashboard', [DocumentationController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
