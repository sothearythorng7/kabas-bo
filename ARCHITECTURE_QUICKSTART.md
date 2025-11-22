# QUICK START - ARCHITECTURE KABAS

## Pour les pressés (5-10 minutes)

### Qu'est-ce que c'est ?
**KABAS** = Plateforme e-commerce multi-magasins avec 3 applications:
- **BACKOFFICE** : Admin interface (Blade + Bootstrap)
- **POS** : Caisse tactile (Vue 3 SPA, offline-capable)
- **FRONT API** : Pour app mobile/web externe

### Stack technique
- **Backend**: Laravel 11 + MySQL
- **BO Frontend**: Blade + Bootstrap 5 + Vanilla JS
- **POS Frontend**: Vue 3 + Pinia + IndexedDB
- **Build**: Vite 7
- **Extras**: Meilisearch, Spatie Roles, Sanctum

---

## Architecture en 1 image

```
UTILISATEUR                  UTILISATEUR           UTILISATEUR
(Admin)                      (Caissier)            (Client)
  |                            |                       |
  v                            v                       v
NAVIGATEUR BO         /pos SPA (Vue 3)    APP EXTERNE (Vue/React)
  |                            |                       |
  +--auth via login            +--auth via PIN         +--Sanctum token
  |                            |                       |
  v                            v                       v
  /dashboard                /api/pos/*               /api/*
  /products                 Offline-capable         JSON API
  /suppliers                
  /financial                
  /blog                     
  (Blade templates)         (IndexedDB cache)       (No UI)
  |                          |                       |
  +-------+------------------+-------+---------------+
          |
          v
    LARAVEL 11
    (Routes + Models + Controllers)
          |
          v
    MySQL DATABASE
    (All shared models)
```

---

## Les 3 routes principales

| Route | Utilisateur | Interface | Purpose |
|-------|-----------|-----------|---------|
| `/dashboard` | Admin | Blade BO | Gestion tout (produits, stocks, finances) |
| `/pos` | Caissier | Vue 3 SPA | Ventes temps réel + offline |
| `/api/*` | Client app | JSON API | Consulter produits, passer commandes |

---

## Modèles clés à connaître

```
Product ←→ Store      (N:M) Produits par magasin
Product ←→ Category   (N:M) Produits par catégorie
Product ←→ Supplier   (N:M) Fournisseurs d'un produit

Sale ←→ SaleItem      (1:N) Items d'une vente
Sale ←→ Shift         (1:N) Shift d'une vente
SaleItem ←→ Product   (1:N) Quoi a-t-on vendu

StockBatch ←→ Product (1:N) Lots de stock par produit
StockBatch ←→ Store   (1:N) Où est le stock

FinancialTransaction  Débits/Crédits comptables
User ←→ Shift         (1:N) Caissier dans un shift
User ←→ Role          (N:M) Admin, manager, cashier
```

---

## Flux de travail typique

### 1. Admin crée un produit
```
Backoffice /products/create
↓
Remplit: EAN, Nom, Prix, Marque, Images
↓
POST /products
↓
ProductController::store()
  ├─ Crée Product row
  ├─ Scout reindex (Meilisearch)
  └─ Attache tous les magasins
↓
Produit visible en POS (via /api/pos/catalog)
```

### 2. Caissier vend un produit (POS)
```
/pos (Vue 3 SPA)
↓
Login PIN
↓
Select produit (depuis cache LocalStorage/IndexedDB)
↓
Ajouter au panier
↓
Choisir paiement (cash, card, split)
↓
POST /api/pos/sales/sync
↓
SyncController::sales()
  ├─ Crée Sale + SaleItem
  ├─ Décrémente stock (FIFO par StockBatch)
  ├─ Crée StockTransaction (log)
  └─ Crée FinancialTransaction (comptabilité)
↓
Vente enregistrée en DB
↓
Visible en BO /dashboard (nouvelle vente)
```

### 3. Comptable vérifie les finances
```
BO /financial/{store}/transactions
↓
FinancialTransactionController::index()
↓
Affiche toutes les transactions
├─ Date
├─ Compte (701 = Ventes, etc)
├─ Montant + Direction (débit/crédit)
├─ Solde courant (cumul)
└─ Utilisateur
↓
Peut exporter en Excel
```

---

## Routes principales par section

### Backoffice BO
```
/dashboard                    Accueil BO
/products                     Catalogue produits
/suppliers, /supplier-orders  Gestion fournisseurs
/resellers                    Gestion revendeurs
/stocks, /stock-movements    Gestion stocks
/financial/{store}/*         Comptabilité
/pages, /blog, /gift-boxes   Contenus
```

### POS
```
/pos                         SPA principale
/api/pos/products            Catalogue JSON
/api/pos/users               Utilisateurs avec PIN
/api/pos/catalog/{storeId}   Filtre par magasin
/api/pos/shifts/*            Gestion shifts
/api/pos/sales/sync          Sync ventes
```

### Front API (External)
```
POST /api/login              Connexion client
POST /api/register           Inscription
GET  /api/products           Produits
POST /api/cart               Ajouter panier
POST /api/checkout           Commander
```

---

## Contrôleurs essentiels

| Contrôleur | Rôle |
|-----------|------|
| **DashboardController** | Vue d'ensemble BO |
| **ProductController** | CRUD produits + images + variations |
| **POS/SyncController** | Synchronise ventes (CRITIQUE) |
| **POS/ShiftController** | Gère shifts/quarts |
| **FinancialTransactionController** | Enregistre débits/crédits |
| **SupplierOrderController** | Commandes fournisseurs |
| **ResellerController** | Gestion revendeurs |

---

## Fichiers clés à modifier

### Pour ajouter une page d'admin
1. `/routes/web.php` - Ajouter la route
2. `/app/Http/Controllers/XxxController.php` - Créer le contrôleur
3. `/app/Models/Xxx.php` - Vérifier le modèle
4. `/resources/views/xxx/` - Créer la vue Blade

### Pour ajouter une API POS endpoint
1. `/routes/web.php` - Ajouter route /api/pos/*
2. `/app/Http/Controllers/POS/SyncController.php` - Ajouter method
3. POS JavaScript - Appeler l'endpoint

### Pour ajouter un modèle
1. `php artisan make:model Xxx -m` - Créer model + migration
2. `/database/migrations/` - Éditer la migration
3. `/app/Models/Xxx.php` - Ajouter les relations
4. `php artisan migrate` - Appliquer

---

## Commandes utiles

```bash
# Voir toutes les routes
php artisan route:list

# Voir routes d'une partie
php artisan route:list | grep /dashboard
php artisan route:list | grep /api/pos

# Ouvrir la console Laravel
php artisan tinker

# Créer un produit via tinker
>>> Product::create(['ean' => '123', 'name' => ['en' => 'Test']])

# Reindexer Meilisearch
php artisan scout:import "App\\Models\\Product"

# Voir logs
tail -f storage/logs/laravel.log

# Faire un test
php artisan test tests/Unit/ProductTest.php
```

---

## Authentification rapide

### Backoffice
```php
// Middleware: auth
// Guard: web (session)
// Authorization: middleware('role:admin')
// Login: Email + Password
```

### POS
```javascript
// No middleware required
// Auth: PIN code (via /api/pos/users)
// Storage: LocalStorage (session_id)
// Client-side validation only
```

### Front API
```php
// Middleware: auth:sanctum
// Guard: api
// Login: POST /api/login (returns token)
// Token: Sanctum personal access token
```

---

## Support offline (POS)

### LocalStorage
```javascript
// Cache produits + moyens de paiement
pos_catalog_cache_v1_store_{storeId}

// Cache arbre catégories
pos_category_tree_store_{storeId}

// Cumuls cash in/out
pos_cash_in_total
pos_cash_out_total
```

### IndexedDB (via Dexie)
```javascript
// Base de données locale
db.table('users')    // Utilisateurs avec PIN
db.table('catalog')  // Produits
db.table('payments') // Moyens de paiement
```

### Synchronisation
- Automatique à chaque vente (si connexion)
- Batch quand offline (Web Worker)
- Force sync via bouton menu POS

---

## Migration DB importante

```bash
# Créer table
php artisan make:migration create_xxx_table

# Voir les migrations
php artisan migrate:status

# Appliquer migration
php artisan migrate

# Rollback dernière
php artisan migrate:rollback

# Rollback tout
php artisan migrate:reset
```

---

## Config importante

| Fichier | Rôle |
|---------|------|
| `.env` | Variables d'environnement (DB, app keys) |
| `config/app.php` | App name, locale, timezone |
| `config/database.php` | MySQL connection |
| `config/scout.php` | Meilisearch config |
| `config/permission.php` | Spatie Roles setup |

---

## Dépannage rapide

### Q: "Product not found in Meilisearch"
A: Run `php artisan scout:import "App\\Models\\Product"`

### Q: "Vente ne se synchronise pas"
A: Vérifier `/api/pos/sales/sync` response, check logs

### Q: "Stock négatif en POS"
A: Check `allow_overselling` flag sur Product

### Q: "Utilisateur ne peut pas se connecter"
A: Vérifier role via `php artisan tinker` → `User::with('roles')->find(1)`

### Q: "POS offline ne fonctionne pas"
A: Vérifier LocalStorage via DevTools → Application tab

---

## Points de vigilance

1. **Stock FIFO** : Décrémentation stricte par créating_at
2. **Balances** : FinancialTransaction.balance_after = cumul (important!)
3. **Multilingue** : Product.name/description en array JSON
4. **Permissions** : Vérifier role:admin middleware
5. **Meilisearch** : Requis pour /products search
6. **Offline POS** : Réessai auto quand connexion revient

---

## Prochaines étapes

### Pour commencer
1. [ ] Lire ARCHITECTURE_SUMMARY.md (20 min)
2. [ ] Explorer `/routes/web.php`
3. [ ] Créer un test endpoint

### Pour développer une feature
1. [ ] Identifier la route dans `/routes/web.php`
2. [ ] Créer/modifier le contrôleur
3. [ ] Créer/modifier le modèle
4. [ ] Ajouter la vue Blade
5. [ ] Tester (unit + integration)

### Pour débugger
1. [ ] Vérifier les logs : `tail -f storage/logs/laravel.log`
2. [ ] Utiliser `dd()` ou `Log::info()`
3. [ ] Ouvrir DevTools (Network, LocalStorage)
4. [ ] Tester via `tinker`

---

**Besoin de plus de détails ?**
- ARCHITECTURE_SUMMARY.md (2-3 pages, vue d'ensemble)
- ARCHITECTURE_ANALYSIS.md (200+ pages, tout en détail)
- ARCHITECTURE_DIAGRAMS.md (100+ pages, diagrammes)
- ARCHITECTURE_INDEX.md (guide de navigation)

