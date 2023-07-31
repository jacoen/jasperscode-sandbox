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
    Route::controller(ProjectController::class)->prefix('/projects')->group(function () {
        Route::get('/', 'index')->name('projects.index');
        Route::post('/', 'store')->name('projects.store');
        Route::get('/{project}', 'show')->name('projects.show');
        Route::put('/{project}', 'update')->name('projects.update');
    });
    Route::get('/trashed/projects',[ProjectController::class, 'trashed'])->name('projects.trashed');
    
    Route::get('/projects/{project}/tasks', ProjectTaskController::class)
        ->middleware('can: read task');
});
