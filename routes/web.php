<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Teacher\TeacherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('teachers', TeacherController::class)->names('teachers');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';




//Route::prefix('teacher')->name('teacher.')->group(function () {
//    Route::get('login', fn () => inertia('Teacher/Login'))->name('login.form');
//    Route::post('login', [TeacherController::class, 'login'])->name('login');
//
//    Route::get('register', fn () => inertia('Teacher/Register'))->name('register.form');
//    Route::post('register', [TeacherController::class, 'register'])->name('register');
//
//    Route::middleware('auth:teacher')->group(function () {
//        Route::get('dashboard', fn () => inertia('Teacher/Dashboard'))->name('dashboard');
//        Route::get('profile', [TeacherController::class, 'getProfile'])->name('profile.get');
//        Route::post('profile', [TeacherController::class, 'updateProfile'])->name('profile.update');
//        Route::post('logout', [TeacherController::class, 'logout'])->name('logout');
//    });
//});
