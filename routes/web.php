<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ContactController;
use App\Http\Middleware\SetUserLocale;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierOrderController;
use App\Http\Controllers\WarehouseInvoiceController;
use App\Http\Controllers\StockValueController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\ResellerContactController;
use App\Http\Controllers\ResellerStockDeliveryController;
use App\Http\Controllers\ResellerSalesReportController;


Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::middleware(['auth', SetUserLocale::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/scanner', function () {
        return view('scanner');
    })->name('scanner');

    Route::middleware(['role:admin'])->group(function () {
        Route::resource('roles', RoleController::class)->parameters(['roles' => 'role']);
        Route::resource('users', UserController::class);
        Route::resource('stores', StoreController::class);
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
        Route::resource('brands', BrandController::class);
        Route::resource('products', ProductController::class);

        // Gestion des catégories
        Route::post('products/{product}/categories/attach', [ProductController::class, 'attachCategory'])->name('products.categories.attach');
        Route::delete('products/{product}/categories/{category}', [ProductController::class, 'detachCategory'])->name('products.categories.detach');

        // Gestion des suppliers
        Route::post('products/{product}/suppliers/attach', [ProductController::class, 'attachSupplier'])->name('products.suppliers.attach');
        Route::delete('products/{product}/suppliers/{supplier}', [ProductController::class, 'detachSupplier'])->name('products.suppliers.detach');
        Route::put('products/{product}/suppliers/{supplier}/price', [ProductController::class, 'updateSupplierPrice'])->name('products.suppliers.updatePrice');
        Route::put('/suppliers/{supplier}/products/{product}/purchase-price', [SupplierController::class, 'updatePurchasePrice'])->name('suppliers.updatePurchasePrice');

        // Stock Value
        Route::get('stock-value', [App\Http\Controllers\StockValueController::class, 'index'])->name('stock-value');
        Route::get('stock-value/{product}/lots', [StockValueController::class, 'lots'])->name('stock-value.lots');


        // Stores
        Route::post('products/{product}/stores/attach', [ProductController::class, 'attachStore'])->name('products.stores.attach');
        Route::delete('products/{product}/stores/{store}', [ProductController::class, 'detachStore'])->name('products.stores.detach');
        Route::put('products/{product}/stores/{store}/stock', [ProductController::class, 'updateStoreStock'])->name('products.stores.updateStock');
        Route::put('products/{product}/stores/{store}/stock', [ProductController::class, 'updateStoreStock'])->name('products.stores.updateStock');

        Route::prefix('suppliers/{supplier}')->group(function() {
            Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
            Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
            Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        });

        Route::put('products/{product}/descriptions', [ProductController::class, 'updateDescriptions'])->name('products.descriptions.update');
        Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');

        Route::resource('stock-movements', StockMovementController::class)->only([
            'index', 'create', 'store'
        ]);
        Route::put('stock-movements/{movement}/receive', [StockMovementController::class, 'receive'])->name('stock-movements.receive');
        Route::put('stock-movements/{movement}/cancel', [StockMovementController::class, 'cancel'])->name('stock-movements.cancel');
        Route::get('stock-movements/{movement}', [StockMovementController::class, 'show'])->name('stock-movements.show');
        Route::get('stock-movements/{movement}/pdf', [StockMovementController::class, 'pdf'])->name('stock-movements.pdf');

        Route::prefix('warehouse/invoices')->name('warehouse-invoices.')->group(function () {
            Route::get('/', [WarehouseInvoiceController::class, 'index'])->name('index');
            Route::get('/create', [WarehouseInvoiceController::class, 'create'])->name('create');
            Route::post('/', [WarehouseInvoiceController::class, 'store'])->name('store');
            Route::get('/{invoice}/edit', [WarehouseInvoiceController::class, 'edit'])->name('edit');
            Route::put('/{invoice}', [WarehouseInvoiceController::class, 'update'])->name('update');
            Route::delete('/{invoice}/{file}', [WarehouseInvoiceController::class, 'deleteFile'])->name('delete-file');
            Route::post('{invoice}/upload-files', [WarehouseInvoiceController::class, 'uploadFiles'])->name('upload-files');
            Route::get('/bills', [WarehouseInvoiceController::class, 'bills'])->name('billsoverview');
        });

        // Routes commandes fournisseurs
        Route::prefix('suppliers/{supplier}')->group(function () {
            // Création d'une commande
            Route::get('orders/create', [SupplierOrderController::class, 'create'])->name('supplier-orders.create');
            Route::post('orders', [SupplierOrderController::class, 'store'])->name('supplier-orders.store');
            // 👉 Validation de la commande (en attente → en attente de réception)
            Route::put('orders/{order}/validate', [SupplierOrderController::class, 'validateOrder'])->name('supplier-orders.validate');

            // Consultation / édition d'une commande
            Route::get('orders/{order}', [SupplierOrderController::class, 'show'])->name('supplier-orders.show');
            Route::get('orders/{order}/edit', [SupplierOrderController::class, 'edit'])->name('supplier-orders.edit');
            Route::put('orders/{order}', [SupplierOrderController::class, 'update'])->name('supplier-orders.update');

            // Réception de la commande
            // Formulaire pour la réception
            Route::get('orders/{order}/reception', [SupplierOrderController::class, 'receptionForm'])
                ->name('supplier-orders.reception');

            // Stocker la réception
            Route::post('orders/{order}/reception', [SupplierOrderController::class, 'storeReception'])
                ->name('supplier-orders.storeReception');
            // Génération PDF
            Route::get('orders/{order}/pdf', [SupplierOrderController::class, 'generatePdf'])->name('supplier-orders.pdf');

        });


        Route::resource('resellers', ResellerController::class);
        Route::post('resellers/{reseller}/contacts', [ResellerContactController::class, 'store'])->name('resellers.contacts.store');
        Route::delete('resellers/{reseller}/contacts/{contact}', [ResellerContactController::class, 'destroy'])->name('resellers.contacts.destroy');
        Route::get('resellers/{reseller}/deliveries/create', [ResellerStockDeliveryController::class, 'create'])->name('resellers.deliveries.create');
        Route::post('resellers/{reseller}/deliveries', [ResellerStockDeliveryController::class, 'store'])->name('resellers.deliveries.store');
        Route::get('resellers/{reseller}/reports/create', [ResellerSalesReportController::class, 'create'])->name('resellers.reports.create');
        Route::post('resellers/{reseller}/reports', [ResellerSalesReportController::class, 'store'])->name('resellers.reports.store');
        Route::get('resellers/{reseller}/reports/{report}', [ResellerSalesReportController::class, 'show'])->name('resellers.reports.show');
        Route::get('resellers/{reseller}/deliveries/{delivery}/edit', [ResellerStockDeliveryController::class, 'edit'])
            ->name('reseller-stock-deliveries.edit');

        Route::get('resellers/{reseller}/deliveries/{delivery}/edit', [ResellerStockDeliveryController::class, 'edit'])
            ->name('reseller-stock-deliveries.edit');

        Route::get('resellers/{reseller}/deliveries/{delivery}', [ResellerStockDeliveryController::class, 'show'])
            ->name('reseller-stock-deliveries.show');

        Route::put('resellers/{reseller}/deliveries/{delivery}', [ResellerStockDeliveryController::class, 'update'])
            ->name('reseller-stock-deliveries.update');

        
    });

});
