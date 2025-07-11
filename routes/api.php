<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware(['auth:sanctum'])->group(function () {   
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/checkout', [AuthController::class, 'checkout']);
Route::get('verify-transaction/{reference}', [AuthController::class, 'verify']);

});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
