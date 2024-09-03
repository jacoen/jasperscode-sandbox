<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\RequestNewTokenController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ExpiredProjectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskImageController;
use App\Http\Controllers\TwoFactorController;
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

Route::middleware(['auth', 'twofactor'])->group(function () {
    Route::controller(TwoFactorController::class)->group(function () {
        Route::get('/verify', 'create')->name('verify.create');
        Route::get('/verify/resend', 'resend')->name('verify.resend');
        Route::post('/verify', 'store')->name('verify.store');
    });

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::controller(NewsletterController::class)->prefix('/newsletter')->group(function () {
        Route::get('/', 'create')->name('newsletter.create');
        Route::post('/', 'store')->name('newsletter.store');
    });

    Route::resource('/users', UserController::class)->except('show');

    Route::controller(ProfileController::class)->name('profile.')->prefix('/profile')->group(function () {
        Route::get('/', 'show')->name('show')->middleware('password.confirm');
        Route::put('/', 'put')->name('update');
    });

    Route::put('two-factor-settings', [ProfileController::class, 'twoFactorSettings'])->name('two-factor.update');

    Route::get('/projects/expired', ExpiredProjectController::class)->name('projects.expired');
    Route::resource('/projects', ProjectController::class);
    Route::controller(ProjectController::class)->prefix('/projects')->name('projects.')->group(function () {
        Route::patch('/{project}/restore', [ProjectController::class, 'restore'])->withTrashed()->name('restore');
        Route::delete('{project}/force_delete', 'forceDelete')->withTrashed()->name('force-delete');
    });

    Route::resource('/tasks', TaskController::class)->except(['create', 'store']);

    Route::controller(TaskController::class)->group(function () {
        Route::prefix('/projects/{project}/tasks')->group(function () {
            Route::get('/create', 'create')->name('tasks.create');
            Route::post('/', 'store')->name('tasks.store');
        });

        Route::patch('/{task}/restore', 'restore')->withTrashed()->name('tasks.restore');
        Route::delete('/tasks/{task}/force_delete', 'forceDelete')->withTrashed()->name('tasks.force-delete');
        Route::get('/admin/tasks', 'adminTasks')->name('admin.tasks');
    });

    Route::controller(CompanyController::class)->name('companies.')->prefix('/companies')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{company}', 'show')->name('show');
        Route::get('/{company}/edit', 'edit')->name('edit');
        Route::put('/{company}', 'update')->name('update');
        Route::delete('/{company}', 'destroy')->name('destroy');
    });

    Route::prefix('trashed')->group(function () {
        Route::get('/projects', [ProjectController::class, 'trashed'])->name('projects.trashed');
        Route::get('/tasks', [TaskController::class, 'trashed'])->name('tasks.trashed');
    });

    Route::delete('tasks/{task}/images/{image}', TaskImageController::class)->name('task-image.delete');

    Route::get('/activities', ActivityController::class)->name('activities.index');
});

Route::get('/design/{design}', DesignController::class)->name('design');
