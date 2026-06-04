<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTransactionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminLoginAuditController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyPhotoController;
use App\Http\Controllers\RentalRequestController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TenantPropertyController;
use App\Http\Controllers\TenantContractController;
use App\Http\Controllers\TenantRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AgentContractController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\HomeController;


Route::get('/', HomeController::class)->name('home');

Route::middleware(['enabled', 'guest_or_tenant'])->group(function () {
    Route::get('/houses', [TenantPropertyController::class, 'index'])->name('houses.index');
    Route::get('/houses/{property}', [TenantPropertyController::class, 'show'])->name('houses.show');
    Route::get('/houses/{property}/photos', [TenantPropertyController::class, 'photos'])->name('houses.photos');
});
Route::get('/locations/regencies/{province}', [TenantPropertyController::class, 'regencies']);
Route::get('/locations/districts/{regency}', [TenantPropertyController::class, 'districts']);
Route::get('/locations/villages/{district}', [TenantPropertyController::class, 'villages']);

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
  
Route::middleware(['auth','enabled','role:admin'])
    ->prefix('admin')
    ->group(function () {

    Route::get('/dashboard', AdminDashboardController::class);

    Route::get('/transactions', [AdminTransactionController::class, 'index']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/create', [AdminUserController::class, 'create']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle']);

    Route::get('/login-audit', [AdminLoginAuditController::class, 'index']);

});

Route::middleware(['auth','enabled','role:agent'])
    ->prefix('agent')
    ->group(function () {

    Route::get('/dashboard', AgentDashboardController::class);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/locations/regencies/{province}', [PropertyController::class, 'regencies']);
    Route::get('/locations/districts/{regency}', [PropertyController::class, 'districts']);
    Route::get('/locations/villages/{district}', [PropertyController::class, 'villages']);
    Route::get('/properties/create', [PropertyController::class, 'create']);
    Route::get('/properties/{property}', [PropertyController::class, 'show']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::get('/properties/{property}/edit', [PropertyController::class, 'edit']);
    Route::put('/properties/{property}', [PropertyController::class, 'update']);
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);

    Route::get('/properties/{property}/photos/gallery', [PropertyPhotoController::class, 'gallery'])
        ->name('agent.properties.photos.gallery');
    Route::post('/properties/{property}/photos', [PropertyPhotoController::class, 'store']);
    Route::delete('/properties/{property}/photos/{photo}', [PropertyPhotoController::class, 'destroy']);

    Route::get('/rental-requests', [RentalRequestController::class, 'index']);
    Route::get('/rental-requests/{request}', [RentalRequestController::class, 'showAgent']);
    Route::get('/contracts', [AgentContractController::class, 'index']);
    Route::post('/rental-requests/{request}/approve', [RentalRequestController::class, 'approve']);
    Route::post('/rental-requests/{request}/reject', [RentalRequestController::class, 'reject']);
    Route::post('/rental-requests/{request}/cancel-lock', [RentalRequestController::class, 'cancelLock']);
    Route::post('/contract-extensions/{extension}/approve', [ContractController::class, 'approveExtension']);
    Route::post('/contract-extensions/{extension}/reject', [ContractController::class, 'rejectExtension']);
    Route::post('/contracts/{contract}/end-rental', [ContractController::class, 'endRental']);

});

Route::middleware(['auth','enabled','role:tenant'])
    ->prefix('tenant')
    ->group(function () {

    Route::get('/dashboard', TenantDashboardController::class);

    Route::get('/properties', [TenantPropertyController::class, 'index']);
    Route::get('/properties/{property}', [TenantPropertyController::class, 'show']);
    Route::post('/properties/{property}/request', [RentalRequestController::class, 'store'])
        ->name('tenant.properties.request');

    Route::get('/requests', [TenantRequestController::class, 'index']);
    Route::get('/requests/{request}', [TenantRequestController::class, 'show']);
    Route::post('/requests/{request}/cancel', [TenantRequestController::class, 'cancel']);

    Route::get('/contracts', [TenantContractController::class, 'index']);
    Route::get('/contracts/{transaction}', [ContractController::class, 'show']);
    Route::get('/contracts/{transaction}/download-pdf', [ContractController::class, 'download']);
    Route::post('/contracts/{contract}/extend', [ContractController::class, 'extend']);
    Route::post('/contract-extensions/{extension}/cancel', [ContractController::class, 'cancelExtensionByTenant']);

    Route::post('/transactions/{transaction}/pay', [PaymentController::class, 'pay']);
});

Route::middleware('auth')->group(function () {
    Route::get('/contracts/{transaction}', [ContractController::class, 'show']);
    Route::get('/contracts/{transaction}/download-pdf', [ContractController::class, 'download']);

    Route::get('/messages', [MessageController::class, 'list']);
    Route::get('/messages/property/{property}', [MessageController::class, 'indexByProperty']);
    Route::post('/messages/property/{property}', [MessageController::class, 'storeByProperty']);
    Route::get('/messages/conversations/{conversation}', [MessageController::class, 'showConversation']);
    Route::post('/messages/conversations/{conversation}', [MessageController::class, 'storeConversation']);
    Route::get('/messages/{rentalRequest}', [MessageController::class, 'index']);
    Route::post('/messages/{rentalRequest}', [MessageController::class, 'store']);

    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'password']);

});

Route::post('/timezone', function (Request $request) {
    if ($request->timezone) {
        session(['tz' => $request->timezone]);
    }
    return response()->noContent();
});
