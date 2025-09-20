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
use App\Http\Controllers\ResellerInvoiceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\StoreDashboardController;
use App\Http\Controllers\SaleReportController;

use App\Http\Controllers\Financial\FinancialAccountController;
use App\Http\Controllers\Financial\FinancialTransactionController;
use App\Http\Controllers\Financial\FinancialPaymentMethodController;
use App\Http\Controllers\Financial\FinancialDashboardController;
use App\Http\Controllers\Financial\FinancialJournalController;
use App\Http\Controllers\Financial\GeneralInvoiceController;

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
        Route::put('products/{product}/ price', [ProductController::class, 'updateSupplierPrice'])->name('products.suppliers.updatePrice');
        Route::put('/suppliers/{supplier}/products/{product}/purchase-price', [SupplierController::class, 'updatePurchasePrice'])->name('suppliers.updatePurchasePrice');
        Route::get('/supplier-orders/overview', [SupplierOrderController::class, 'overview'])->name('supplier-orders.overview');
        Route::get('suppliers/{supplier}/sale-reports/create', [SaleReportController::class, 'create'])->name('sale-reports.create');
        Route::prefix('suppliers/{supplier}')->group(function () {
            Route::get('sale-reports', [SaleReportController::class, 'index'])->name('sale-reports.index');
            Route::get('sale-reports/create', [SaleReportController::class, 'create'])->name('sale-reports.create');
            Route::post('sale-reports', [SaleReportController::class, 'store'])->name('sale-reports.store');
            Route::get('sale-reports/{saleReport}', [SaleReportController::class, 'show'])->name('sale-reports.show');

            Route::get('sale-reports/{saleReport}/send', [SaleReportController::class, 'sendReport'])->name('sale-reports.send');
            Route::post('sale-reports/{saleReport}/send', [SaleReportController::class, 'doSendReport'])->name('sale-reports.doSend');


            // Passage à "invoiced"
            Route::put('sale-reports/{saleReport}/mark-invoiced', [SaleReportController::class, 'markInvoiced'])->name('sale-reports.markInvoiced');

            // Nouveau : créer une facture depuis un sale_report
            Route::get('sale-reports/{saleReport}/invoice/create', [SaleReportController::class, 'createInvoice'])->name('sale-reports.invoice.create');
            Route::post('sale-reports/{saleReport}/invoice', [SaleReportController::class, 'storeInvoice'])->name('sale-reports.invoice.store');

            // Nouveau : marquer la facture comme reçue et initier workflow paiement
            Route::put('sale-reports/{saleReport}/invoice/receive', [SaleReportController::class, 'receiveInvoice'])->name('sale-reports.invoice.receive');
        
            Route::get('sale-reports/{saleReport}/invoice-reception', [SaleReportController::class, 'invoiceReception'])
                ->name('sale-reports.invoiceReception');

            Route::post('sale-reports/{saleReport}/invoice-reception', [SaleReportController::class, 'storeInvoiceReception'])
                ->name('sale-reports.storeInvoiceReception');        

            // Marquer un rapport comme payé
            Route::post('sale-reports/{saleReport}/mark-as-paid', [SaleReportController::class, 'markAsPaid'])
                ->name('sale-reports.markAsPaid');
        });


        
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

        Route::prefix('reseller-invoices')->name('reseller-invoices.')->group(function () {
            Route::get('/', [ResellerInvoiceController::class, 'index'])->name('index');
            Route::get('/create', [ResellerInvoiceController::class, 'create'])->name('create');
            Route::post('/', [ResellerInvoiceController::class, 'store'])->name('store');
            Route::get('/{invoice}/edit', [ResellerInvoiceController::class, 'edit'])->name('edit');
            Route::put('/{invoice}', [ResellerInvoiceController::class, 'update'])->name('update');
            Route::delete('/{invoice}', [ResellerInvoiceController::class, 'destroy'])->name('destroy');
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
           
            // --- Réception de facture ---
            // Formulaire de réception de facture
            Route::get('orders/{order}/invoice-reception', [SupplierOrderController::class, 'receptionInvoiceForm'])
                ->name('supplier-orders.invoiceReception');

            // Stocker la réception de facture
            Route::post('orders/{order}/invoice-reception', [SupplierOrderController::class, 'storeInvoiceReception'])
                ->name('supplier-orders.storeInvoiceReception');

            Route::post('orders/{order}/mark-paid', [SupplierOrderController::class, 'markPaid'])
                ->name('supplier-orders.markAsPaid');
        });


        Route::resource('resellers', ResellerController::class);
        Route::post('resellers/{reseller}/contacts', [ResellerContactController::class, 'store'])->name('resellers.contacts.store');
        Route::delete('resellers/{reseller}/contacts/{contact}', [ResellerContactController::class, 'destroy'])->name('resellers.contacts.destroy');
        Route::get('resellers/{reseller}/deliveries/create', [ResellerStockDeliveryController::class, 'create'])->name('resellers.deliveries.create');
        Route::post('resellers/{reseller}/deliveries', [ResellerStockDeliveryController::class, 'store'])->name('resellers.deliveries.store');
        Route::get('resellers/{reseller}/reports/create', [ResellerSalesReportController::class, 'create'])->name('resellers.reports.create');
        Route::post('resellers/{reseller}/reports', [ResellerSalesReportController::class, 'store'])->name('resellers.reports.store');
        Route::get('resellers/{reseller}/reports/{report}', [ResellerSalesReportController::class, 'show'])->name('resellers.reports.show');
        Route::post('resellers/{reseller}/reports/{report}/payments', [ResellerSalesReportController::class, 'addPayment'])->name('resellers.report.addPayment');
        
        Route::get('resellers/{reseller}/deliveries/{delivery}/edit', [ResellerStockDeliveryController::class, 'edit'])
            ->name('reseller-stock-deliveries.edit');

        Route::get('resellers/{reseller}/deliveries/{delivery}/edit', [ResellerStockDeliveryController::class, 'edit'])
            ->name('reseller-stock-deliveries.edit');

        Route::get('resellers/{reseller}/deliveries/{delivery}', [ResellerStockDeliveryController::class, 'show'])
            ->name('reseller-stock-deliveries.show');

        Route::put('resellers/{reseller}/deliveries/{delivery}', [ResellerStockDeliveryController::class, 'update'])
            ->name('reseller-stock-deliveries.update');

        Route::get('/deliveries/{delivery}/invoice', [\App\Http\Controllers\ResellerInvoiceController::class, 'generateOrDownloadInvoice'])
            ->name('resellers.deliveries.invoice');
        Route::get('/resellers/{reseller}/reports/{report}/invoice', [ResellerSalesReportController::class, 'invoice'])->name('resellers.reports.invoice');

        Route::prefix('reseller-invoices')->name('reseller-invoices.')->group(function () {
            Route::get('/', [ResellerInvoiceController::class, 'index'])->name('index');
            Route::get('/{invoice}', [ResellerInvoiceController::class, 'show'])->name('show');
            Route::post('/{invoice}/payments', [ResellerInvoiceController::class, 'addPayment'])->name('addPayment');
        });

        Route::get('resellers/{reseller}/deliveries/create', [ResellerStockDeliveryController::class, 'create'])
            ->name('resellers.deliveries.create');

        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'show'])->name('invoices.download');
        Route::get('/invoices/{invoice}/view', [InvoiceController::class, 'stream'])->name('invoices.view');


        Route::prefix('stores/{site}')->name('stores.')->group(function () {
            // Journals (Transactions)
            Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
            Route::get('journals/create', [JournalController::class, 'create'])->name('journals.create');
            Route::post('journals', [JournalController::class, 'store'])->name('journals.store');
            Route::get('journals/{journal}', [JournalController::class, 'show'])->name('journals.show');
            Route::delete('journals/{journal}', [JournalController::class, 'destroy'])->name('journals.destroy');

            // Supplier Payments (Sorties liées aux fournisseurs)
            Route::get('payments', [SupplierPaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/create', [SupplierPaymentController::class, 'create'])->name('payments.create');
            Route::post('payments', [SupplierPaymentController::class, 'store'])->name('payments.store');
            Route::get('payments/{payment}/edit', [SupplierPaymentController::class, 'edit'])->name('payments.edit');
            Route::put('payments/{payment}', [SupplierPaymentController::class, 'update'])->name('payments.update');
            Route::delete('payments/{payment}', [SupplierPaymentController::class, 'destroy'])->name('payments.destroy');

            // Expenses (Dépenses générales)
            Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
            Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

            // Expense Categories (Catégories de dépenses)
            Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
            Route::get('expense-categories/create', [ExpenseCategoryController::class, 'create'])->name('expense-categories.create');
            Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
            Route::get('expense-categories/{category}/edit', [ExpenseCategoryController::class, 'edit'])->name('expense-categories.edit');
            Route::put('expense-categories/{category}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
            Route::delete('expense-categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

            Route::get('dashboard', [StoreDashboardController::class, 'index'])->name('dashboard.index');
        });
    });

    Route::get('/financial', [FinancialDashboardController::class, 'overviewInvoices'])->name('financial.overview');
    Route::prefix('financial/{store}')->name('financial.')->group(function () {
        Route::resource('accounts', FinancialAccountController::class);
        Route::resource('payment-methods', FinancialPaymentMethodController::class)->parameters(['payment-methods' => 'paymentMethod']);
        // Route pour exporter les transactions en Excel
        Route::get('transactions/export', [FinancialTransactionController::class, 'export'])->name('transactions.export');
        Route::resource('transactions', FinancialTransactionController::class);
        Route::get('journals', [FinancialJournalController::class, 'index'])->name('journals.index');
        Route::get('journals/{journal}', [FinancialJournalController::class, 'show'])->name('journals.show');
        Route::get('dashboard', [FinancialDashboardController::class, 'index'])->name('dashboard');

        // --- NOUVELLES ROUTES pour Factures générales ---
        Route::resource('general-invoices', GeneralInvoiceController::class);
    });
});
