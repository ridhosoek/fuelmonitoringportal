<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BastController;
use App\Http\Controllers\DailyReconciliationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuelDispensingController;
use App\Http\Controllers\FuelOrderController;
use App\Http\Controllers\FuelReceivingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\OperationalDashboardController;
use App\Http\Controllers\OutstandingInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitMonitoringController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/operational-control', OperationalDashboardController::class)->name('operational-dashboard');

    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/current-stock', [InventoryController::class, 'currentStock'])->name('current-stock');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
    });

    Route::middleware('role:super_admin,vhs_admin')->group(function (): void {
        Route::resource('receivings', FuelReceivingController::class)->only(['create', 'store']);
        Route::post('/receivings/{receiving}/post', [FuelReceivingController::class, 'post'])->name('receivings.post');
    });

    Route::middleware('role:super_admin,vhs_admin,vhs_operator,supervisor')->group(function (): void {
        Route::resource('dispensings', FuelDispensingController::class)->only(['create', 'store']);
        Route::resource('basts', BastController::class)->only(['create', 'store']);
        Route::resource('reconciliations', DailyReconciliationController::class)->only(['create', 'store']);

        Route::post('/dispensings/{dispensing}/post', [FuelDispensingController::class, 'post'])->name('dispensings.post');
        Route::post('/basts/{bast}/finalize', [BastController::class, 'finalize'])->name('basts.finalize');
        Route::post('/reconciliations/{reconciliation}/submit', [DailyReconciliationController::class, 'submit'])->name('reconciliations.submit');
    });

    Route::middleware('role:super_admin,vhs_admin,supervisor,customer_requester')->group(function (): void {
        Route::resource('fuel-orders', FuelOrderController::class)->only(['create', 'store']);
    });

    Route::get('/receivings/{receiving}/attachment', [FuelReceivingController::class, 'downloadAttachment'])->name('receivings.attachment');
    Route::resource('receivings', FuelReceivingController::class)->only(['index', 'show']);

    Route::get('/dispensings/{dispensing}/attachment', [FuelDispensingController::class, 'downloadAttachment'])->name('dispensings.attachment');
    Route::resource('dispensings', FuelDispensingController::class)->only(['index', 'show']);

    Route::resource('fuel-orders', FuelOrderController::class)->only(['index', 'show']);
    Route::resource('basts', BastController::class)->only(['index', 'show']);
    Route::resource('reconciliations', DailyReconciliationController::class)->only(['index', 'show']);
    Route::get('/units/export-pdf', [UnitMonitoringController::class, 'exportPdf'])->name('units.export-pdf');
    Route::get('/units/{unit}/export-pdf', [UnitMonitoringController::class, 'exportUnitPdf'])->name('units.export-unit-pdf');
    Route::get('/units', [UnitMonitoringController::class, 'index'])->name('units.index');
    Route::get('/units/{unit}', [UnitMonitoringController::class, 'show'])->name('units.show');
    Route::get('/outstanding-invoices', [OutstandingInvoiceController::class, 'index'])
        ->middleware('role:super_admin,vhs_admin,supervisor')
        ->name('outstanding-invoices.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::middleware('role:super_admin,supervisor')->group(function (): void {
        Route::post('/receivings/{receiving}/reverse', [FuelReceivingController::class, 'reverse'])->name('receivings.reverse');
        Route::post('/dispensings/{dispensing}/reverse', [FuelDispensingController::class, 'reverse'])->name('dispensings.reverse');
        Route::post('/reconciliations/{reconciliation}/approve', [DailyReconciliationController::class, 'approve'])->name('reconciliations.approve');
    });

    Route::middleware('role:super_admin,vhs_admin')->group(function (): void {
        Route::get('/master', [MasterDataController::class, 'index'])->name('master.index');
        Route::get('/master/{resource}/create', [MasterDataController::class, 'create'])->name('master.create');
        Route::get('/master/{resource}/export-pdf', [MasterDataController::class, 'exportPdf'])->name('master.export-pdf');
        Route::get('/master/{resource}/{id}/edit', [MasterDataController::class, 'edit'])->name('master.edit');
        Route::post('/master/{resource}', [MasterDataController::class, 'store'])->name('master.store');
        Route::put('/master/{resource}/{id}', [MasterDataController::class, 'update'])->name('master.update');
    });
});
