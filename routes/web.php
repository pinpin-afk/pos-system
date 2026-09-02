<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashierSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (! auth()->check()) {
        return Inertia::render('Auth/Portal');
    }

    return auth()->user()->canAccessAdmin()
        ? redirect()->route('dashboard')
        : redirect()->route('pos.index');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/kasir/login', [CashierSessionController::class, 'create'])->name('cashier.login');
    Route::post('/kasir/login', [CashierSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('cashier.login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/two-factor', [TwoFactorController::class, 'enable'])->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('profile.two-factor.confirm');
    Route::delete('/profile/two-factor', [TwoFactorController::class, 'disable'])->name('profile.two-factor.disable');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::post('/branches/{branch}/switch', [BranchController::class, 'switch'])->name('branches.switch');

    Route::get('/shifts/open', [ShiftController::class, 'create'])->name('shifts.open');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/close', [ShiftController::class, 'closeForm'])->name('shifts.close');
    Route::post('/shifts/close', [ShiftController::class, 'close'])->name('shifts.close.store');
    Route::post('/shifts/cash-movements', [ShiftController::class, 'cashMovement'])->name('shifts.cash-movements.store');

    Route::middleware('shift.open')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::post('/pos/sync', [PosController::class, 'sync'])->name('pos.sync');
        Route::post('/pos/hold', [PosController::class, 'hold'])->name('pos.hold');
        Route::delete('/pos/held/{sale}', [PosController::class, 'discardHold'])->name('pos.held.destroy');
        Route::get('/pos/customers', [PosController::class, 'searchCustomers'])->name('pos.customers.search');
        Route::post('/pos/customers', [PosController::class, 'storeCustomer'])->name('pos.customers.store');
    });

    Route::get('/receipts/{sale}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::post('/receipts/{sale}/email', [ReceiptController::class, 'email'])->name('receipts.email');

    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    });
    Route::middleware('permission:products.manage')->group(function () {
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::middleware('permission:categories.manage')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    Route::middleware('permission:brands.manage')->group(function () {
        Route::resource('brands', BrandController::class)->except(['show']);
    });

    Route::middleware('permission:stock.view')->group(function () {
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    });
    Route::middleware('permission:stock.adjust')->post('/stock/{product}/adjust', [StockController::class, 'adjust'])->name('stock.adjust');

    Route::middleware('permission:stock.opname')->group(function () {
        Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
        Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->name('stock-opnames.store');
        Route::post('/stock-opnames/{stock_opname}/complete', [StockOpnameController::class, 'complete'])->name('stock-opnames.complete');
    });

    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    });
    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
    });
    Route::middleware('permission:purchases.manage')->group(function () {
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    });
    Route::middleware('permission:sales.refund')->post('/sales/{sale}/refund', [SaleController::class, 'refund'])->name('sales.refund');
    Route::middleware('permission:sales.void')->post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

    Route::middleware('permission:reports.view')->get('/reports', ReportController::class)->name('reports.index');

    Route::middleware('permission:shifts.view')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('permission:imports.manage')->group(function () {
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::post('/imports/products', [ImportController::class, 'products'])->name('imports.products');
        Route::post('/imports/customers', [ImportController::class, 'customers'])->name('imports.customers');
        Route::post('/imports/stock', [ImportController::class, 'stock'])->name('imports.stock');
    });

    Route::middleware('permission:exports.view')->group(function () {
        Route::get('/exports/products', [ExportController::class, 'products'])->name('exports.products');
        Route::get('/exports/customers', [ExportController::class, 'customers'])->name('exports.customers');
        Route::get('/exports/sales', [ExportController::class, 'sales'])->name('exports.sales');
        Route::get('/exports/sales/print', [ExportController::class, 'printSales'])->name('exports.sales.print');
    });

    Route::middleware('permission:labels.print')->get('/labels', [LabelController::class, 'index'])->name('labels.index');

    Route::middleware('permission:branches.manage')->group(function () {
        Route::resource('branches', BranchController::class)->except(['show']);
    });

    Route::middleware('permission:warehouses.manage')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });

    Route::middleware('permission:transfers.view')->get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index');
    Route::middleware('permission:transfers.manage')->group(function () {
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store');
        Route::post('/transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
    });

    Route::middleware('permission:expenses.view')->get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    Route::middleware('permission:promotions.manage')->group(function () {
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::get('/promotions/create', [PromotionController::class, 'create'])->name('promotions.create');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::delete('/promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    });

    Route::middleware('permission:activity.view')->get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::middleware('permission:insights.view')->get('/insights', InsightController::class)->name('insights.index');
});
