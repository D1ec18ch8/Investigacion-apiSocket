<?php

use App\Events\EventPrivate;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserManageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    // Collaborative user management (list, lock/unlock fields, update values)
    Route::get('/users/manage', [UserManageController::class, 'index'])->name('users.manage');
    Route::patch('/users/manage/{user}', [UserManageController::class, 'update'])->name('users.manage.update');
    Route::post('/users/manage/lock', [UserManageController::class, 'lock'])->name('users.manage.lock');
    Route::post('/users/manage/unlock', [UserManageController::class, 'unlock'])->name('users.manage.unlock');
});

