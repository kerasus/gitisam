<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\UnitUserController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DatabaseBackupController;
use App\Http\Controllers\Api\InvoiceCategoryController;
use App\Http\Controllers\Api\InvoiceDistributionController;
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
Route::get('/test', [AuthController::class, 'test']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/units/publicIndex', [UnitController::class, 'publicIndex']);
// Public route for direct payment link
Route::get('/units/{unit_id}/pay-debt/{target_group}', [TransactionController::class, 'redirectToGatewayDirect'])
    ->name('units.pay-debt');
Route::get('/units/{unit}/get_balance', [UnitController::class, 'getBalance']);

// Public route for custom payment request
Route::post('/transactions/redirect-to-gateway', [TransactionController::class, 'redirectToGateway'])
    ->name('transactions.redirect-to-gateway');

Route::get('/transactions/callback', [TransactionController::class, 'handleCallback'])
    ->name('transactions.callback');
Route::post('/transactions/callback', [TransactionController::class, 'handleCallback'])
    ->name('transactions.callback');
Route::get('/transactions/{transaction}/public-data', [TransactionController::class, 'getPublicData'])
    ->name('transactions.publicData');

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{user_id}/reset-password', [AuthController::class, 'resetPassword'])->name('users.reset.password');

    Route::resource('units', UnitController::class);
    Route::resource('users', '\\'.UserController::class);
    Route::resource('invoices', '\\'.InvoiceController::class);
    Route::resource('buildings', '\\'.BuildingController::class);
    Route::resource('unit-users', '\\'.UnitUserController::class);
    Route::resource('transactions', '\\' . TransactionController::class);
    Route::resource('invoice-categories', '\\'.InvoiceCategoryController::class);
    Route::resource('invoice-distributions', '\\'.InvoiceDistributionController::class);


    Route::post('/users/{userId}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/users/{userId}/remove-role', [UserController::class, 'removeRole']);

    Route::post('/transactions/store_income', [TransactionController::class, 'storeIncome']);
    Route::post('/unit_users/bulk', [UnitUserController::class, 'bulkStore']);
    Route::post('/units/{unit}/update_balance', [UnitController::class, 'updateBalance']);
    Route::post('/units/{unit}/{target_group}/send-debt-sms', [UnitController::class, 'sendDebtSMS']);
    Route::post('/units/{unit}/send-login-info', [UnitController::class, 'sendLoginInfo'])->name('units.sendLoginInfo');
    Route::post('/invoice-distributions/bulk', [InvoiceDistributionController::class, 'bulkStore']);

    Route::post('/invoices/{invoiceId}/attach-image', [InvoiceController::class, 'attachImageToInvoice'])
        ->name('invoices.attachImage');
    Route::delete('/invoices/{invoiceId}/detach-image/{imageId}', [InvoiceController::class, 'detachImageFromInvoice'])
        ->name('invoices.detachImage');

    Route::post('/transactions/{transactionsId}/attach-image', [TransactionController::class, 'attachImage'])
        ->name('transactions.attachImage');
    Route::delete('/transactions/{transactionsId}/detach-image/{imageId}', [TransactionController::class, 'detachImage'])
        ->name('transactions.detachImage');

    Route::post('/units/{unit}/attach-user', [UnitController::class, 'attachUser'])
        ->name('transactions.attachImage');
    Route::delete('/units/{unit}/detach-user/{userId}', [UnitController::class, 'detachUser'])
        ->name('transactions.detachImage');

    Route::get('/sms/account-balance', [SmsController::class, 'getAccountBalance'])
        ->name('account.balance');
    Route::post('/sms/send-monthly-debt-reminders/{target_group}', [SmsController::class, 'sendMonthlyDebtReminders'])
        ->name('send.monthly.debt.reminders');

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('/verify-mobile', [AuthController::class, 'verifyMobile']);

    Route::get('/backup-database', [DatabaseBackupController::class, 'backupDatabase']);
});
