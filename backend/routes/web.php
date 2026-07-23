<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SaleController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/payments', [CustomerController::class, 'storePayment'])->name('customers.payments.store');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables');
        Route::get('/low-stock', [ReportController::class, 'lowStock'])->name('low-stock');
        Route::get('/sales-summary', [ReportController::class, 'salesSummary'])->name('sales-summary');
    });

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'destroy']);
    });
});

require __DIR__.'/auth.php';
