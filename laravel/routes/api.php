<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Route::get('/me', function (Request $request) {
    //     return $request->user();
    // });

    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);
    Route::delete('/posts/{post}/delete', [PostController::class, 'destroy']);
    // Route::post('/posts/{post}/restore', [PostController::class, 'restore']);//Not part of the task.
});
