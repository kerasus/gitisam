<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceTypeController;
use App\Http\Controllers\Api\BuildingController;

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

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::resource('units', UnitController::class);
    Route::resource('users', '\\'.UserController::class);
    Route::resource('invoices', '\\'.InvoiceController::class);
    Route::resource('buildings', '\\'.BuildingController::class);
    Route::resource('invoice-types', '\\'.InvoiceTypeController::class);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('/verify-mobile', [AuthController::class, 'verifyMobile']);

});
