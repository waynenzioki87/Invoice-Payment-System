
<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StaffDashboardController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard Redirect
Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'staff' => redirect()->route('staff.dashboard'),
        'customer' => redirect()->route('customer.dashboard'),
        default => abort(403),
    };
})->middleware('auth')->name('dashboard');

// Admin Dashboard
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
});

// Staff Dashboard
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])
        ->name('staff.dashboard');
});

// Customer Dashboard & Portal
// Email verification requirement REMOVED
Route::middleware(['auth', 'role:customer'])->group(function () {

    Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])
        ->name('customer.dashboard');

    Route::get('/customer/invoices', [CustomerPortalController::class, 'invoices'])
        ->name('customer.invoices.index');

    Route::get('/customer/invoices/{invoice}', [CustomerPortalController::class, 'invoice'])
        ->name('customer.invoices.show');

    Route::get('/customer/invoices/{invoice}/pdf', [CustomerPortalController::class, 'downloadInvoice'])
        ->name('customer.invoices.pdf');

    Route::get('/customer/invoices/{invoice}/print', [CustomerPortalController::class, 'printInvoice'])
        ->name('customer.invoices.print');

    Route::get('/customer/payments', [CustomerPortalController::class, 'payments'])
        ->name('customer.payments.index');

    Route::get('/customer/payments/{payment}', [CustomerPortalController::class, 'payment'])
        ->name('customer.payments.show');

    Route::get('/customer/payments/{payment}/receipt', [CustomerPortalController::class, 'downloadReceipt'])
        ->name('customer.payments.receipt');

    Route::get('/customer/payments/{payment}/receipt/print', [CustomerPortalController::class, 'printReceipt'])
        ->name('customer.payments.receipt.print');

    Route::get('/customer/receipts', [CustomerPortalController::class, 'receipts'])
        ->name('customer.receipts.index');
});

// Admin and Staff Management
Route::middleware(['auth', 'role:admin,staff'])->group(function () {

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])
        ->name('reports.index');

    Route::get('/reports/export', [ReportsController::class, 'export'])
        ->name('reports.export');

    // Customer Management
    Route::resource('customers', CustomerController::class);

    // Invoice Management
    Route::resource('invoices', InvoiceController::class);

    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
        ->name('invoices.pdf');

    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])
        ->name('invoices.print');

    Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])
        ->name('invoices.duplicate');

    // Payment Management
    Route::resource('payments', PaymentController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])
        ->name('payments.receipt');

    Route::get('/payments/{payment}/receipt/print', [PaymentController::class, 'printReceipt'])
        ->name('payments.receipt.print');

    // M-Pesa
    Route::post('/payments/mpesa', [MpesaController::class, 'initiate'])
        ->name('payments.mpesa.initiate');
});

// M-Pesa Callback
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('mpesa.callback');

// Profile Routes
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

// Authentication Routes
require __DIR__.'/auth.php';
