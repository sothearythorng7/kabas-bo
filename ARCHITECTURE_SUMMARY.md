# RÉSUMÉ ARCHITECTURE - PROJET KABAS

## Vue d'ensemble

Application Laravel 11 avec **3 composantes distinctes** :

### 1. BACKOFFICE (/dashboard)
- **Interface** : Blade templating + Bootstrap 5
- **Utilisateurs** : Administrateurs (role:admin)
- **Authentification** : Email/Password + Spatie Roles
- **Fonctionnalités** :
  - Gestion produits/catalogues (multilingue)
  - Gestion stocks et mouvements (FIFO)
  - Gestion fournisseurs et commandes
  - Gestion revendeurs et livraisons
  - Comptabilité et finances (par magasin)
  - Contenus (pages, blog, coffrets cadeaux)
  - Administration (utilisateurs, rôles, magasins)
  
- **Modèles clés** : Product, Store, Supplier, SupplierOrder, Sale, FinancialTransaction
- **Recherche** : Meilisearch (Scout) pour produits
- **Export** : Excel et PDF

### 2. FRONT (/api/*)
- **Type** : API REST pour frontend externe
- **Authentification** : Sanctum tokens
- **Endpoints** :
  - POST /login, /register
  - GET /products, /categories
  - POST /cart, /checkout
  - GET /orders
- **Status** : Défini mais implémentation externe

### 3. POS (/pos)
- **Interface** : Vue 3 SPA (Single Page App)
- **Utilisateurs** : Caissiers/Vendeurs
- **Authentification** : PIN code
- **Fonctionnalités** :
  - Affichage produits hiérarchique
  - Gestion panier et ventes temps réel
  - Paiements multiples
  - Gestion shifts/quarts
  - Support offline (IndexedDB)
  - Synchronisation asynchrone
  
- **Stack** : Vue 3 + Pinia + Vite
- **Storage** : LocalStorage + IndexedDB
- **API** : /api/pos/* endpoints

---

## Routes principales par section

### Backoffice (protégées - auth + admin)
```
/dashboard                    Dashboard
/products, /brands, /categories   Catalogue
/stocks, /stock-movements, /inventory   Stocks
/suppliers, /supplier-orders      Fournisseurs
/resellers, /resellers/*         Revendeurs
/financial/{store}/*             Finances
/pages, /blog/*, /gift-*/*       Contenus
/users, /roles, /stores          Admin
/backups, /promotion-bar         Système
```

### POS (semi-public)
```
/pos                          SPA principale
/api/pos/products             Catalogue
/api/pos/users                Utilisateurs POS
/api/pos/catalog/{storeId}    Filtré par magasin
/api/pos/shifts/*             Gestion shifts
/api/pos/sales/sync           Synchronisation ventes
```

### Frontend (API public)
```
/api/login, /api/register     Auth
/api/products, /categories    Catalogue
/api/cart, /checkout          Commandes
/api/orders                   Historique
```

---

## Modèles de données clés

### Partagés (Backoffice + POS)
- **Product** - Produits multilingues (name, description, slugs)
- **Store** - Magasins (warehouse/shop)
- **StockBatch** - Lots de stock (FIFO)
- **Sale** - Ventes avec split_payments
- **SaleItem** - Détails ventes avec is_delivery
- **Shift** - Quarts de travail
- **User** - Utilisateurs (pin_code, store_id)
- **FinancialTransaction** - Comptabilité
- **FinancialPaymentMethod** - Moyens de paiement

### Backoffice uniquement
- **Supplier** - Fournisseurs
- **SupplierOrder** - Commandes
- **Reseller** - Revendeurs
- **ResellerStockDelivery** - Livraisons
- **ResellerInvoice** - Factures revendeurs
- **FinancialAccount** - Comptes comptables
- **Category, Brand** - Métadonnées produits
- **Page, BlogPost, GiftBox** - Contenus

### POS uniquement
- Aucun (utilise les modèles Backoffice)
- Storage local via JavaScript : Table.js, Database.js (IndexedDB)

---

## Architecture technique

### Backend
- **Framework** : Laravel 11
- **DB** : MySQL
- **Authentication** : Session (BO), Sanctum (API), PIN (POS)
- **Authorization** : Spatie Roles/Permissions
- **Search** : Meilisearch via Scout
- **Cache** : Redis/File
- **Files** : Storage S3/Local
- **Exports** : Excel, PDF

### Frontend
- **Backoffice** : Blade + Bootstrap 5 + Vanilla JS
- **POS** : Vue 3 + Pinia + Vite + IndexedDB
- **Build** : Vite 7.1
- **UI** : Bootstrap Icons, TinyMCE
- **Notifications** : Notyf

---

## Flux données

```
Base de données MySQL
        ↓
    Laravel
    ├── /dashboard → Blade views (BO)
    ├── /api/* → JSON API (Front)
    └── /api/pos/* → JSON API (POS)
        ↓
    ├── Navigateur BO (Blade + JS)
    ├── App Front externe (Vue/React)
    └── POS SPA (Vue 3)
        ↓
    LocalStorage/IndexedDB (POS offline)
```

---

## Cas d'usage typiques

### Admin crée un produit
1. /products/create → Blade form
2. POST /products → ProductController::store()
3. Attache catégories/fournisseurs/magasins
4. Produit synchro → Meilisearch
5. Stock initial = 0 pour tous les magasins
6. POS récupère via /api/pos/catalog/{storeId}

### Caissier vend un produit (POS)
1. /pos → Vue 3 login avec PIN
2. Cherche produit → Affiche depuis catalogue en cache
3. Ajoute au panier
4. Choisit paiement (cash/card/split)
5. POST /api/pos/sales/sync → SyncController::sales()
6. Crée Sale + SaleItem + décrémente stock (FIFO)
7. Crée FinancialTransaction (compte 701)
8. Stocké localement pendant fermeture shift
9. Synchro en batch POST /api/pos/sync

### Manager reçoit commande fournisseur
1. /suppliers/{supplier}/orders/create → Blade form
2. POST /suppliers/{supplier}/orders → Crée commande
3. GET /suppliers/{supplier}/orders/{order}/reception → Formulaire réception
4. POST réception → Incrémente StockBatch pour magasin
5. Crée StockTransaction ('in')
6. Génère FinancialTransaction si facture reçue

### Comptable enregistre transaction
1. /financial/{store}/transactions/create → Blade form
2. POST → Crée FinancialTransaction
3. Calcule balance_after (solde cumulatif)
4. Upload pièce jointe (PDF/image)
5. Visible dans /financial/{store}/journals

---

## Middlewares et authentification

### Backoffice
```
middleware('auth')              ✓ Connecté
middleware('role:admin')        ✓ Admin uniquement
middleware('SetUserLocale')     ✓ Langue utilisateur
```

### POS
```
Route::prefix('api/pos') → API publique
- login() via PIN sans middleware
- Autres routes sans auth requis (token dans headers possible)
```

### Frontend API
```
middleware('auth:sanctum')      ✓ Token requis
```

---

## Points de vigilance

1. **Stock FIFO** : Décrémentation stricte par lot (important pour coût moyen)
2. **Transactions financières** : Solde cumulatif calculé à chaque insertion
3. **POS offline** : Synchronisation en batch POST /sync
4. **Multilingue** : Product.name, description, slugs en array JSON
5. **Permissions** : Utiliser middleware 'role:*' sur routes protégées
6. **Meilisearch** : Nécessite configuration + redis pour Scout

---

## Fichiers clés à connaître

| Fichier | Role |
|---------|------|
| `/routes/web.php` | Toutes routes Backoffice + POS |
| `/routes/api.php` | Routes API Frontend |
| `/app/Http/Controllers/ProductController.php` | Produits BO |
| `/app/Http/Controllers/POS/SyncController.php` | Sync POS (crucial) |
| `/app/Http/Controllers/POS/ShiftController.php` | Shifts POS |
| `/app/Http/Controllers/DashboardController.php` | Dashboard BO |
| `/resources/views/layouts/app.blade.php` | Layout BO |
| `/resources/views/pos/index.blade.php` | Layout POS |
| `/resources/js/pos/` | Composants Vue 3 POS |
| `/public/js/pos/` | JavaScript vanilla POS (offline) |
| `/app/Models/Product.php` | Modèle clé |
| `/app/Models/Sale.php` | Ventes |
| `/config/scout.php` | Meilisearch config |

