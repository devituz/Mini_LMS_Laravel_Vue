<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Groups\GroupsController;
use App\Http\Controllers\Students\StudentsController;
use App\Http\Controllers\Teacher\TeacherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('teachers', TeacherController::class)->names('teachers');
    Route::resource('students',StudentsController::class)->names('students');
    Route::resource('groups',GroupsController::class)->names('groups');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';




//Route::prefix('teacher')->name('teacher.')->group(function () {
//    Route::get('login', fn () => inertia('Teacher/Login'))->name('login.form');
//    Route::post('login', [GroupsController::class, 'login'])->name('login');
//
//    Route::get('register', fn () => inertia('Teacher/Register'))->name('register.form');
//    Route::post('register', [GroupsController::class, 'register'])->name('register');
//
//    Route::middleware('auth:teacher')->group(function () {
//        Route::get('dashboard', fn () => inertia('Teacher/Dashboard'))->name('dashboard');
//        Route::get('profile', [GroupsController::class, 'getProfile'])->name('profile.get');
//        Route::post('profile', [GroupsController::class, 'updateProfile'])->name('profile.update');
//        Route::post('logout', [GroupsController::class, 'logout'])->name('logout');
//    });
//});
