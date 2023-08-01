<?php

use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectTaskController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('/projects', ProjectController::class);
    Route::get('/trashed/projects', [ProjectController::class, 'trashed'])->name('projects.trashed');

    Route::get('/projects/{project}/tasks', ProjectTaskController::class)
        ->middleware('can: read task');

    Route::controller(TaskController::class)->group(function () {
        Route::post('/projects/{project}/tasks', 'store');

        Route::prefix('/tasks')->group(function () {
            Route::get('/', 'index')->name('tasks.index');
            Route::get('/{task}', 'show')->name('tasks.show');
        });
    });
});
