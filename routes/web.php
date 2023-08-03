<?php

use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\RequestNewTokenController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TrashedProjectController;
use App\Http\Controllers\TrashedTaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

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

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::controller(AccountActivationController::class)->prefix('/change-password')->middleware('guest')->group(function () {
    Route::get('/{password_token}', 'create')->name('activate-account.create');
    Route::post('/', 'store')->name('activate-account.store');
});

Route::controller(RequestNewTokenController::class)->prefix('/request-token')->middleware('guest')->group(function () {
    Route::get('/{password_token}', 'create')->name('request-token.create');
    Route::post('/', 'store')->name('request-token.store');
});

Route::middleware('auth')->group(function () {

    Route::resource('/users', UserController::class)->except('show');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('/projects', ProjectController::class);
    Route::patch('projects/{project}/restore', [ProjectController::class, 'restore'])->withTrashed()->name('projects.restore');

    Route::controller(TaskController::class)->group(function () {
        Route::prefix('/projects/{project}/tasks')->group(function () {
            Route::get('/create', 'create')->name('tasks.create');
            Route::post('/', 'store')->name('tasks.store');
        });

        Route::prefix('/tasks')->group(function () {
            Route::get('/', 'index')->name('tasks.index');
            Route::get('/{task}', 'show')->name('tasks.show');
            Route::get('/{task}/edit', 'edit')->name('tasks.edit');
            Route::put('/{task}', 'update')->name('tasks.update');
            Route::delete('/{task}', 'destroy')->name('tasks.destroy');
            Route::patch('/{task}/restore', 'restore')->withTrashed()->name('tasks.restore');
        });

        Route::get('user/tasks', 'userTasks')->name('tasks.user');
    });

    Route::prefix('trashed')->group(function () {
        Route::get('/projects', TrashedProjectController::class)->middleware('can:restore project')->name('projects.trashed');
        Route::get('/tasks', TrashedTaskController::class)->name('tasks.trashed');
    });
});
