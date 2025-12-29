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
use App\Http\Controllers\ResellerStockReturnController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\StoreDashboardController;
use App\Http\Controllers\SaleReportController;
use App\Http\Controllers\RefillController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\Financial\FinancialAccountController;
use App\Http\Controllers\Financial\FinancialTransactionController;
use App\Http\Controllers\Financial\FinancialPaymentMethodController;
use App\Http\Controllers\Financial\FinancialDashboardController;
use App\Http\Controllers\Financial\FinancialJournalController;
use App\Http\Controllers\Financial\FinancialShiftController;
use App\Http\Controllers\Financial\GeneralInvoiceController;
use App\Http\Controllers\POS\SyncController;
use App\Http\Controllers\POS\ShiftController;
use App\Http\Controllers\POS\ExchangeController;
use App\Http\Controllers\VariationTypeController;
use App\Http\Controllers\VariationValueController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BlogTagController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\GiftBoxController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Factory\FactoryDashboardController;
use App\Http\Controllers\Factory\FactorySupplierController;
use App\Http\Controllers\Factory\RawMaterialController;
use App\Http\Controllers\Factory\RawMaterialInventoryController;
use App\Http\Controllers\Factory\RecipeController;
use App\Http\Controllers\Factory\ProductionController;
use App\Http\Controllers\BI\BIDashboardController;
use App\Http\Controllers\Reception\ReceptionController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ExchangeController as BOExchangeController;

// ===========================================
// PWA Reception Routes (outside auth middleware)
// ===========================================
Route::prefix('reception')->group(function () {
    // Public routes
    Route::get('/', [ReceptionController::class, 'loginForm'])->name('reception.login');
    Route::post('/auth', [ReceptionController::class, 'authenticate'])->name('reception.auth');
    Route::post('/logout', [ReceptionController::class, 'logout'])->name('reception.logout');

    // Protected routes (PIN session)
    Route::middleware('reception.auth')->group(function () {
        Route::get('/home', [ReceptionController::class, 'home'])->name('reception.home');

        // Supplier Orders
        Route::get('/orders', [ReceptionController::class, 'ordersList'])->name('reception.orders');
        Route::get('/orders/{order}', [ReceptionController::class, 'orderShow'])->name('reception.orders.show');
        Route::post('/orders/{order}/receive-item', [ReceptionController::class, 'receiveItem'])->name('reception.orders.receive-item');
        Route::post('/orders/{order}/finalize', [ReceptionController::class, 'finalizeOrder'])->name('reception.orders.finalize');

        // Refill
        Route::get('/refill', [ReceptionController::class, 'refillSuppliers'])->name('reception.refill');
        Route::get('/refill/{supplier}', [ReceptionController::class, 'refillProducts'])->name('reception.refill.products');
        Route::post('/refill/{supplier}/store', [ReceptionController::class, 'storeRefill'])->name('reception.refill.store');

        // Supplier Returns
        Route::get('/returns', [ReceptionController::class, 'returnSuppliers'])->name('reception.returns');
        Route::get('/returns/{supplier}', [ReceptionController::class, 'returnProducts'])->name('reception.returns.products');
        Route::post('/returns/{supplier}/store', [ReceptionController::class, 'storeReturn'])->name('reception.returns.store');

        // Check Price (barcode scanner)
        Route::get('/check-price', [ReceptionController::class, 'checkPrice'])->name('reception.check-price');
        Route::post('/check-price/lookup', [ReceptionController::class, 'lookupBarcode'])->name('reception.check-price.lookup');

        // Stock Transfers
        Route::get('/transfers', [ReceptionController::class, 'transfersList'])->name('reception.transfers');
        Route::get('/transfers/create', [ReceptionController::class, 'transferCreate'])->name('reception.transfers.create');
        Route::post('/transfers/products', [ReceptionController::class, 'transferProducts'])->name('reception.transfers.products');
        Route::post('/transfers/store', [ReceptionController::class, 'storeTransfer'])->name('reception.transfers.store');
        Route::get('/transfers/{movement}', [ReceptionController::class, 'transferShow'])->name('reception.transfers.show');
        Route::post('/transfers/{movement}/receive', [ReceptionController::class, 'receiveTransfer'])->name('reception.transfers.receive');
    });
});

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/track-url', function (\Illuminate\Http\Request $request) {
    $url = $request->input('url');
    if ($url) {
        // Récupérer l'historique existant ou initialiser
        //$history = session('history', []);
        $history =  $_SESSION['url_history'] ?? [];

        // Ajouter l'URL si ce n'est pas déjà la dernière entrée
        if (empty($history) || end($history) !== $url) {
            $history[] = $url;
        }

        // Garder uniquement les 10 dernières URLs
        if (count($history) > 10) {
            array_shift($history);
        }

        // Enregistrer en session
        //session(['history' => $history]);
        //session()->save();
        $_SESSION['url_history'] = $history;
    }

    // Récupérer l'historique réel depuis la session après modification
    $currentHistory = $_SESSION['url_history'];

    return response()->json([
        'status' => 'ok',
        'history' => $currentHistory,
    ]);
})->name('track-url')->middleware('web');



Route::middleware(['auth', SetUserLocale::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/products-issues', [DashboardController::class, 'productsWithIssues'])->name('dashboard.products-issues');
    Route::get('/dashboard/daily-sales/{store}', [DashboardController::class, 'dailySales'])->name('dashboard.daily-sales');

    Route::get('/scanner', function () {
        return view('scanner');
    })->name('scanner');
    
    Route::get('products/search', [ProductController::class, 'search'])->name('products.search'); // Ajax recherche EAN / nom

    Route::middleware(['role:admin'])->group(function () {
        Route::resource('roles', RoleController::class)->parameters(['roles' => 'role']);
        Route::resource('users', UserController::class);
        Route::resource('stores', StoreController::class);
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
        Route::resource('brands', BrandController::class);
        Route::get('products/check-ean', [ProductController::class, 'checkEan'])->name('products.check-ean');
        Route::resource('products', ProductController::class);
        Route::post('products/{product}/photos', [ProductController::class, 'uploadPhotos'])->name('products.photos.upload');
        Route::delete('products/{product}/photos/{photo}', [ProductController::class, 'deletePhoto'])->name('products.photos.delete');
        Route::post('products/{product}/photos/{photo}/set-primary', [ProductController::class, 'setPrimaryPhoto'])->name('products.photos.setPrimary');
        Route::get('products/{product}/variations', [ProductController::class, 'variationsIndex'])->name('products.variations.index');
        Route::post('products/{product}/variations', [ProductController::class, 'variationsStore'])->name('products.variations.store');
        Route::put('products/{product}/variations/{variation}', [ProductController::class, 'variationsUpdate'])->name('products.variations.update');
        Route::delete('products/{product}/variations/{variation}', [ProductController::class, 'variationsDestroy'])->name('products.variations.destroy');
        Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');

        // Gift Boxes
        Route::resource('gift-boxes', GiftBoxController::class);
        Route::post('gift-boxes/{giftBox}/images', [GiftBoxController::class, 'uploadImage'])->name('gift-boxes.images.upload');
        Route::delete('gift-boxes/{giftBox}/images/{image}', [GiftBoxController::class, 'deleteImage'])->name('gift-boxes.images.delete');
        Route::post('gift-boxes/{giftBox}/images/{image}/set-primary', [GiftBoxController::class, 'setPrimaryImage'])->name('gift-boxes.images.setPrimary');
        Route::post('gift-boxes/{giftBox}/images/reorder', [GiftBoxController::class, 'reorderImages'])->name('gift-boxes.images.reorder');
        Route::post('gift-boxes/{giftBox}/categories/attach', [GiftBoxController::class, 'attachCategory'])->name('gift-boxes.categories.attach');
        Route::delete('gift-boxes/{giftBox}/categories/{category}', [GiftBoxController::class, 'detachCategory'])->name('gift-boxes.categories.detach');
        Route::post('gift-boxes/{giftBox}/products/attach', [GiftBoxController::class, 'attachProduct'])->name('gift-boxes.products.attach');
        Route::delete('gift-boxes/{giftBox}/products/{product}', [GiftBoxController::class, 'detachProduct'])->name('gift-boxes.products.detach');
        Route::put('gift-boxes/{giftBox}/products/{product}/quantity', [GiftBoxController::class, 'updateProductQuantity'])->name('gift-boxes.products.updateQuantity');

        // Gift Cards
        Route::resource('gift-cards', GiftCardController::class);
        Route::post('gift-cards/{giftCard}/categories/attach', [GiftCardController::class, 'attachCategory'])->name('gift-cards.categories.attach');
        Route::delete('gift-cards/{giftCard}/categories/{category}', [GiftCardController::class, 'detachCategory'])->name('gift-cards.categories.detach');

        // Inventory Management
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
        Route::post('inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
        Route::get('inventory/confirm', [InventoryController::class, 'confirm'])->name('inventory.confirm');
        Route::post('inventory/apply', [InventoryController::class, 'apply'])->name('inventory.apply');
        Route::post('inventory/cancel', [InventoryController::class, 'cancel'])->name('inventory.cancel');

        Route::get('variation-types/{type}/values', [VariationTypeController::class, 'values'])->name('variation-types.values'); // Ajax

        // Gestion des catégories
        Route::post('products/{product}/categories/attach', [ProductController::class, 'attachCategory'])->name('products.categories.attach');
        Route::delete('products/{product}/categories/{category}', [ProductController::class, 'detachCategory'])->name('products.categories.detach');

        // Gestion des suppliers
        Route::post('products/{product}/suppliers/attach', [ProductController::class, 'attachSupplier'])
            ->name('products.suppliers.attach');

        Route::delete('products/{product}/suppliers/{supplier}', [ProductController::class, 'detachSupplier'])
            ->name('products.suppliers.detach');

        // 🔧 FIX: ajouter {supplier} et supprimer l’espace avant price
        Route::put('products/{product}/suppliers/{supplier}/price', [ProductController::class, 'updateSupplierPrice'])
            ->name('products.suppliers.updatePrice');

        // (tu peux garder aussi cette route côté SupplierController si tu l’utilises ailleurs)
        Route::put('/suppliers/{supplier}/products/{product}/purchase-price', [SupplierController::class, 'updatePurchasePrice'])
            ->name('suppliers.updatePurchasePrice');
        Route::get('/supplier-orders/overview', [SupplierOrderController::class, 'overview'])->name('supplier-orders.overview');
        Route::get('suppliers/{supplier}/sale-reports/create', [SaleReportController::class, 'create'])->name('sale-reports.create');
        Route::prefix('suppliers/{supplier}')->group(function () {
            Route::get('sale-reports', [SaleReportController::class, 'index'])->name('sale-reports.index');
            Route::get('sale-reports/create', [SaleReportController::class, 'create'])->name('sale-reports.create');
            Route::get('sale-reports/create/step2', [SaleReportController::class, 'createStep2'])->name('sale-reports.create.step2');
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

        Route::resource('hero-slides', \App\Http\Controllers\HeroSlideController::class)->except('show')->names('hero-slides');
        
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
        Route::get('/stocks/reseller', [StockController::class, 'reseller'])->name('stocks.reseller');

        Route::resource('stock-movements', StockMovementController::class)->only([
            'index', 'create', 'store'
        ]);
        Route::put('stock-movements/{movement}/receive', [StockMovementController::class, 'receive'])->name('stock-movements.receive');
        Route::put('stock-movements/{movement}/cancel', [StockMovementController::class, 'cancel'])->name('stock-movements.cancel');
        Route::get('stock-movements/{movement}', [StockMovementController::class, 'show'])->name('stock-movements.show');
        Route::get('stock-movements/{movement}/pdf', [StockMovementController::class, 'pdf'])->name('stock-movements.pdf');
        Route::get('stock-movements/{movement}/invoice', [StockMovementController::class, 'downloadInvoice'])->name('stock-movements.invoice');

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
            Route::get('orders/stock/{store}', [SupplierOrderController::class, 'getProductsStock'])->name('supplier-orders.stock');
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

            // Annulation de commande
            Route::delete('orders/{order}', [SupplierOrderController::class, 'destroy'])
                ->name('supplier-orders.destroy');
            Route::put('orders/{order}/revert-to-pending', [SupplierOrderController::class, 'revertToPending'])
                ->name('supplier-orders.revertToPending');

            Route::get('refills', [RefillController::class, 'index'])->name('refills.index');
            Route::get('refills/{refill}', [RefillController::class, 'show'])->name('refills.show');
            Route::put('refills/{refill}/quantities', [RefillController::class, 'updateQuantities'])->name('refills.updateQuantities');

            // Formulaire réception refill
            Route::get('refills/reception/create', [RefillController::class, 'receptionForm'])->name('refills.reception.form');
            Route::post('refills/reception', [RefillController::class, 'storeReception'])->name('refills.reception.store');

            Route::post('sale-reports/{saleReport}/send-telegram', [SaleReportController::class, 'doSendReportTelegram'])   ->name('sale-reports.send.telegram');

            // Supplier Returns (consignment)
            Route::get('returns', [SupplierReturnController::class, 'index'])->name('supplier-returns.index');
            Route::get('returns/create', [SupplierReturnController::class, 'create'])->name('supplier-returns.create');
            Route::post('returns', [SupplierReturnController::class, 'store'])->name('supplier-returns.store');
            Route::get('returns/{return}', [SupplierReturnController::class, 'show'])->name('supplier-returns.show');
            Route::get('returns/{return}/edit', [SupplierReturnController::class, 'edit'])->name('supplier-returns.edit');
            Route::put('returns/{return}', [SupplierReturnController::class, 'update'])->name('supplier-returns.update');
            Route::post('returns/{return}/validate', [SupplierReturnController::class, 'validateReturn'])->name('supplier-returns.validate');
            Route::get('returns/{return}/pdf', [SupplierReturnController::class, 'downloadPdf'])->name('supplier-returns.pdf');
            Route::delete('returns/{return}', [SupplierReturnController::class, 'destroy'])->name('supplier-returns.destroy');
            Route::get('returns/products/{store}', [SupplierReturnController::class, 'getProductsWithStock'])->name('supplier-returns.products');
        });


        Route::resource('resellers', ResellerController::class);
        Route::post('resellers/{reseller}/update-stock', [ResellerController::class, 'updateStock'])->name('resellers.update-stock');
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

        // Reseller Stock Returns (Retours de produits)
        Route::get('resellers/{reseller}/returns/create', [ResellerStockReturnController::class, 'create'])->name('resellers.returns.create');
        Route::post('resellers/{reseller}/returns', [ResellerStockReturnController::class, 'store'])->name('resellers.returns.store');
        Route::get('resellers/{reseller}/returns/{return}', [ResellerStockReturnController::class, 'show'])->name('resellers.returns.show');
        Route::post('resellers/{reseller}/returns/{return}/validate', [ResellerStockReturnController::class, 'validateReturn'])->name('resellers.returns.validate');
        Route::post('resellers/{reseller}/returns/{return}/cancel', [ResellerStockReturnController::class, 'cancel'])->name('resellers.returns.cancel');

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

        Route::resource('pages', \App\Http\Controllers\PageController::class)
            ->names('admin.pages');
        Route::patch('pages/{page}/toggle', [\App\Http\Controllers\PageController::class, 'toggle'])
            ->name('admin.pages.toggle');

        // Promotion bar
        Route::get('promotion-bar', [\App\Http\Controllers\PromotionBarController::class, 'index'])
            ->name('promotion-bar.index');
        Route::put('promotion-bar', [\App\Http\Controllers\PromotionBarController::class, 'update'])
            ->name('promotion-bar.update');
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
        Route::get('shifts', [FinancialShiftController::class, 'index'])->name('shifts.index');
        Route::get('shifts/{shift}', [FinancialShiftController::class, 'show'])->name('shifts.show');
        Route::get('general-invoices/export', [GeneralInvoiceController::class, 'export'])->name('general-invoices.export');
        Route::get('general-invoices/{generalInvoice}/attachment', [GeneralInvoiceController::class, 'downloadAttachment'])->name('general-invoices.attachment');
        Route::resource('general-invoices', GeneralInvoiceController::class);
        Route::post('general-invoices/{generalInvoice}/mark-as-paid', [GeneralInvoiceController::class, 'markAsPaid'])->name('general-invoices.mark-as-paid');
    });

    Route::resource('variation-types', \App\Http\Controllers\VariationTypeController::class);
    Route::resource('variation-values', \App\Http\Controllers\VariationValueController::class);
    Route::resource('invoice-categories', \App\Http\Controllers\InvoiceCategoryController::class);

});


// ### POS ###
Route::get('/pos', function () {
    return view('pos.index');
});

Route::prefix('api/pos')->middleware('api')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::post('sync', [SyncController::class, 'sync']);
    Route::get('users', [SyncController::class, 'users']);
    Route::get('catalog/{storeId}', [SyncController::class, 'catalog']);
    Route::get('search/{storeId}', [SyncController::class, 'search']);

    // Shifts
    Route::get('shifts/current/{userId}', [ShiftController::class, 'currentShift']);
    Route::get('shifts/expected-cash/{userId}', [ShiftController::class, 'expectedCash']);
    Route::post('shifts/start', [ShiftController::class, 'start']);
    Route::post('shifts/end', [ShiftController::class, 'end']);
    Route::post('shifts/change-user', [ShiftController::class, 'changeUser']);
    Route::post('shifts/sync', [SyncController::class, 'shifts']);
    Route::post('shifts/sales-by-date', [ShiftController::class, 'salesByDate']);

    Route::post('sales/sync', [SyncController::class, 'sales']);

    // Exchange
    Route::get('exchange/lookup-sale', [ExchangeController::class, 'lookupSale']);
    Route::post('exchange/process', [ExchangeController::class, 'process']);

    // Vouchers
    Route::get('voucher/validate', [ExchangeController::class, 'validateVoucher']);
    Route::post('voucher/apply', [ExchangeController::class, 'applyVoucher']);
});

// Blog Routes
Route::middleware(['auth'])->prefix('blog')->name('blog.')->group(function () {
    // Blog Posts
    Route::resource('posts', BlogPostController::class);
    Route::delete('posts/{post}/image', [BlogPostController::class, 'deleteImage'])->name('posts.deleteImage');

    // Blog Categories
    Route::resource('categories', BlogCategoryController::class)->except(['show']);

    // Blog Tags
    Route::resource('tags', BlogTagController::class)->except(['show']);
});

// Contact Messages Routes
Route::middleware(['auth'])->group(function () {
    Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::post('contact-messages/{contactMessage}/mark-as-read', [ContactMessageController::class, 'markAsRead'])->name('contact-messages.mark-as-read');
    Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
});

// Home Content Routes
Route::middleware(['auth'])->group(function () {
    Route::get('home-content', [App\Http\Controllers\HomeContentController::class, 'edit'])->name('home-content.edit');
    Route::put('home-content', [App\Http\Controllers\HomeContentController::class, 'update'])->name('home-content.update');
});

// Backup Routes
Route::middleware(['auth'])->group(function () {
    Route::get('backups', [App\Http\Controllers\BackupController::class, 'index'])->name('backups.index');
    Route::post('backups/create', [App\Http\Controllers\BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/download/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
    Route::delete('backups/{filename}', [App\Http\Controllers\BackupController::class, 'delete'])->name('backups.delete');
});

// Vouchers & Exchanges Routes
Route::middleware(['auth'])->group(function () {
    Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('vouchers/export', [VoucherController::class, 'export'])->name('vouchers.export');
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::post('vouchers/{voucher}/cancel', [VoucherController::class, 'cancel'])->name('vouchers.cancel');

    Route::get('exchanges', [BOExchangeController::class, 'index'])->name('exchanges.index');
    Route::get('exchanges/{exchange}', [BOExchangeController::class, 'show'])->name('exchanges.show');
});

// Factory Routes
Route::middleware(['auth', SetUserLocale::class])->prefix('factory')->name('factory.')->group(function () {
    // Dashboard
    Route::get('/', [FactoryDashboardController::class, 'index'])->name('dashboard');

    // Factory Suppliers
    Route::resource('suppliers', FactorySupplierController::class);

    // Raw Materials
    Route::get('raw-materials/search', [RawMaterialController::class, 'search'])->name('raw-materials.search');
    Route::resource('raw-materials', RawMaterialController::class);
    Route::post('raw-materials/{rawMaterial}/add-stock', [RawMaterialController::class, 'addStock'])->name('raw-materials.add-stock');
    Route::post('raw-materials/{rawMaterial}/adjust-stock', [RawMaterialController::class, 'adjustStock'])->name('raw-materials.adjust-stock');
    Route::post('raw-materials/{rawMaterial}/clone', [RawMaterialController::class, 'clone'])->name('raw-materials.clone');

    // Inventory
    Route::get('inventory', [RawMaterialInventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/export', [RawMaterialInventoryController::class, 'export'])->name('inventory.export');
    Route::post('inventory/import', [RawMaterialInventoryController::class, 'import'])->name('inventory.import');

    // Recipes
    Route::get('recipes/{recipe}/max-producible', [RecipeController::class, 'maxProducible'])->name('recipes.max-producible');
    Route::post('recipes/{recipe}/clone', [RecipeController::class, 'clone'])->name('recipes.clone');
    Route::resource('recipes', RecipeController::class);

    // Productions
    Route::resource('productions', ProductionController::class)->except(['edit', 'update']);
});

// BI Routes
Route::middleware(['auth', SetUserLocale::class])->prefix('bi')->name('bi.')->group(function () {
    Route::get('/', [BIDashboardController::class, 'index'])->name('dashboard');
});
