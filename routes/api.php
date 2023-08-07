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
    Route::apiResource('/projects', ProjectController::class);
    Route::get('/trashed/projects', [ProjectController::class, 'trashed'])->name('projects.trashed');
    Route::put('/projects/{project}/restore', [ProjectController::class, 'restore'])->withTrashed()->name('projects.restore');
    
    Route::apiResource('/tasks', TaskController::class)->except('store');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/trashed/tasks', [TaskController::class, 'trashed'])->name('tasks.trashed');
    Route::put('/tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');

    Route::get('/projects/{project}/tasks', ProjectTaskController::class);
    
    

});
