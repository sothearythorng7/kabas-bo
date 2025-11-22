# INDEX DE DOCUMENTATION - ARCHITECTURE KABAS

Cette documentation complète couvre les 3 composantes du projet KABAS en profondeur.

## Fichiers de documentation créés

### 1. ARCHITECTURE_SUMMARY.md (COMMENCER ICI)
**Résumé exécutif - 2-3 pages**
- Vue d'ensemble des 3 parties (BO, FRONT, POS)
- Routes principales par section
- Modèles de données clés
- Architecture technique
- Cas d'usage typiques
- Fichiers clés à connaître

**Lecteur cible:** Tout nouveau développeur, managers

---

### 2. ARCHITECTURE_ANALYSIS.md (TRÈS DÉTAILLÉ)
**Analyse complète - 200+ pages**
- Routes web.php lignes par lignes (groupées par fonctionnalité)
- Routes api.php
- Tous les contrôleurs (60+) avec descriptions
- Tous les modèles (80+) avec relations
- Vues Blade par section (45+ templates)
- JavaScript/CSS Backoffice
- Architecture POS (Vue 3)
- Authentification et autorisation
- Configuration et migrations
- Services et intégrations
- Points d'entrée par rôle
- Navigation Backoffice et POS
- Technologies et dépendances
- Résumé exécutif par aspect

**Lecteur cible:** Architectes, développeurs senior

---

### 3. ARCHITECTURE_DIAGRAMS.md (VISUEL)
**Diagrammes ASCII détaillés - 100+ pages**
1. Architecture générale du système (DB → Laravel → 3 interfaces)
2. Flux des routes et requêtes (16 sections principales)
3. Contrôleurs et flux de traitement (2 exemples complets)
4. Flux de données complet (création produit + vente POS)
5. Modèle de données avec relations
6. Architecture Vue 3 POS
7. Middleware et authentification
8. Cycle de vie transaction financière (avec exemple)

**Lecteur cible:** Architectes, lead devs, (pour presentations)

---

## Navigation rapide par besoin

### Je veux comprendre rapidement le projet
→ Lire: **ARCHITECTURE_SUMMARY.md** (20 min)

### Je dois coder une nouvelle fonctionnalité
→ Lire: 
1. ARCHITECTURE_SUMMARY.md (vue d'ensemble)
2. Routes correspondantes dans ARCHITECTURE_ANALYSIS.md
3. Contrôleur cible dans ARCHITECTURE_ANALYSIS.md
4. Modèles concernés dans ARCHITECTURE_ANALYSIS.md

### Je dois debugger une vente POS
→ Lire:
1. "Vente en POS" section dans ARCHITECTURE_SUMMARY.md
2. "SyncController" dans ARCHITECTURE_ANALYSIS.md (3.2)
3. "Vente en POS (Offline → Sync)" dans ARCHITECTURE_DIAGRAMS.md

### Je dois ajouter une route API
→ Lire:
1. Routes API existantes dans ARCHITECTURE_ANALYSIS.md (1.2.E)
2. Contrôleur correspondant dans ARCHITECTURE_ANALYSIS.md
3. Modèle utilisé

### Je dois optimiser les requêtes DB
→ Lire:
1. Relations modèles dans ARCHITECTURE_DIAGRAMS.md (section 5)
2. Modèles concernés dans ARCHITECTURE_ANALYSIS.md (1.3)
3. Vues Blade correspondantes

### Je dois implémenter l'authentification
→ Lire:
1. "Authentification et autorisation" dans ARCHITECTURE_ANALYSIS.md
2. "Middleware et authentification" dans ARCHITECTURE_DIAGRAMS.md (section 7)
3. User model dans ARCHITECTURE_ANALYSIS.md

### Je dois intégrer une nouvelle API externe
→ Lire:
1. "Services et intégrations" dans ARCHITECTURE_ANALYSIS.md
2. Configuration dans ARCHITECTURE_ANALYSIS.md (4.1)

---

## Structure des 3 composantes

```
BACKOFFICE
├─ Routes: /routes/web.php (lignes 1-403)
│  ├─ Auth routes
│  ├─ Dashboard
│  ├─ Admin routes
│  └─ (avec role:admin middleware)
├─ Controllers: /app/Http/Controllers/ (50+ fichiers)
├─ Models: /app/Models/ (80+ fichiers)
├─ Views: /resources/views/ (45+ fichiers Blade)
└─ Styles: /resources/sass/app.scss

FRONT (API uniquement)
├─ Routes: /routes/api.php (11 routes)
├─ Controllers: /app/Http/Controllers/Api/ (4 fichiers)
├─ Models: Réutilise modèles Backoffice
└─ Frontend: EXTERNE (Vue/React/Native)

POS (Single Page App)
├─ Routes: /pos (SPA view) + /api/pos/* (API)
├─ View: /resources/views/pos/
│  ├─ index.blade.php (layout)
│  └─ screens/ (10 screens Blade)
├─ Controllers: /app/Http/Controllers/POS/ (3 fichiers)
├─ Models: Réutilise modèles Backoffice
├─ Vue 3: /resources/js/pos/ (SPA components)
└─ JavaScript: /public/js/pos/ (vanilla, IndexedDB)
```

---

## Technologies par section

### Backoffice
- **Backend**: Laravel 11, MySQL
- **Frontend**: Blade, Bootstrap 5, Vanilla JS
- **Build**: Vite 7
- **Utilities**: Meilisearch, Excel, PDF, Roles

### Front API
- **Backend**: Laravel 11 (Sanctum tokens)
- **Frontend**: External (Vue/React/Native)

### POS
- **Backend**: Laravel 11, MySQL
- **Frontend**: Vue 3, IndexedDB
- **Build**: Vite 7
- **Offline**: LocalStorage + IndexedDB

---

## Modèles clés à comprendre

1. **Product** (80+ lignes)
   - Multilingue (name, description, slugs)
   - Relations: Brand, Categories, Suppliers, Stores, Images
   - Scout Searchable

2. **Sale** + **SaleItem** (ventes)
   - Shift, Store, Payment type
   - Support split_payments (multiples moyens)
   - is_delivery flag (articles livraison)

3. **StockBatch** (stock FIFO)
   - Lot de stock par Product + Store
   - Quantity (décrémente à la vente)
   - created_at (ordre FIFO)

4. **FinancialTransaction** (comptabilité)
   - Direction (debit/credit)
   - Balance_after (cumul)
   - Account (701, 401, 411, etc.)

5. **User**
   - pin_code (POS login)
   - store_id (affectation magasin)
   - Roles (via Spatie)

---

## Points d'entrée principaux

### Route Entry Points
```
/ → Accueil (auth check)
/dashboard → BO (admin)
/pos → POS (caissier)
/api/pos/* → POS API
/api/* → Front API (externe)
```

### Controller Entry Points
```
DashboardController → BO principal
ProductController → Catalogue
POS/SyncController → Ventes
Financial/* → Comptabilité
```

### Model Entry Points
```
Product → Tout tourne autour
Sale → Ventes
FinancialTransaction → Comptabilité
```

---

## Migrations importantes

**Stock:**
- products, stock_batches, stock_movements
- stock_transactions, stock_movements

**Ventes:**
- sales, sale_items, shifts
- cash_transactions

**Finances:**
- financial_accounts, financial_transactions
- financial_payment_methods, financial_journals

**Fournisseurs:**
- suppliers, supplier_orders
- purchase_price_history

**Revendeurs:**
- resellers, reseller_stock_deliveries
- reseller_sales_reports, reseller_invoices

---

## Configuration importante

```
config/scout.php          → Meilisearch
config/permission.php     → Spatie Roles
config/sanctum.php        → API tokens
config/mail.php          → Email
config/database.php      → MySQL
config/site_paths.php    → Frontend paths
```

---

## Flux critiques à comprendre

1. **Création produit**
   - ProductController::create/store()
   - Scout reindex
   - Attach to stores (stock=0)
   - POS récupère via API

2. **Vente en POS**
   - PIN login → Local cache
   - Ajouter au panier (offline)
   - Sync → SyncController::sales()
   - FIFO stock decrement
   - FinancialTransaction création

3. **Commande fournisseur**
   - Create → SupplierOrderController
   - Validation → Order status
   - Réception → Stock increment
   - Facture → FinancialTransaction

4. **Gestion revendeur**
   - Create → ResellerController
   - Livraison → StockDeliveryController
   - Rapport ventes → SalesReportController
   - Facturation → InvoiceController

---

## Checklist pour nouveau développeur

- [ ] Lire ARCHITECTURE_SUMMARY.md
- [ ] Explorer routes/web.php et comprendre la structure
- [ ] Identifier ta tâche dans routes/web.php
- [ ] Lire le contrôleur concerné
- [ ] Lire le modèle concerné
- [ ] Vérifier les tests existants
- [ ] Coder
- [ ] Tester (unit + integration)

---

## Commandes utiles Laravel

```
# Routes
php artisan route:list | grep posts

# Modèles
php artisan tinker
>>> Product::with('brand')->first()

# Migrations
php artisan migrate:status
php artisan migrate

# Meilisearch
php artisan scout:import "App\\Models\\Product"

# Testing
php artisan test

# Debugging
dd($variable)
Log::info('message')
\DB::enableQueryLog()
```

---

## Contacts par fonctionnalité

*(À adapter à ton équipe)*

- **Backoffice** → Frontend team
- **POS** → POS specialist
- **Finances** → Accounting team
- **DB/Perf** → DevOps/DBA

---

## Ressources externes

- Laravel 11: https://laravel.com/docs/11
- Vue 3: https://vuejs.org/
- Meilisearch: https://www.meilisearch.com/
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Bootstrap: https://getbootstrap.com/

---

**Dernière mise à jour**: 2025-02-14
**Status**: Complete deep analysis
**Format**: Markdown
**Fichiers concernés**: 463+ fichiers analysés

