# ANALYSE APPROFONDIE DU PROJET LARAVEL KABAS

## Vue d'ensemble générale

Le projet **KABAS** est une application e-commerce multi-magasins basée sur Laravel 11 avec 3 composantes principales distinctes :

1. **BACKOFFICE (BO)** - Interface d'administration complète
2. **FRONT** - Interface client (implémentation externe ou partenaire)
3. **POS** - Système Point de Vente autonome (SPA avec Vue 3)

---

## PARTIE 1 : BACKOFFICE (Interface d'Administration)

### 1.1 Routes et Points d'entrée

**Fichier : `/routes/web.php` (lignes 1-403)**

#### Routes d'authentification
```
GET  /                              → Redirection vers /dashboard ou /login
POST /track-url                    → Suivi de l'historique de navigation (session)
```

#### Routes principales protégées
```
Middleware: auth + SetUserLocale (traduction utilisateur)

GET  /dashboard                    → Tableau de bord admin
GET  /dashboard/products-issues   → Détail des problèmes produits
GET  /scanner                      → Lecteur code-barres (EAN)
GET  /products/search             → Recherche Ajax (EAN/nom)
```

#### Routes administrateur (role:admin)

**A. Gestion des utilisateurs et permissions**
```
resources('roles', RoleController)         → CRUD Rôles
resources('users', UserController)         → CRUD Utilisateurs
resources('stores', StoreController)       → CRUD Magasins/Points de vente
```

**B. Catalogue produits**
```
resources('products', ProductController)              → CRUD Produits
resources('brands', BrandController)                 → CRUD Marques
resources('categories', CategoryController)          → CRUD Catégories

POST   /products/{product}/photos                    → Upload images
DELETE /products/{product}/photos/{photo}            → Supprimer image
POST   /products/{product}/photos/{photo}/set-primary→ Image principale
GET    /products/{product}/variations                → Gestion variations
POST|PUT|DELETE /products/{product}/variations/*     → CRUD Variations

POST   /products/{product}/categories/attach         → Lier catégories
DELETE /products/{product}/categories/{category}     → Délier catégories

POST   /products/{product}/suppliers/attach          → Lier fournisseurs
DELETE /products/{product}/suppliers/{supplier}      → Délier fournisseurs
PUT    /products/{product}/suppliers/{supplier}/price→ MAJ prix fournisseur

POST   /products/{product}/stores/attach             → Lier magasins
DELETE /products/{product}/stores/{store}            → Délier magasins
PUT    /products/{product}/stores/{store}/stock      → MAJ stock magasin

PUT    /products/{product}/descriptions              → Descriptions multilingues
```

**C. Gestion des stocks et mouvements**
```
GET    /stocks                                       → Vue stocks globaux
POST   /stock-movements                              → Créer transfert stock
PUT    /stock-movements/{movement}/receive           → Recevoir transfert
PUT    /stock-movements/{movement}/cancel            → Annuler transfert
GET    /stock-movements/{movement}                   → Détail transfert
GET    /stock-movements/{movement}/pdf               → Export PDF

POST   /inventory/export                             → Export inventaire
POST   /inventory/import                             → Import inventaire
GET    /inventory/confirm                            → Confirmation inventaire
POST   /inventory/apply                              → Appliquer inventaire
POST   /inventory/cancel                             → Annuler inventaire

GET    /stock-value                                  → Valorisation stock
GET    /stock-value/{product}/lots                   → Détail par lot
```

**D. Gestion des fournisseurs**
```
resources('suppliers', SupplierController)          → CRUD Fournisseurs
PUT    /suppliers/{supplier}/products/{product}/purchase-price → Prix achat

GET    /supplier-orders/overview                     → Vue d'ensemble commandes
GET    /suppliers/{supplier}/sale-reports/create     → Créer rapport vente

Prefix: /suppliers/{supplier}/orders/
  GET    /create                                     → Créer commande
  POST   /                                           → Valider commande
  PUT    /{order}/validate                           → Validation
  GET    /{order}                                    → Détail commande
  GET    /{order}/edit                               → Éditer commande
  PUT    /{order}                                    → MAJ commande
  GET    /{order}/reception                          → Formulaire réception
  POST   /{order}/reception                          → Valider réception
  GET    /{order}/pdf                                → Export PDF
  GET    /{order}/invoice-reception                  → Réception facture
  POST   /{order}/invoice-reception                  → Valider réception facture
  POST   /{order}/mark-paid                          → Marquer payé

Prefix: /suppliers/{supplier}/refills/
  GET    /                                           → Liste refills
  GET    /{refill}                                   → Détail refill
  GET    /reception/create                           → Recevoir refill
  POST   /reception                                  → Valider réception
```

**E. Rapports de ventes fournisseurs**
```
Prefix: /suppliers/{supplier}/sale-reports/
  GET    /                                           → Liste rapports
  GET    /create                                     → Créer rapport
  POST   /                                           → Sauvegarder rapport
  GET    /{saleReport}                               → Détail rapport
  GET    /{saleReport}/send                          → Envoyer rapport
  POST   /{saleReport}/send                          → Valider envoi
  PUT    /{saleReport}/mark-invoiced                 → Marquer facturé
  GET    /{saleReport}/invoice/create                → Créer facture
  POST   /{saleReport}/invoice                       → Sauvegarder facture
  PUT    /{saleReport}/invoice/receive               → Recevoir facture
  GET    /{saleReport}/invoice-reception             → Formulaire réception
  POST   /{saleReport}/invoice-reception             → Valider réception
  POST   /{saleReport}/mark-as-paid                  → Marquer payé
  POST   /{saleReport}/send-telegram                 → Envoyer par Telegram
```

**F. Revendeurs (resellers)**
```
resources('resellers', ResellerController)          → CRUD Revendeurs
POST   /resellers/{reseller}/contacts                → Ajouter contact
DELETE /resellers/{reseller}/contacts/{contact}      → Supprimer contact

GET    /resellers/{reseller}/deliveries/create       → Créer livraison stock
POST   /resellers/{reseller}/deliveries              → Sauvegarder livraison
GET    /resellers/{reseller}/deliveries/{delivery}/edit  → Éditer livraison
PUT    /resellers/{reseller}/deliveries/{delivery}   → MAJ livraison
GET    /resellers/{reseller}/deliveries/{delivery}   → Détail livraison

GET    /resellers/{reseller}/reports/create          → Créer rapport vente
POST   /resellers/{reseller}/reports                 → Sauvegarder rapport
GET    /resellers/{reseller}/reports/{report}        → Détail rapport
POST   /resellers/{reseller}/reports/{report}/payments→ Ajouter paiement

GET    /deliveries/{delivery}/invoice                → Générer facture
GET    /resellers/{reseller}/reports/{report}/invoice→ Facture rapport

Prefix: /reseller-invoices/
  GET    /                                           → Liste factures
  GET    /create                                     → Créer facture
  POST   /                                           → Sauvegarder
  GET    /{invoice}/edit                             → Éditer
  PUT    /{invoice}                                  → MAJ
  DELETE /{invoice}                                  → Supprimer
  GET    /{invoice}                                  → Détail
  POST   /{invoice}/payments                         → Ajouter paiement
```

**G. Gestion financière par magasin**
```
GET    /financial                                    → Vue d'ensemble factures
Prefix: /financial/{store}/
  resources('accounts', FinancialAccountController)  → Comptes financiers
  resources('payment-methods', FinancialPaymentMethodController)
  resources('transactions', FinancialTransactionController) → Transactions
  resources('general-invoices', GeneralInvoiceController)
  resources('shifts', FinancialShiftController)      → Shifts/Quarts
  GET    /journals                                   → Journaux
  GET    /journals/{journal}                         → Détail journal
  GET    /dashboard                                  → Dashboard financier
  GET    /transactions/export                        → Export Excel

Prefix: /stores/{site}/
  resources('journals', JournalController)           → Journal des transactions
  resources('payments', SupplierPaymentController)   → Paiements fournisseurs
  resources('expenses', ExpenseController)           → Dépenses générales
  resources('expense-categories', ExpenseCategoryController)
  GET    /dashboard                                  → Dashboard du magasin
```

**H. Gestion des contenus (Gifts, Pages, Blog)**
```
resources('gift-boxes', GiftBoxController)          → Coffrets cadeaux
  + images upload/management
  + association catégories/produits

resources('gift-cards', GiftCardController)         → Cartes cadeaux
resources('pages', PageController)                   → Pages statiques
resources('hero-slides', HeroSlideController)       → Slides d'accueil
resources('blog.posts', BlogPostController)         → Articles blog
resources('blog.categories', BlogCategoryController)→ Catégories blog
resources('blog.tags', BlogTagController)           → Tags blog

resources('variation-types')                        → Types de variations
resources('variation-values')                       → Valeurs de variations
resources('invoice-categories')                     → Catégories factures

GET    /promotion-bar                                → Barre promotionnelle
PUT    /promotion-bar                                → MAJ barre

GET    /contact-messages                             → Messages de contact
GET    /contact-messages/{contactMessage}            → Détail message
POST   /contact-messages/{contactMessage}/mark-as-read

GET    /home-content/edit                            → Éditer contenu accueil
PUT    /home-content                                 → MAJ contenu

GET    /backups                                      → Liste sauvegardes
POST   /backups/create                               → Créer sauvegarde
GET    /backups/download/{filename}                  → Télécharger
DELETE /backups/{filename}                           → Supprimer
```

### 1.2 Contrôleurs du Backoffice

**Localisation : `/app/Http/Controllers/`**

#### Contrôleurs principaux

| Contrôleur | Responsabilités |
|-----------|-----------------|
| **DashboardController** | Vue d'ensemble : factures à payer, produits sans images, descriptions manquantes, stock zéro |
| **ProductController** | CRUD produits, gestion images, variations, associations (catégories/fournisseurs/magasins), descriptions multilingues |
| **StockController** | Vue stock globale par magasin |
| **StockMovementController** | Transferts de stock entre magasins |
| **InventoryController** | Import/export/inventaire physique |
| **CategoryController** | Gestion catégories hiérarchiques, slugs par langue |
| **BrandController** | Gestion marques |
| **SupplierController** | CRUD fournisseurs, gestion contacts |
| **SupplierOrderController** | Commandes fournisseurs, réception, factures |
| **SaleReportController** | Rapports ventes fournisseurs, facturation, paiements |
| **RefillController** | Réapprovisionnements rapides |
| **ResellerController** | Gestion revendeurs, livrables |
| **ResellerStockDeliveryController** | Livraisons aux revendeurs |
| **ResellerSalesReportController** | Rapports ventes revendeurs |
| **ResellerInvoiceController** | Factures revendeurs |
| **StoreController** | Gestion magasins/points de vente |
| **UserController** | Gestion utilisateurs, attribution magasins |
| **RoleController** | Gestion rôles et permissions |
| **FinancialTransactionController** | Transactions financières, export Excel |
| **FinancialAccountController** | Comptes (401, 411, 701, etc.) |
| **FinancialPaymentMethodController** | Moyens de paiement |
| **FinancialDashboardController** | Dashboard financier |
| **FinancialShiftController** | Shifts financiers |
| **GeneralInvoiceController** | Factures générales |
| **JournalController** | Journaux comptables |
| **ExpenseController** | Gestion dépenses |
| **ExpenseCategoryController** | Catégories dépenses |
| **SupplierPaymentController** | Paiements fournisseurs |
| **GiftBoxController** | Gestion coffrets cadeaux |
| **GiftCardController** | Gestion cartes cadeaux |
| **WarehouseInvoiceController** | Factures d'entrepôt |
| **BlogPostController** | Gestion articles blog |
| **BlogCategoryController** | Catégories blog |
| **BlogTagController** | Tags blog |
| **ContactMessageController** | Messages de contact |
| **PageController** | Pages statiques |
| **HeroSlideController** | Slides d'accueil |
| **HomeContentController** | Contenu d'accueil |
| **BackupController** | Gestion sauvegardes MySQL |
| **PromotionBarController** | Barre promotionnelle |

### 1.3 Modèles de données (Backoffice)

**Localisation : `/app/Models/`**

#### Catalogue et Produits
- `Product` - Produits avec support multilingue (name, description, slugs)
- `ProductImage` - Images de produits
- `ProductVariation` - Variations produit (taille, couleur, etc.)
- `Brand` - Marques
- `Category` - Catégories hiérarchiques
- `CategoryTranslation` - Traductions catégories
- `VariationType` - Types de variations
- `VariationValue` - Valeurs de variations

#### Stocks et Magasins
- `Store` - Magasins/Points de vente
- `StockBatch` - Lots de stock (FIFO)
- `StockTransaction` - Transactions de stock (in/out)
- `StockMovement` - Transferts entre magasins
- `StockMovementItem` - Détails transferts

#### Fournisseurs
- `Supplier` - Fournisseurs
- `SupplierOrder` - Commandes fournisseurs
- `Contact` - Contacts fournisseurs
- `PurchasePriceHistory` - Historique prix d'achat
- `SupplierOrderInvoiceLine` - Lignes factures commandes
- `PriceDifference` - Écarts prix

#### Revendeurs
- `Reseller` - Revendeurs
- `ResellerContact` - Contacts revendeurs
- `ResellerStockDelivery` - Livraisons stock
- `ResellerSalesReport` - Rapports ventes revendeurs
- `ResellerSalesReportItem` - Détails rapport ventes
- `ResellerSalesReportAnomaly` - Anomalies détectées
- `ResellerInvoice` - Factures revendeurs
- `ResellerInvoicePayment` - Paiements factures
- `ResellerStockBatch` - Lots revendeurs

#### Finances
- `FinancialAccount` - Comptes comptables
- `FinancialTransaction` - Transactions (débits/crédits)
- `FinancialPaymentMethod` - Moyens de paiement
- `FinancialJournal` - Journaux comptables
- `FinancialTransactionLog` - Logs transactions
- `FinancialTransactionAttachment` - Pièces jointes
- `GeneralInvoice` - Factures générales
- `Journal` - Journaux de caisse
- `Expense` - Dépenses
- `ExpenseCategory` - Catégories dépenses
- `SupplierPayment` - Paiements fournisseurs
- `Account` - Comptes ancienne version (compatibilité)

#### Ventes et Shifts
- `Sale` - Ventes POS
- `SaleItem` - Lignes de ventes
- `SaleReport` - Rapports de ventes fournisseurs
- `SaleReportItem` - Détails rapports
- `Shift` - Quarts de travail
- `CashTransaction` - Transactions de caisse
- `CashTransactionItem` - Détails transactions caisse

#### Contenu et Pages
- `Page` - Pages statiques
- `BlogPost` - Articles blog
- `BlogCategory` - Catégories blog
- `BlogTag` - Tags blog
- `GiftBox` - Coffrets cadeaux
- `GiftBoxImage` - Images coffrets
- `GiftBoxItem` - Produits dans coffrets
- `GiftCard` - Cartes cadeaux
- `GiftCardCode` - Codes cartes cadeaux
- `HeroSlide` - Slides d'accueil
- `HomeContent` - Contenu d'accueil
- `PromotionBar` - Barre promotionnelle

#### Autre
- `User` - Utilisateurs avec pin_code et store_id
- `WarehouseInvoice` - Factures d'entrepôt
- `WarehouseInvoiceFile` - Fichiers factures
- `ContactMessage` - Messages de contact
- `InvoiceCategory` - Catégories factures

### 1.4 Vues Blade (Backoffice)

**Localisation : `/resources/views/`**

#### Layouts et partials
```
layouts/app.blade.php                 → Layout principal avec sidebar
partials/menu.blade.php               → Menu de navigation
partials/sidebar-menu.blade.php       → Menu sidebar
partials/flash-messages.blade.php     → Messages flash
components/breadcrumb.blade.php       → Fil d'Ariane
```

#### Pages principales
```
dashboard.blade.php                   → Dashboard principal
dashboard/products-issues.blade.php   → Détail problèmes produits
home.blade.php                        → Page d'accueil authentifiée
scanner.blade.php                     → Lecteur code-barres
```

#### Gestion Produits
```
products/index.blade.php              → Liste produits (Meilisearch)
products/create.blade.php             → Créer produit
products/edit.blade.php               → Éditer produit (42KB - très complet)
products/partials/*.blade.php         → Composants réutilisables
```

#### Gestion Stocks
```
stocks/index.blade.php                → Vue stocks
stock_movements/index.blade.php       → Transferts stock
stock_movements/create.blade.php      → Créer transfert
stock_movements/show.blade.php        → Détail transfert
stock_movements/pdf.blade.php         → Export PDF
accounting/stock_value.blade.php      → Valorisation stock
accounting/stock_value_lot.blade.php  → Détail par lot
inventory/index.blade.php             → Inventaire physique
inventory/confirm.blade.php           → Confirmation
```

#### Gestion Fournisseurs
```
suppliers/index.blade.php             → Liste fournisseurs
suppliers/create.blade.php            → Créer fournisseur
suppliers/edit.blade.php              → Éditer fournisseur
supplier_orders/create.blade.php      → Créer commande
supplier_orders/edit.blade.php        → Éditer commande
supplier_orders/show.blade.php        → Détail commande
supplier_orders/reception.blade.php   → Réception
supplier_orders/invoice_reception.blade.php → Réception facture
supplier_orders/pdf.blade.php         → Export PDF
supplier_orders/overview.blade.php    → Aperçu commandes
supplier_orders/partials/*.blade.php  → Composants
sale_reports/create.blade.php         → Créer rapport
sale_reports/show.blade.php           → Détail rapport
sale_reports/send.blade.php           → Envoyer rapport
sale_reports/reception_invoice.blade.php → Réception facture
refills/show.blade.php                → Détail refill
refills/reception.blade.php           → Réception refill
```

#### Gestion Revendeurs
```
resellers/index.blade.php             → Liste revendeurs
resellers/create.blade.php            → Créer revendeur
resellers/show.blade.php              → Détail revendeur
resellers/deliveries/create.blade.php → Créer livraison
resellers/deliveries/edit.blade.php   → Éditer livraison
resellers/reports/create.blade.php    → Créer rapport
resellers/reports/show.blade.php      → Détail rapport
resellers/reports/invoice.blade.php   → Facture rapport
reseller_invoices/index.blade.php     → Liste factures
reseller_invoices/show.blade.php      → Détail facture
warehouse-invoices/bills.blade.php    → Aperçu factures
```

#### Gestion Financière
```
financial/dashboard.blade.php         → Dashboard financier
financial/accounts/create.blade.php   → Créer compte
financial/accounts/edit.blade.php     → Éditer compte
financial/transactions/create.blade.php → Créer transaction
financial/transactions/edit.blade.php  → Éditer transaction
financial/transactions/show.blade.php  → Détail transaction
financial/transactions/excel.blade.php → Export Excel
financial/payment_methods/create.blade.php
financial/general-invoices/create-modal.blade.php
financial/general-invoices/edit.blade.php
financial/shifts/show.blade.php       → Détail shift
financial/journals/show.blade.php     → Détail journal
```

#### Administration
```
users/index.blade.php                 → Liste utilisateurs
roles/index.blade.php                 → Rôles
stores/index.blade.php                → Magasins
stores/create.blade.php               → Créer magasin
stores/edit.blade.php                 → Éditer magasin
stores/show.blade.php                 → Détail magasin
stores/dashboard.blade.php            → Dashboard magasin
categories/index.blade.php            → Catégories (Vue.js tree)
brands/index.blade.php                → Marques
```

#### Contenus
```
pages/index.blade.php                 → Pages statiques
blog/posts/index.blade.php            → Articles blog
blog/posts/create.blade.php           → Créer article
blog/categories/index.blade.php       → Catégories blog
blog/tags/index.blade.php             → Tags blog
gift-boxes/index.blade.php            → Coffrets cadeaux
gift-boxes/create.blade.php           → Créer coffret
gift-boxes/edit.blade.php             → Éditer coffret
gift-cards/index.blade.php            → Cartes cadeaux
hero_slides/index.blade.php           → Slides d'accueil
home-content/edit.blade.php           → Éditer contenu accueil
promotion-bar/index.blade.php         → Barre promotionnelle
contact-messages/index.blade.php      → Messages de contact
backups/index.blade.php               → Sauvegardes
```

#### Autres
```
journals/index.blade.php              → Journaux
journals/create.blade.php             → Créer journal
payments/index.blade.php              → Paiements fournisseurs
payments/create.blade.php             → Créer paiement
expenses/index.blade.php              → Dépenses
expenses/create.blade.php             → Créer dépense
expenses/categories/index.blade.php   → Catégories dépenses
variation_types/index.blade.php       → Types variations
variation_values/index.blade.php      → Valeurs variations
invoice-categories/index.blade.php    → Catégories factures
```

### 1.5 Architecture JavaScript/CSS Backoffice

**Localisation : `/resources/js/` et `/resources/sass/`**

```
resources/js/
├── app.js                 → Point d'entrée (Vite)
├── bootstrap.js           → Configuration Axios, CSRF
├── flash.js               → Notifications/Toasts
├── menu.js                → Gestion menu
└── pos/                   → [Voir section POS]

resources/sass/
├── app.scss               → Styles principaux
└── (import Bootstrap + Tailwind)
```

**Framework frontend :**
- **Bootstrap 5.3** - Composants UI
- **Axios** - Requêtes HTTP
- **Vite 7.1** - Build tool
- **Meilisearch** - Recherche produits (Scout)
- **TinyMCE** - Éditeur riche
- **Notyf** - Notifications

### 1.6 Authentification et Autorisation

**Middleware : `/app/Http/Middleware/SetUserLocale.php`**

La localisation de l'utilisateur est appliquée à chaque requête.

**Contrôle d'accès :**
- Utilise **Spatie Permission** pour les rôles/permissions
- Role `admin` : accès à toutes les routes admin
- Route group `/dashboard` et autres : `middleware(['auth', SetUserLocale::class])`
- POS routes : pas d'auth requise (login dans POS lui-même)

**Modèle User :**
```php
protected $fillable = ['name', 'email', 'password', 'locale', 'pin_code', 'store_id'];
- pin_code     : Code PIN pour POS
- store_id     : Magasin affecté
- roles()      : Via Spatie Permission
- shifts()     : Quarts de travail
```

---

## PARTIE 2 : FRONT (Interface Client)

**Status : Non implémentée dans ce projet**

Basé sur `/routes/api.php`, l'application semble avoir une API destinée à un frontend externe :

```
POST   /login                        → Authentification
POST   /register                     → Inscription
GET    /categories                   → Liste catégories

Middleware: auth:sanctum (API tokens)
GET    /products                     → Liste produits
GET    /products/{id}                → Détail produit
POST   /cart                         → Ajouter au panier
POST   /checkout                     → Passer commande
GET    /orders                       → Mes commandes
```

**Contrôleurs API :**
```
app/Http/Controllers/Api/
├── AuthController.php         → login, register
├── ProductController.php       → Catalogue
├── CategoryController.php      → Catégories
└── OrderController.php         → Panier et commandes
```

Le frontend peut être une application Vue.js/React externe qui consomme cette API via Sanctum.

---

## PARTIE 3 : POS (Point de Vente)

### 3.1 Architecture POS

**Type : Single Page Application (SPA) avec Vue 3 + Pinia**

**Point d'entrée : `/pos`**
```
Route::get('/pos', function () {
    return view('pos.index');
});
```

### 3.2 Routes API POS

**Fichier : `/routes/web.php` (lignes 406-424)**

```
Prefix: /api/pos
├── GET    /products              → Catalogue complet
├── POST   /sync                  → Synchronisation des ventes
├── GET    /users                 → Utilisateurs avec PIN
├── GET    /catalog/{storeId}     → Catalogue par magasin

Shifts:
├── GET    /shifts/current/{userId}     → Quart actuel
├── POST   /shifts/start                → Ouvrir quart
├── POST   /shifts/end                  → Fermer quart
├── POST   /shifts/sales-by-date        → Ventes par date

Sales:
└── POST   /sales/sync             → Synchroniser ventes en batch
```

### 3.3 Contrôleurs POS

**Localisation : `/app/Http/Controllers/POS/`**

#### SyncController (17.4 KB - logique métier complexe)
```php
public function users()                    → Utilisateurs POS avec PIN
public function catalog($storeId)          → Catalogue filtre par magasin
public function sync(Request $request)     → Synchronisation initiale
public function sales(Request $request)    → Synchronisation des ventes
```

**Logique de sales() :**
1. Validation des données de ventes (shift, items, paiements)
2. Création des modèles Sale et SaleItem
3. Décrémentation FIFO du stock (StockBatch)
4. Création transactions stock (StockTransaction)
5. Génération transaction financière (compte 701)
6. Calcul du solde cumulatif
7. Support paiements multiples (split_payments)
8. Support articles livraison (is_delivery flag)

#### ShiftController (3.2 KB)
```php
public function currentShift($userId)      → Quart actuel utilisateur
public function start(Request $request)    → Ouvrir un quart
public function end(Request $request)      → Fermer un quart
public function salesByDate(Request $request)
```

**Logique Shifts :**
- Associé à User + Store
- Status : 'open', 'closed'
- Synchronisation des ventes lors de la fermeture

### 3.4 Vues POS (Blade)

**Localisation : `/resources/views/pos/`**

```
index.blade.php                    → Layout principal SPA
  - Charge JS/CSS POS
  - Cache localStorage
  - Gère menu global
  - Modales (sync, cash in/out)
  - Overlay menu

screens/
├── login.blade.php               → Écran de connexion (PIN)
├── dashboard.blade.php           → Accueil POS (menu principal)
├── products.blade.php            → Affichage produits/catégories
├── search-results.blade.php      → Résultats recherche
├── sales.blade.php               → Panier et checkout
├── sale-detail.blade.php         → Détail d'une vente
├── shift-start.blade.php         → Ouverture quart
├── shift-end.blade.php           → Fermeture quart
└── journal.blade.php             → Journal quart
```

### 3.5 JavaScript POS

**Localisation : `/resources/js/pos/` (SPA avec Vite/Vue 3)**

```
main.js                            → Point d'entrée Vue 3
  - Crée app Vue avec Pinia
  - Charge router
  - Sync utilisateurs

App.vue                            → Composant racine
router.js                          → Vue Router configuration
db.js                              → Synchronisation DB

Components:
├── Login.vue                      → Connexion PIN
├── Layout.vue                     → Layout principal
├── Products.vue                   → Grille produits
├── ShiftStart.vue                 → Ouverture quart
├── ShiftEnd.vue                   → Fermeture quart
├── ShiftEndModal.vue              → Modal fermeture
├── ShiftStartModal.vue            → Modal ouverture
├── ModalManager.vue               → Gestion modales
└── (autres composants Vue)

Store (Pinia):
├── user.js                        → État utilisateur
└── modalStore.js                  → État modales

Workers:
└── syncWorker.js                  → Web Worker pour sync en arrière-plan
```

**Localisation : `/public/js/pos/` (JavaScript vanilla pour support offline)**

```
core/
├── Table.js                       → Classe table (IndexedDB)
└── Database.js                    → Classe database IndexedDB

tables/
├── UsersTable.js                  → Table utilisateurs
├── CatalogTable.js                → Table produits
└── PaymentsTable.js               → Table moyens de paiement

app.js                             → App POS (screen management)
```

### 3.6 Fonctionnalités POS spécifiques

#### Gestion des stocks POS
- **FIFO** : Utilisation stock par lot (batch) le plus ancien
- **Transactions** : Chaque vente crée transaction stock ('out')
- **Overselling** : Flag `allow_overselling` permet stock négatif si activé

#### Gestion des paiements POS
- **Mono-paiement** : `payment_type` unique par vente
- **Multi-paiement** : `split_payments` pour paiements multiples
  ```
  split_payments: [
    { payment_type: 'cash', amount: 50 },
    { payment_type: 'card', amount: 25 }
  ]
  ```
- **Moyens** : Mappés via `FinancialPaymentMethod` (code 'cash', 'card', etc.)

#### Synchronisation POS
- **LocalStorage** : Catalogue + paiements en cache
- **IndexedDB** : Base de données locale (Table.js, Database.js)
- **Web Worker** : Sync asynchrone en arrière-plan
- **Fallback offline** : Fonctionne sans réseau avec cache

#### Services spéciaux POS
- **Cash In/Out** : Gestion trésorerie (localStorage cumul)
- **Delivery** : Articles livraison (flag `is_delivery`)
- **Discounts** : Remises par article ou globales
- **Multiple stores** : Catalogue filtré par magasin

---

## ARCHITECTURE GLOBALE - INTERACTIONS

### 3.7 Flux de données

```
┌─────────────────────────────────────────────────────────────┐
│                     BASE DE DONNÉES MySQL                   │
│  (produits, stocks, utilisateurs, transactions, finances)   │
└─────────────────────────────────────────────────────────────┘
          ↑                    ↑                    ↑
          │                    │                    │
    ┌─────────────────────────────────────────────────────┐
    │         LARAVEL (Routes + Contrôleurs)              │
    ├─────────────────────────────────────────────────────┤
    │  [BACKOFFICE]    [API FRONT]     [API POS]          │
    │  /dashboard      /api/*           /api/pos/*         │
    │  Blade views     JSON/Sanctum     JSON/No auth       │
    └─────────────────────────────────────────────────────┘
          ↑                    ↑                    ↑
          │                    │                    │
    ┌──────────────┐    ┌─────────────┐      ┌──────────────┐
    │  Navigateur  │    │   Front App │      │  POS (SPA)   │
    │  Admin       │    │  (External) │      │   Vue 3      │
    │  Blade + JS  │    │  Vue/React  │      │   + Offline  │
    └──────────────┘    └─────────────┘      └──────────────┘
```

### 3.8 Modèles de données partagés

**Entre Backoffice et POS :**
- `Product` - Catalogue produits
- `Store` - Points de vente
- `StockBatch` - Stock par lot
- `User` - Utilisateurs (avec pin_code POS)
- `Shift` - Quarts de travail
- `Sale` / `SaleItem` - Ventes
- `FinancialTransaction` - Transactions financières
- `FinancialPaymentMethod` - Moyens de paiement

**Spécifiques Backoffice :**
- Supplier, SupplierOrder (gestion fournisseurs)
- Reseller, ResellerStockDelivery (gestion revendeurs)
- Category, Brand, VariationType (métadonnées produits)
- FinancialAccount (comptabilité)
- Page, BlogPost, GiftBox (contenus)

**Spécifiques POS :**
- Aucun modèle unique (utilise modèles partagés)
- Stockage local : IndexedDB (Table.js)

---

## CONFIGURATION ET INFRASTRUCTURE

### 4.1 Configuration Laravel

**Fichier : `/config/` (16 fichiers)**

| Fichier | Usage |
|---------|-------|
| `app.php` | APP_NAME, APP_LOCALE, timezone, providers |
| `auth.php` | Auth guards (web, api) |
| `database.php` | Connexions MySQL/SQLite |
| `filesystems.php` | Stockage (public, s3) |
| `session.php` | Session driver, cookie |
| `cache.php` | Cache driver |
| `queue.php` | Job queuing |
| `mail.php` | Mailer config |
| `services.php` | Services externes |
| `sanctum.php` | API tokens |
| `scout.php` | **Meilisearch** pour recherche produits |
| `site_paths.php` | Public path pour site front |
| `permission.php` | Spatie roles/permissions |
| `menu.php` | Configuration menu BO |
| `tabs.php` | Configuration onglets |
| `logging.php` | Logging channels |

### 4.2 Migrations base de données

**Localisation : `/database/migrations/`**

**Clés de migration (2025) :**
```
Authentification:
- users_table, roles, permissions, model_has_roles
- pin_code, store_id

Produits/Stocks:
- products (multilingue: name, description, slugs)
- product_images, categories, brands
- stock_batches, stock_transactions, stock_movements
- variation_types, variation_values, product_variations
- suppliers, supplier_orders, purchase_price_history

Magasins:
- stores (type: warehouse/shop, is_reseller)
- store_product (pivot avec alert_stock_quantity)

Revendeurs:
- resellers, reseller_contacts, reseller_stock_deliveries
- reseller_sales_reports, reseller_invoices
- reseller_invoice_payments

Finances:
- financial_accounts, financial_transactions
- financial_payment_methods, financial_journals
- general_invoices, journal, expenses

Ventes/Shifts:
- sales, sale_items (avec is_delivery, split_payments)
- shifts, cash_transactions
- sale_items: product_id nullable (articles livraison)

Contenus:
- pages, hero_slides
- blog_posts, blog_categories, blog_tags
- gift_boxes, gift_cards, gift_card_codes
- contact_messages, home_content

Autre:
- warehouse_invoices, warehouse_invoice_files
- invoice_categories, promotion_bars
```

### 4.3 Services et intégrations

**Meilisearch (Scout)**
- Recherche full-text produits
- Configuration : `/config/scout.php`
- Index : table `products` avec Scout searchable

**Spatie Permission**
- Gestion rôles/permissions
- Middleware `role:admin` sur routes admin
- Traits : `HasRoles` sur User

**Laravel Sanctum**
- Authentification API tokens
- Utilisé pour FRONT API (external)
- Routes : `middleware('auth:sanctum')`

**Excel/PDF**
- **Maatwebsite Excel** : Export transactions, inventaire
- **Barryvdh PDF** : Export commandes, rapports

**EmailTransport**
- Envoi rapports par email
- Config : `.env` MAIL_*

**Telegram**
- Envoi rapports ventes par Telegram
- Script : `generate_telegram_session.php`

---

## POINTS D'ENTRÉE ET NAVIGATION

### 5.1 Points d'entrée par rôle

#### 1. Administrateur (Admin)
```
Accès : Toutes les routes admin

Flux de connexion:
  / → Détecte auth() ? redirect /dashboard : /login
  /login → Authentification
  /dashboard → Vue d'ensemble (factures, problèmes produits)

Navigation principale:
  - Gestion produits (/products)
  - Gestion stocks (/stocks, /stock-movements, /inventory)
  - Gestion fournisseurs (/suppliers, /supplier-orders)
  - Gestion revendeurs (/resellers)
  - Gestion financière (/financial)
  - Gestion contenus (pages, blog, gifts, etc.)
  - Administration (utilisateurs, rôles, magasins)
```

#### 2. Opérateur de caisse (Caissier)
```
Accès : POS uniquement

Flux de connexion:
  /pos → Vue d'accueil
  ? → Authentification PIN sur écran login
  /pos → Dashboard POS (menu principal)

Navigation:
  - Affichage produits
  - Création de ventes (panier + paiement)
  - Gestion shift (ouverture/fermeture)
  - Recherche produits
  - Historique ventes (journal)
```

#### 3. Gestionnaire de magasin
```
Accès : Backoffice avec vue limitée

Flux :
  /dashboard → Dashboard personnel
  /stores/{store}/dashboard → Dashboard magasin
  /financial/{store}/dashboard → Dashboard financier

Routes autorisées:
  - Ventes du magasin
  - Gestion finances magasin
  - Rapports magasin
```

### 5.2 Navigation dans Backoffice

**Layout : `/resources/views/layouts/app.blade.php`**

```
Header:
- Logo
- Barre utilisateur (profil, déconnexion)
- Liens rapides

Sidebar (menu.blade.php):
├── Dashboard
├── Catalogue
│   ├── Produits
│   ├── Catégories
│   ├── Marques
│   └── Variations
├── Stocks
│   ├── Stocks
│   ├── Mouvements
│   ├── Inventaire
│   └── Valorisation
├── Fournisseurs
│   ├── Fournisseurs
│   ├── Commandes
│   ├── Rapports ventes
│   └── Refills
├── Revendeurs
│   ├── Revendeurs
│   ├── Rapports
│   ├── Livraisons
│   └── Factures
├── Finances
│   ├── Dashboard
│   ├── Transactions
│   ├── Comptes
│   ├── Moyens de paiement
│   ├── Journaux
│   └── Factures
├── Administration
│   ├── Utilisateurs
│   ├── Rôles
│   ├── Magasins
│   └── Sauvegardes
└── Contenus
    ├── Pages
    ├── Blog
    ├── Coffrets
    ├── Cartes cadeaux
    └── Slides
```

**Interactions clés :**
- Fil d'Ariane (breadcrumb) pour localisation
- Messages flash (succès/erreur) via Notyf
- Modales pour actions rapides
- Tables paginées (20-100 items/page)
- Filtres et recherche

### 5.3 Navigation dans POS

**Layout : `/resources/views/pos/index.blade.php`**

```
Header:
- Bouton menu (3 lignes)
- Titre écran actuel

Menu latéral (global side-menu):
├── Dashboard
├── Fin de quart
├── Journal
├── Déconnexion
├── Sync catalogue
├── Cash In/Out

Écrans:
1. Login (PIN code)
   → Sélection utilisateur via PIN

2. Dashboard (accueil)
   → Menu principal
   → Boutons: Produits, Quart, Journal, etc.

3. Products (grille)
   → Catégories hiérarchiques
   → Recherche produits
   → Ajout au panier

4. Sales (panier)
   → Articles de la vente
   → Remises
   → Sélection paiement
   → Validation

5. Shift Start
   → Montant de caisse initial
   → Ouvrir quart

6. Shift End
   → Montant de caisse final
   → Fermer quart
   → Rapport

7. Journal
   → Historique ventes du quart
```

---

## TECHNOLOGIES ET DÉPENDANCES

### 6.1 Backend
```
laravel/framework      ~11.0
laravel/sanctum        (API tokens)
spatie/laravel-permission (Rôles)
laravel/scout          (Meilisearch)
meilisearch/meilisearch-php
barryvdh/laravel-dompdf (PDF)
maatwebsite/laravel-excel (Excel)
```

### 6.2 Frontend
```
vue:                   ^3.5.21
vue-router:            ^4.5.1
pinia:                 ^3.0.3    (State management)
axios:                 ^1.12.2   (HTTP client)
bootstrap:             ^5.2.3    (UI Components)
tinymce:               ^8.1.2    (Rich text editor)
notyf:                 ^3.10.0   (Notifications)
dexie:                 ^4.2.0    (IndexedDB wrapper)
```

### 6.3 Build tools
```
vite:                  ^7.1.3
laravel-vite-plugin:   ^2.0.0
sass:                  ^1.56.1
tailwindcss:           ^4.0.0
```

---

## RÉSUMÉ EXÉCUTIF

| Aspect | Backoffice | POS | Front |
|--------|-----------|-----|-------|
| **Type** | Blade SPA | Vue 3 SPA | API Only |
| **Authentification** | Email/Password | PIN Code | Sanctum Tokens |
| **Utilisateurs** | Admins, Managers | Caissiers | Clients |
| **Points clés** | Gestion catalogs, stocks, finances | Ventes temps réel, offline | Catalogue, panier |
| **Routes** | `/dashboard`, `/products`, `/financial` | `/pos`, `/api/pos/*` | `/api/*` |
| **Vues** | Blade 45+ templates | 10+ screens Vue | JSON API |
| **Modèles** | 60+ modèles Laravel | Réutilisation modèles | Partagés |
| **JavaScript** | Vanilla JS + Bootstrap | Vue 3 + Pinia + Web Worker | Externe |
| **Offline support** | Non | Oui (IndexedDB) | Non |
| **Data sync** | Real-time | Batch async | Temps réel |

---

## CONCLUSION

Le projet KABAS est une **plateforme e-commerce professionnelle multi-magasins** avec :

1. **Backoffice complet** : Gestion produits, stocks, fournisseurs, revendeurs, finances
2. **POS autonome** : Ventes temps réel avec support offline
3. **API extensible** : Pour intégrations front externes
4. **Architecture modulaire** : Utilise Spatie, Scout, Sanctum

Les 3 parties sont **fortement intégrées au niveau base de données** mais **séparées au niveau logique** (routes, vues, authentification).

