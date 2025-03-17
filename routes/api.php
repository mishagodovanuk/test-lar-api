<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

Route::get('/v1/users', [UserController::class, 'index']);
Route::get('/v1/users/{id}', [UserController::class, 'show']);
Route::post('/v1/users', [UserController::class, 'store']);

Route::get('/v1/positions', [UserController::class, 'positions']);
Route::get('/v1/token', [UserController::class, 'token']);

