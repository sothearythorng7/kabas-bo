# DIAGRAMMES ARCHITECTURE - PROJET KABAS

## 1. Architecture générale du système

```
┌─────────────────────────────────────────────────────────────────────┐
│                   BASE DE DONNÉES MYSQL                             │
│  ┌──────────────┬──────────────┬────────────────┬─────────────────┐ │
│  │  PRODUITS    │   STOCKS     │  COMPTABILITÉ  │   UTILISATEURS  │ │
│  │              │              │                │                 │ │
│  │ - products   │ - stock_     │ - financial_   │ - users         │ │
│  │ - brands     │   batches    │   accounts     │ - shifts        │ │
│  │ - categories │ - stock_     │ - financial_   │ - roles         │ │
│  │ - variations │   trans.     │   trans.       │ - permissions   │ │
│  │ - images     │ - stock_     │ - financial_   │                 │ │
│  │              │   movements  │   journals     │                 │ │
│  └──────────────┴──────────────┴────────────────┴─────────────────┘ │
│  ┌──────────────┬──────────────┬────────────────┬─────────────────┐ │
│  │ FOURNISSEURS │  REVENDEURS  │   VENTES       │  CONTENUS       │ │
│  │              │              │                │                 │ │
│  │ - suppliers  │ - resellers  │ - sales        │ - pages         │ │
│  │ - orders     │ - deliveries │ - sale_items   │ - blog_posts    │ │
│  │ - contacts   │ - invoices   │ - cash_trans.  │ - gift_boxes    │ │
│  │              │ - reports    │                │ - hero_slides   │ │
│  └──────────────┴──────────────┴────────────────┴─────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                               ↑ ↑ ↑
                    ╔══════════╩═╩═╩═════════════╗
                    │        LARAVEL 11          │
                    │      (Routes + Models)     │
                    ╚═════╤═════╤═════╤══════════╝
                          ↓     ↓     ↓
           ┌──────────────────┬───────────────┬──────────────────┐
           ↓                  ↓               ↓                  ↓
    ┌─────────────┐   ┌──────────────┐  ┌──────────┐   ┌──────────────┐
    │ BACKOFFICE  │   │   FRONT API  │  │   POS    │   │  DÉPENDANCES │
    │             │   │              │  │          │   │              │
    │ Blade Views │   │ JSON Endpoints  │ Vue 3 SPA│   │ Meilisearch  │
    │ Bootstrap 5 │   │ Sanctum tokens  │ IndexedDB│   │ Spatie Roles │
    │ Vanilla JS  │   │ JSON responses  │ Pinia    │   │ Sanctum      │
    └─────────────┘   └──────────────┘  └──────────┘   └──────────────┘
           ↓                  ↓               ↓
    ┌─────────────┐   ┌──────────────┐  ┌──────────┐
    │   Navigateur    │   │ External App   │  │ Browser  │
    │   Admin         │   │ (Vue/React)    │  │ SPA      │
    │ (Chrome/Firefox)│   │ or Native      │  │          │
    └─────────────┘   └──────────────┘  └──────────┘
```

---

## 2. Flux des routes et requêtes

```
╔════════════════════════════════════════════════════════════════════════╗
║                    ROUTEUR LARAVEL (routes/web.php)                    ║
╚════════════════════════════════════════════════════════════════════════╝

1. ROOT ROUTES
   GET  /                           → redirect /dashboard OU /login

2. AUTHENTICATION ROUTES
   POST /login                      → Auth::routes() (Laravel standard)
   POST /register
   GET  /home                       → HomeController@index

3. BACKOFFICE ROUTES (middleware: ['auth', 'SetUserLocale'])
   ┌─────────────────────────────────────────────────────────────┐
   │ GET  /dashboard                → DashboardController@index  │
   │ GET  /dashboard/products-issues → DashboardController@...  │
   │ GET  /scanner                   → Scanner view              │
   │ GET  /products/search           → ProductController@search  │
   └─────────────────────────────────────────────────────────────┘

4. ADMIN ROUTES (middleware: 'role:admin')
   ┌─ CATALOGUE ────────────────────────────────────────────────┐
   │ resources('products')              → ProductController     │
   │ resources('brands')                → BrandController       │
   │ resources('categories')            → CategoryController    │
   │ POST   /products/{id}/photos       → Upload images         │
   │ GET    /products/{id}/variations   → Variations CRUD       │
   └────────────────────────────────────────────────────────────┘

   ┌─ STOCKS ───────────────────────────────────────────────────┐
   │ resources('stock-movements')       → StockMovementCtrlr    │
   │ POST   /inventory/export           → InventoryController   │
   │ POST   /inventory/import                                   │
   │ GET    /stock-value                → StockValueController  │
   └────────────────────────────────────────────────────────────┘

   ┌─ FOURNISSEURS ─────────────────────────────────────────────┐
   │ resources('suppliers')             → SupplierController    │
   │ /suppliers/{supplier}/orders/*     → SupplierOrderCtrlr    │
   │ /suppliers/{supplier}/sale-reports → SaleReportController  │
   │ /suppliers/{supplier}/refills      → RefillController      │
   └────────────────────────────────────────────────────────────┘

   ┌─ REVENDEURS ───────────────────────────────────────────────┐
   │ resources('resellers')             → ResellerController    │
   │ /resellers/{id}/deliveries/*       → StockDeliveryCtrlr    │
   │ /resellers/{id}/reports/*          → SalesReportController │
   │ /reseller-invoices/*               → InvoiceController     │
   └────────────────────────────────────────────────────────────┘

   ┌─ FINANCES ─────────────────────────────────────────────────┐
   │ /financial/{store}/accounts/*      → FinancialAccountCtrlr │
   │ /financial/{store}/transactions/*  → TransactionController │
   │ /financial/{store}/journals        → FinancialJournalCtrlr │
   │ /financial/{store}/shifts          → FinancialShiftCtrlr   │
   └────────────────────────────────────────────────────────────┘

   ┌─ CONTENUS ─────────────────────────────────────────────────┐
   │ resources('pages')                 → PageController        │
   │ resources('blog.posts')            → BlogPostController    │
   │ resources('gift-boxes')            → GiftBoxController     │
   │ resources('gift-cards')            → GiftCardController    │
   │ resources('hero-slides')           → HeroSlideController   │
   └────────────────────────────────────────────────────────────┘

5. POS ROUTES
   ┌──────────────────────────────────────────────────────────────┐
   │ GET  /pos                        → view('pos.index')         │
   │                                    (SPA - Vue 3)              │
   │                                                               │
   │ API Routes (prefix: /api/pos)                                │
   │   GET    /products               → POS/ProductController     │
   │   POST   /sync                   → POS/SyncController        │
   │   GET    /users                  → SyncController@users      │
   │   GET    /catalog/{storeId}      → SyncController@catalog    │
   │   GET    /shifts/current/{userId}→ POS/ShiftController      │
   │   POST   /shifts/start           → ShiftController@start     │
   │   POST   /shifts/end             → ShiftController@end       │
   │   POST   /sales/sync             → SyncController@sales      │
   └──────────────────────────────────────────────────────────────┘

6. FRONT API ROUTES (routes/api.php)
   ┌──────────────────────────────────────────────────────────────┐
   │ POST   /login                    → Api/AuthController        │
   │ POST   /register                 → Api/AuthController        │
   │ GET    /categories               → Api/CategoryController    │
   │                                                               │
   │ Auth Sanctum Middleware ('auth:sanctum')                     │
   │   GET    /products               → Api/ProductController     │
   │   GET    /products/{id}                                      │
   │   POST   /cart                   → Api/OrderController       │
   │   POST   /checkout                                           │
   │   GET    /orders                                             │
   └──────────────────────────────────────────────────────────────┘
```

---

## 3. Contrôleurs et flux de traitement

### 3.1 ProductController (Backoffice - 22.4 KB)

```
ProductController
├── index(Request $request)
│   └─ Si q=search
│       └─ Scout Meilisearch
│   └─ Sinon
│       └─ SQL LIKE
│   └─ WITH: brand, stores, images count
│   └─ VIEW: products.index
│
├── create()
│   └─ Load: brands, suppliers, stores, categories
│   └─ VIEW: products.create
│
├── store(Request $request)
│   ├─ Validate: ean, price, name (multilingue)
│   ├─ DB::transaction()
│   │  ├─ Create Product
│   │  ├─ SetTranslation: name, slugs per locale
│   │  └─ Attach all stores with stock_quantity=0
│   └─ REDIRECT: products.edit
│
├── edit(Product $product)
│   ├─ Load: categories, suppliers, stores, images, variations
│   ├─ Build supplierPivot (prix achat)
│   └─ VIEW: products.edit (42.6 KB - très détaillé)
│
├── update(Product $product, Request $request)
│   ├─ Validate et MAJ produit
│   ├─ Scout réindexation
│   └─ REDIRECT: products.edit
│
├── destroy(Product $product)
│   └─ DELETE + REDIRECT: products.index
│
├── uploadPhotos(Product $product, Request $request)
│   ├─ Validate: images files
│   ├─ Store in Storage
│   ├─ Create ProductImage records
│   └─ JSON response
│
├── deletePhoto(Product $product, ProductImage $photo)
│   ├─ Delete file
│   ├─ Delete record
│   └─ JSON response
│
├── attachCategory(Product $product, Request $request)
│   ├─ Validate category_id
│   ├─ Check if already attached
│   ├─ Attach with timestamps
│   └─ JSON response
│
├── attachSupplier(Product $product, Request $request)
│   ├─ Validate supplier_id, purchase_price
│   ├─ Attach with pivot purchase_price
│   └─ JSON response
│
├── updateSupplierPrice(Product $product, Supplier $supplier, Request $request)
│   ├─ Validate price
│   ├─ Update pivot purchase_price
│   └─ JSON response
│
├── attachStore(Product $product, Request $request)
│   ├─ Validate store_id
│   ├─ Attach with alert_stock_quantity
│   └─ JSON response
│
├── updateStoreStock(Product $product, Store $store, Request $request)
│   ├─ Validate quantity
│   ├─ Update pivot stock_quantity
│   └─ JSON response
│
├── search(Request $request)
│   ├─ EAN lookup (quick search)
│   ├─ Name search
│   └─ JSON products
│
└── updateDescriptions(Product $product, Request $request)
    ├─ Validate: description (multilingue)
    ├─ SetTranslation per locale
    └─ JSON response
```

---

### 3.2 POS/SyncController (Ventes - 17.4 KB)

```
SyncController
├── users()
│   ├─ SELECT: id, name, pin_code, store_id
│   ├─ WHERE: pin_code NOT NULL
│   └─ JSON array [user1, user2, ...]
│
├── catalog($storeId)
│   ├─ Product WHERE is_active=true
│   ├─ WITH: brand, images, categories
│   ├─ WHERE: store_id matching
│   └─ JSON products with category tree
│
├── sync(Request $request)
│   ├─ Validate data structure
│   ├─ Load users + products into memory
│   └─ JSON response {status, message}
│
└── sales(Request $request)  [CRUCIAL LOGIC]
    │
    ├─ Validate structure:
    │  {
    │    shift_id,
    │    sales: [
    │      {
    │        id,
    │        payment_type,
    │        total,
    │        discounts,
    │        split_payments: [
    │          { payment_type, amount },
    │          ...
    │        ],
    │        items: [
    │          {
    │            product_id,
    │            quantity,
    │            price,
    │            is_delivery,
    │            delivery_address
    │          },
    │          ...
    │        ]
    │      },
    │      ...
    │    ]
    │  }
    │
    ├─ Get shift, store, user from DB
    │
    ├─ Load FinancialAccount (701 - Ventes)
    ├─ Load PaymentMethods into map
    │
    ├─ DB::transaction() {
    │
    │  FOR EACH sale:
    │    1. Create Sale record
    │       {
    │         shift_id,
    │         store_id,
    │         payment_type,
    │         total,
    │         discounts,
    │         split_payments,
    │         synced_at
    │       }
    │
    │    2. FOR EACH item:
    │       a. Create SaleItem
    │          {
    │            sale_id,
    │            product_id,
    │            quantity,
    │            price,
    │            discounts,
    │            is_delivery,
    │            delivery_address
    │          }
    │
    │       b. IF NOT is_delivery:
    │          - Get StockBatch (FIFO: ordered by created_at)
    │          - WHERE: store_id, product_id, quantity > 0
    │          - FOR EACH batch:
    │            * Decrement quantity
    │            * Create StockTransaction (type: 'out')
    │              { stock_batch_id, store_id, product_id, 
    │                type: 'out', quantity, reason: 'sale', 
    │                sale_id, shift_id }
    │
    │    3. Create FinancialTransaction
    │       a. Get last transaction balance
    │       b. Calculate:
    │          - amount = sale.total
    │          - direction = 'credit'
    │          - balance_before = last.balance_after
    │          - balance_after = balance_before + amount
    │       c. Link to account 701
    │       d. Create journal entry
    │
    │    4. Log warning si stock insuffisant
    │
    │  RETURN:
    │    {
    │      status: 'success',
    │      synced_sales: [...],
    │      message: 'X sales synced'
    │    }
    │
    └─ END transaction
```

---

## 4. Flux de données complet

### 4.1 Création d'un produit en Backoffice

```
Admin → /products/create
         ↓
         ProductController::create()
         ├─ Load brands, suppliers, stores
         ├─ Build category options
         └─ VIEW products.create
         ↓
Admin remplit le formulaire
├─ EAN (unique)
├─ Noms multilingues
├─ Prix retail + B2B
├─ Marque
├─ Images
└─ Submit
         ↓
         POST /products
         ↓
         ProductController::store(Request $request)
         ├─ Validate (EAN unique, price numeric, etc.)
         ├─ DB::transaction()
         │  ├─ Create Product row
         │  │  {
         │  │    ean, name: {...}, description: {...},
         │  │    slugs: {...}, price, price_btob,
         │  │    brand_id, is_active, is_best_seller,
         │  │    is_resalable, allow_overselling
         │  │  }
         │  │
         │  ├─ FOR EACH locale:
         │  │  └─ setTranslation('name', locale, ...)
         │  │     setTranslation('slugs', locale, ...)
         │  │
         │  └─ Attach to all stores
         │     (stock_quantity = 0, alert_stock_quantity = 0)
         │
         ├─ Product::create() saved
         ├─ Scout reindex (Meilisearch)
         └─ Redirect to edit
         ↓
         ProductController::edit($product)
         ├─ Load relations
         └─ VIEW products.edit (complet avec tabs)
         ↓
Admin peut désormais:
├─ Upload images
├─ Lier catégories
├─ Lier fournisseurs (avec prix achat)
├─ Lier magasins
├─ Ajouter variations
└─ Éditer descriptions
         ↓
Chaque changement met à jour Scout
         ↓
POS récupère via /api/pos/catalog/{storeId}
         ↓
Produit visible en caisse!
```

---

### 4.2 Vente en POS (Offline → Sync)

```
Caissier → /pos
           ↓
           view('pos.index') [Vue 3 SPA]
           ├─ Load LocalStorage cache
           │  ├─ pos_catalog_cache_v1_store_{storeId}
           │  └─ pos_category_tree_store_{storeId}
           └─ Load IndexedDB
              ├─ UsersTable
              ├─ CatalogTable
              └─ PaymentsTable
           ↓
Écran Login
├─ [Caissier tape son PIN]
└─ POST /api/pos/users (offline depuis cache)
           ↓
Écran Dashboard
├─ Affiche menu: Produits, Shift, Journal
├─ Bouton "Force Sync" optionnel
└─ Bouton "Cash In/Out"
           ↓
Caissier clique "Produits"
           ↓
Écran Products (depuis LocalStorage/IndexedDB cache)
├─ Affiche catégories hiérarchiques
├─ Caissier choisit une catégorie
├─ Produits filtrés s'affichent
│  ├─ Nom
│  ├─ Prix
│  ├─ Image principale
│  └─ Stock disponible
└─ Caissier clique sur un produit
           ↓
Ajoute au panier
├─ Quantity: X
├─ Price: XX.XX
└─ Buttons: -, +, Remove
           ↓
Clique "Valider vente"
           ↓
Écran Sales (Panier final)
├─ Liste articles
├─ Total brut
├─ Champ remise (optionnel)
├─ Paiement multiple:
│  ├─ Montant cash: 50
│  ├─ Montant carte: 25
│  └─ Total: 75
└─ Button: "Valider paiement"
           ↓
JavaScript crée objet Sale:
│
│ {
│   id: uuid(),
│   shift_id: current_shift.id,
│   payment_type: 'split',
│   total: 75,
│   discounts: [...],
│   split_payments: [
│     { payment_type: 'cash', amount: 50 },
│     { payment_type: 'card', amount: 25 }
│   ],
│   items: [
│     {
│       product_id: 123,
│       quantity: 2,
│       price: 37.50,
│       discounts: [...],
│       is_delivery: false
│     },
│     ...
│   ]
│ }
│
└─ Stocké en LocalStorage + IndexedDB
           ↓
Si connexion réseau:
  ├─ POST /api/pos/sales/sync
  │  │
  │  └─ SyncController::sales()
  │     ├─ Create Sale row
  │     ├─ Create SaleItem rows
  │     ├─ Decrement StockBatch (FIFO)
  │     ├─ Create StockTransaction
  │     ├─ Create FinancialTransaction
  │     └─ JSON response {status, message}
  │
  └─ SYNCED!
           ↓
Si pas de connexion:
  ├─ Vente stockée en local
  ├─ "Paiement en attente de synchronisation"
  ├─ Réessai automatique quand connexion revient
  └─ Web Worker essaie sync en arrière-plan
           ↓
Fin du quart (Shift End)
├─ Caissier clique "Fin de quart"
├─ Entre montant réel trésorerie
├─ POST /api/pos/shifts/end
│  ├─ Update Shift (status = 'closed')
│  ├─ Force sync de toutes ventes en attente
│  └─ JSON response
└─ Rapport du quart
           ↓
Données finales en DB:
├─ sales table
├─ sale_items table
├─ stock_batches (quantités diminuées)
├─ stock_transactions table
├─ financial_transactions table
└─ shifts table
           ↓
Visible en Backoffice:
├── /dashboard (vérifie nouvelles ventes)
├── /stocks (stock réduit)
├── /financial/{store}/transactions
└── Rapports ventes/finances
```

---

## 5. Modèle de données - Relations

```
┌─────────────────────────────────────────────────────────────────┐
│                          USERS                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ id | name | email | password | pin_code | store_id | ... │ │
│  └────┬───────────────────────────────┬──────────┬───────────┘ │
│       │ HasRoles (Spatie)             │          │              │
│       │ roles/permissions             │          │              │
└───────┼────────────────────────────────┼──────────┼──────────────┘
        │                                │          │
        │                           ┌────▼──────────▼────┐
        │                           │     STORES         │
        │                           │  (Magasins/Shops)  │
        │                           │ ┌────────────────┐ │
        │                           │ │ id | name | ...│ │
        │                           │ └───┬────────────┘ │
        │                           └─────┼──────────────┘
        │                                 │
        │ ┌──────────────────────────────┘
        │ │
        │ ▼
    ┌─────────────────────────────────────────────────────────────┐
    │                      SHIFTS                                 │
    │  ┌─────────────────────────────────────────────────────────┐│
    │  │ id | user_id | store_id | status | created_at | ... ││
    │  └─────┬──────────────────┬──────────────────────────────┘│
    └───────┼───────────────────┼──────────────────────────────┘
            │                   │
            │ 1:N               │ 1:N
            │                   ▼
            │            ┌──────────────────────────┐
            │            │      SALES               │
            │            │  ┌────────────────────┐  │
            │            │  │ id | shift_id |    │  │
            │            │  │ store_id |         │  │
            │            │  │ payment_type |     │  │
            │            │  │ total | synced_at  │  │
            │            │  └────┬──────────────┘  │
            └────────────────────┤──────────────────┘
                                 │
                            1:N  │
                                 ▼
                    ┌──────────────────────────┐
                    │     SALE_ITEMS           │
                    │  ┌────────────────────┐  │
                    │  │ id | sale_id |     │  │
                    │  │ product_id |       │  │
                    │  │ quantity | price   │  │
                    │  │ is_delivery        │  │
                    │  └────┬──────────────┘  │
                    └───────┼──────────────────┘
                            │
                       1:N  │
                            ▼
        ┌─────────────────────────────────────────────┐
        │            PRODUCTS                         │
        │  ┌──────────────────────────────────────────┐│
        │  │ id | ean | name{} | description{} |    ││
        │  │ price | price_btob | brand_id |        ││
        │  │ is_active | allow_overselling | ...    ││
        │  └────┬─────────────────────────────────┬──┘│
        └──────┼─────────────────────────────────┼───┘
               │                                 │
               │ M:M                            │ 1:N
               │                                │
        ┌──────▼───────┐                 ┌──────▼──────────────┐
        │  CATEGORIES  │                 │  PRODUCT_IMAGES     │
        │ (pivot: ..)  │                 │ (is_primary flag)   │
        └──────────────┘                 └─────────────────────┘
               │
               │ M:M
               ▼
        ┌────────────────┐
        │    BRANDS      │
        └────────────────┘
```

---

## 6. Architecture POS (Vue 3)

```
┌─────────────────────────────────────────────────────────┐
│                    /pos/index.blade.php                  │
│                    (SPA Container)                       │
├─────────────────────────────────────────────────────────┤
│                                                           │
│  <div id="pos-container">                                │
│    ├─ screens/login.blade.php                            │
│    ├─ screens/dashboard.blade.php                        │
│    ├─ screens/products.blade.php                         │
│    ├─ screens/search-results.blade.php                   │
│    ├─ screens/sales.blade.php                            │
│    ├─ screens/sale-detail.blade.php                      │
│    ├─ screens/shift-start.blade.php                      │
│    ├─ screens/shift-end.blade.php                        │
│    └─ screens/journal.blade.php                          │
│                                                           │
│  <div id="side-menu">              Global Menu            │
│    ├─ Button Dashboard                                   │
│    ├─ Button End Shift                                   │
│    ├─ Button Journal                                     │
│    ├─ Button Logout                                      │
│    ├─ Button Force Sync                                  │
│    ├─ Button Cash In                                     │
│    └─ Button Cash Out                                    │
│                                                           │
│  <div id="side-menu-overlay">     Menu Overlay            │
│                                                           │
│  <div id="syncModal">             Modal Sync              │
│  <div id="cashDialogModal">       Modal Cash Dialog       │
│                                                           │
└─────────────────────────────────────────────────────────┘
  │
  ├─ Load JavaScript:
  │  ├─ /js/pos/core/Table.js
  │  ├─ /js/pos/core/Database.js
  │  ├─ /js/pos/tables/UsersTable.js
  │  ├─ /js/pos/tables/CatalogTable.js
  │  ├─ /js/pos/tables/PaymentsTable.js
  │  └─ /js/pos/app.js
  │
  └─ Global JS:
     ├─ LocalStorage Management:
     │  ├─ pos_catalog_cache_v1_store_{storeId}
     │  │  └─ {catalog: [...], payments: [...], savedAt}
     │  │
     │  ├─ pos_category_tree_store_{storeId}
     │  │  └─ {tree: {...}, savedAt}
     │  │
     │  ├─ pos_cash_in_total
     │  └─ pos_cash_out_total
     │
     ├─ Window globals:
     │  ├─ window.sales
     │  ├─ window.currentUser
     │  ├─ window.currentShift
     │  ├─ window.categoryTree
     │  └─ window.selectedParentId, selectedChildId
     │
     ├─ Menu handlers:
     │  ├─ #btn-open-menu
     │  ├─ #btn-close-menu
     │  ├─ #btn-go-dashboard
     │  ├─ #btn-end-shift
     │  ├─ #btn-journal
     │  ├─ #btn-logout
     │  ├─ #btn-force-sync
     │  ├─ #btn-cash-in
     │  └─ #btn-cash-out
     │
     └─ Cash Dialog handlers:
        ├─ .cash-num (numeric buttons)
        ├─ #cash-clear
        ├─ #cash-dot
        └─ #cashDialogOk
```

---

## 7. Middleware et flux d'authentification

```
Backoffice:
│
├─ GET /                                    ✓ Any
│  └─ IF auth() { redirect /dashboard }
│     ELSE { redirect /login }
│
├─ GET /login                               ✓ Guest
│  └─ Auth::routes()
│
├─ POST /login                              ✓ Guest
│  └─ Authenticate user
│     Store in session
│
├─ GET /dashboard                           ✗ auth, SetUserLocale
│  └─ User must be authenticated
│
├─ GET /products, /suppliers, etc.          ✗ auth, role:admin
│  └─ User must be admin role
│
└─ POST /logout                             ✗ auth
   └─ Clear session

POS:
│
├─ GET /pos                                 ✓ Public
│  └─ Serve SPA view
│
├─ GET /api/pos/users                       ✓ Public
│  └─ Return users with pin_code
│
├─ POST /api/pos/shifts/start               ✓ Public (pin auth on client)
│  └─ Create shift for user
│
├─ POST /api/pos/sales/sync                 ✓ Public
│  └─ Sync sales data
│
└─ All other /api/pos/*                     ✓ Public
   └─ No middleware required
   └─ Client must send token if needed

Front API:
│
├─ POST /api/login                          ✓ Guest
│  └─ Return Sanctum token
│
├─ POST /api/register                       ✓ Guest
│  └─ Create user account
│
├─ GET /api/categories                      ✓ Guest
│  └─ Public catalog
│
├─ GET /api/products                        ✗ auth:sanctum
│  └─ User must have valid token
│
├─ POST /api/cart                           ✗ auth:sanctum
│  └─ Panier operations
│
└─ GET /api/orders                          ✗ auth:sanctum
   └─ User must have valid token
```

---

## 8. Cycle de vie d'une transaction financière

```
Scenario: Vente en caisse de 100$ (cash + carte)

POS:
│
├─ Split Payments:
│  ├─ Paiement 1: CASH 60$
│  └─ Paiement 2: CARD 40$
│
└─ POST /api/pos/sales/sync
   │
   └─ SyncController::sales()
      │
      ├─ 1. Create Sale
      │  └─ sale_id = generated
      │     shift_id = 1
      │     store_id = 1
      │     payment_type = 'split'
      │     total = 100
      │     split_payments = [...cash, card...]
      │
      ├─ 2. Create SaleItem x 2
      │  ├─ item_id: product 10, qty 2, price 50
      │  └─ item_id: product 20, qty 1, price 50
      │
      ├─ 3. Decrement Stock (FIFO)
      │  ├─ Product 10:
      │  │  ├─ Get batch1 (5 units, created 2025-01-01)
      │  │  ├─ Remove 2 units → batch1 = 3 units
      │  │  └─ Create StockTransaction (qty: 2, reason: sale)
      │  │
      │  └─ Product 20:
      │     ├─ Get batch1 (10 units)
      │     ├─ Remove 1 unit → batch1 = 9 units
      │     └─ Create StockTransaction (qty: 1, reason: sale)
      │
      ├─ 4. Get Financial Account
      │  └─ FinancialAccount (code: '701') - Ventes
      │
      ├─ 5. Get Last Transaction Balance
      │  ├─ SELECT * FROM financial_transactions
      │  │         WHERE store_id = 1
      │  │         ORDER BY transaction_date DESC
      │  │         LIMIT 1
      │  │
      │  └─ last_balance_after = 5000$
      │
      ├─ 6. Create FinancialTransaction
      │  ├─ account_id = 701
      │  │ amount = 100
      │  │ direction = 'credit'
      │  │ balance_before = 5000$
      │  │ balance_after = 5100$
      │  │ transaction_date = now()
      │  │ store_id = 1
      │  │ created_by = user_id
      │  │ payment_method_id = ???
      │  │ description = "Vente #123"
      │  │
      │  └─ Create FinancialJournal entry
      │
      └─ 7. Return success response
         └─ {status: 'success', synced_sales: [sale_id], message: '1 sale synced'}

Backoffice:
│
└─ GET /financial/1/transactions
   │
   ├─ FinancialTransactionController::index()
   │  └─ Load all transactions for store 1
   │
   └─ VIEW financial/transactions/index.blade.php
      │
      ├─ Filter by date range ✓
      ├─ Filter by account ✓
      ├─ Filter by payment method ✓
      ├─ Filter by amount ✓
      │
      └─ Display:
         ├─ Transaction date: 2025-02-14 14:30:00
         ├─ Account: 701 - Ventes
         ├─ Amount: 100.00
         ├─ Direction: Credit (✓)
         ├─ Balance Before: 5000.00
         ├─ Balance After: 5100.00
         ├─ Payment Method: SPLIT (cash/card)
         ├─ User: John Doe
         └─ Actions: View, Edit, Delete
```

