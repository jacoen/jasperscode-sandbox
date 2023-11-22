<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\RequestNewTokenController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskImageController;
use App\Http\Controllers\TrashedProjectController;
use App\Http\Controllers\TrashedTaskController;
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
})->name('welcome');

Auth::routes(['register' => false]);

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::controller(AccountActivationController::class)
    ->prefix('/change-password')
    ->name('activate-account.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/{password_token}', 'create')->name('create');
        Route::post('/', 'store')->name('store');
    });

Route::controller(RequestNewTokenController::class)
    ->prefix('/request-token')
    ->name('request-token.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/{password_token}', 'create')->name('create');
        Route::post('/', 'store')->name('store');
    });

Route::middleware('auth')->group(function () {
    Route::controller(NewsletterController::class)->prefix('/newsletter')->group(function () {
        Route::get('/', 'create')->name('newsletter.create');
        Route::post('/', 'store')->name('newsletter.store');
    });

    Route::resource('/users', UserController::class)->except('show');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('/projects', ProjectController::class);
    Route::controller(ProjectController::class)->prefix('/projects')->name('projects.')->group(function () {
        Route::patch('/{project}/restore', [ProjectController::class, 'restore'])->withTrashed()->name('restore');
        Route::patch('{project}/force_delete', 'forceDelete')->withTrashed()->name('force-delete');
    });

    Route::resource('/tasks', TaskController::class)->except(['create', 'store']);

    Route::controller(TaskController::class)->group(function () {
        Route::prefix('/projects/{project}/tasks')->group(function () {
            Route::get('/create', 'create')->name('tasks.create');
            Route::post('/', 'store')->withTrashed()->name('tasks.store');
        });

        Route::patch('/{task}/restore', 'restore')->withTrashed()->name('tasks.restore');
        Route::patch('/tasks/{task}/force_delete', 'forceDelete')->withTrashed()->name('tasks.force-delete');
        Route::get('/admin/tasks', 'adminTasks')->name('admin.tasks');
    });

    Route::prefix('trashed')->group(function () {
        Route::get('/projects', [ProjectController::class, 'trashed'])->name('projects.trashed');
        Route::get('/tasks', [TaskController::class, 'trashed'])->name('tasks.trashed');
    });

    Route::delete('tasks/{task}/images/{image}', TaskImageController::class)->name('task-image.delete');

    Route::get('/activities', ActivityController::class)->name('activities.index');
});
