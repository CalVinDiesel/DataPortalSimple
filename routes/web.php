<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/proxy', [ProxyController::class, 'proxy'])->name('proxy');

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Auth Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin Routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminController::class, 'manageUsers'])->name('users');
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/submissions', [AdminController::class, 'manageSubmissions'])->name('submissions');
        Route::post('/submissions/{submission}/update', [AdminController::class, 'updateSubmission'])->name('submissions.update');
    });

    // User Routes
    Route::middleware(['role:user'])->prefix('user')->name('user.')->group(function () {
        Route::get('/submit', [UserController::class, 'showSubmitForm'])->name('submit');
        Route::post('/submit', [SubmissionController::class, 'store']);
        Route::get('/view/{submission}', [UserController::class, 'viewProject'])->name('view');
    });
});
