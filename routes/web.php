<?php

use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\RequestNewTokenController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::controller(AccountActivationController::class)->prefix('/change-password')->middleware('guest')->group(function () {
    Route::get('/{password_token}', 'create')->name('activate-account.create');
    Route::post('/', 'store')->name('activate-account.store');
});

Route::controller(RequestNewTokenController::class)->prefix('/request-token')->middleware('guest')->group(function () {
    Route::get('/{password_token}', 'create')->name('request-token.create');
    Route::post('/', 'store')->name('request-token.store');
});

Route::middleware('auth')->group(function () {
    Route::view('about', 'about')->name('about');

    Route::resource('/users', UserController::class)->except('show');

    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    Route::resource('/projects', ProjectController::class);
});
