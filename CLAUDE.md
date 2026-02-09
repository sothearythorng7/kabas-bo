# Kabas Concept Store - Contexte Projet

> Ce fichier contient toutes les informations nécessaires pour comprendre et travailler sur le projet Kabas.
> Dernière mise à jour: 2026-02-09

## Vue d'Ensemble

**Kabas Concept Store** est un système complet de gestion commerciale composé de **4 parties** :

| Partie | Répertoire | Description |
|--------|------------|-------------|
| **Back Office (BO)** | `/var/www/kabas` | Administration, gestion stocks, fournisseurs, comptabilité |
| **Point of Sale (POS)** | `/var/www/kabas` | Caisse PWA offline-first pour tablettes/mobiles |
| **Reception PWA** | `/var/www/kabas` | Application spécialisée pour réception marchandises |
| **Site Public** | `/var/www/kabas-site` | E-commerce B2C multilingue |

---

## Stack Technique

### Backend (Laravel 12)
```
PHP 8.2+ | Laravel 12.0 | MySQL
Sanctum (API auth) | Spatie Permission (rôles) | Spatie Translatable (i18n)
Scout + Meilisearch (recherche) | DomPDF (PDF) | Maatwebsite Excel
```

### Frontend
```
Vue.js 3 + Pinia (POS) | Bootstrap 5 + Tailwind CSS 4
Vite 7 | Dexie.js (IndexedDB offline) | TinyMCE
```

### Infrastructure
```
Queue: database | Cache: database | Session: database
Meilisearch (search) | Telegram (notifications) | Gmail SMTP
```

---

## 1. BACK OFFICE (BO)

### Accès
- URL: `https://bo.kabasconceptstore.com`
- Auth: Laravel standard (users table)
- Rôles: Admin, Manager, Staff, etc. (Spatie Permission)

### Modules Principaux

| Module | Contrôleur | Description |
|--------|------------|-------------|
| **Dashboard** | `DashboardController` | Vue d'ensemble, alertes stock, ventes |
| **Produits** | `ProductController` | Catalogue, variations, photos, EAN |
| **Stocks** | `StockController`, `StockMovementController` | Inventaire, transferts inter-magasins |
| **Fournisseurs** | `SupplierController`, `SupplierOrderController` | Commandes, réception, paiements |
| **Revendeurs** | `ResellerController`, `ResellerSalesReportController` | Livraisons, rapports, invoices |
| **Comptabilité** | `Financial/*Controller` | Comptes, transactions, journaux |
| **Fabrique** | `Factory/*Controller` | Matières premières, recettes, production |
| **BI** | `BI/BIDashboardController` | Business Intelligence |

### Routes Clés
```
/dashboard                     → Tableau de bord principal
/products                      → Gestion catalogue
/stocks                        → Inventaire
/stock-movements               → Transferts de stock
/suppliers/{id}/orders         → Commandes fournisseur
/resellers/{id}/reports        → Rapports revendeurs
/financial/{store}/*           → Comptabilité par magasin
/factory/*                     → Module production
```

---

## 2. POINT OF SALE (POS)

### Accès
- URL: `/pos`
- Auth: PIN utilisateur ou login
- Mode: PWA offline-first

### Architecture
```
resources/js/pos/
├── store/          # Pinia stores (user, cart, sync)
├── components/     # Composants Vue
├── views/          # Écrans principaux
└── api/            # Client API
```

### API Endpoints (Préfixe: `/api/pos/`)
```
GET  /catalog/{storeId}        → Catalogue produits
GET  /search/{storeId}         → Recherche produits
POST /shifts/start             → Ouvrir caisse
POST /shifts/end               → Fermer caisse
POST /shifts/change-user       → Changement caissier
POST /sales/sync               → Synchroniser ventes
GET  /exchange/lookup-sale     → Recherche vente pour échange
POST /exchange/process         → Traiter échange
GET  /voucher/validate         → Valider bon d'achat
POST /voucher/apply            → Appliquer bon d'achat
```

### Fonctionnalités
- Ventes en mode offline (sync automatique)
- Gestion des shifts (ouverture/fermeture caisse)
- Multi-utilisateurs par shift
- Échanges/retours avec vouchers
- Scan EAN/barcode

---

## 3. RECEPTION PWA

### Accès
- URL: `/reception`
- Auth: PIN (middleware `ReceptionAuth`)

### Routes (Préfixe: `/reception/`)
```
/                              → Login PIN
/home                          → Dashboard
/orders                        → Commandes à recevoir
/orders/{order}                → Réception articles
/refill                        → Réapprovisionnement rapide
/returns                       → Retours fournisseur
/check-price                   → Scanner de prix
/transfers                     → Transferts inter-magasins
```

### Contrôleur Principal
`Reception/ReceptionController` - Toute la logique de réception

---

## 4. SITE PUBLIC (E-COMMERCE)

### Répertoire
`/var/www/kabas-site`

### Accès
- URL: `https://www.kabasconceptstore.com`
- Locales: `/fr`, `/en`

### Architecture Multi-DB
```
DB Principale (kabas)     │  DB Backoffice (partagée)
─────────────────────────────────────────────────────
• Customers               │  • Products
• Orders, OrderItems      │  • Categories
• Carts, CartItems        │  • Brands
• Sessions                │  • GiftBoxes, GiftCards
```

### Routes Clés
```
/{locale}                      → Accueil
/{locale}/product/{slug}       → Fiche produit
/{locale}/c/{slug}             → Catégorie
/{locale}/search               → Recherche
/{locale}/cart                 → Panier
/{locale}/login                → Connexion client
/{locale}/account              → Dashboard client
```

### Fonctionnalités
- Catalogue multilingue (FR/EN)
- Panier sans authentification
- Checkout guest avec paiement ABA PayWay (popup iframe)
- Stock déduit du Warehouse en FIFO + transaction financière automatique
- Compte client (commandes, adresses)
- Coffrets cadeaux & cartes cadeaux
- Blog intégré
- Recherche avec autocomplete

---

## Modèles Métier Principaux

### Core
| Modèle | Table | Description |
|--------|-------|-------------|
| `Product` | products | Produits avec variations, photos |
| `Category` | categories | Catégories hiérarchiques (3 niveaux) |
| `Brand` | brands | Marques |
| `Store` | stores | Magasins (normal ou entrepôt) |
| `User` | users | Utilisateurs BO/POS |

### Ventes & Stock
| Modèle | Table | Description |
|--------|-------|-------------|
| `Sale` | sales | Ventes POS |
| `SaleItem` | sale_items | Lignes de vente |
| `Shift` | shifts | Sessions de caisse |
| `StockBatch` | stock_batches | Lots de stock par magasin |
| `StockMovement` | stock_movements | Transferts de stock |

### Fournisseurs
| Modèle | Table | Description |
|--------|-------|-------------|
| `Supplier` | suppliers | Fournisseurs (achat/consignment) |
| `SupplierOrder` | supplier_orders | Commandes d'achat |
| `SupplierReturn` | supplier_returns | Retours fournisseur |
| `Refill` | refills | Réapprovisionnements simples |

### Revendeurs
| Modèle | Table | Description |
|--------|-------|-------------|
| `Reseller` | resellers | Revendeurs |
| `ResellerStockDelivery` | reseller_stock_deliveries | Livraisons |
| `ResellerSalesReport` | reseller_sales_reports | Rapports de vente |
| `ResellerInvoice` | reseller_invoices | Factures |

### Comptabilité
| Modèle | Table | Description |
|--------|-------|-------------|
| `FinancialTransaction` | financial_transactions | Transactions comptables |
| `FinancialAccount` | financial_accounts | Comptes (actif, passif) |
| `FinancialJournal` | financial_journals | Journaux |
| `GeneralInvoice` | general_invoices | Factures générales |

### Échanges & Vouchers
| Modèle | Table | Description |
|--------|-------|-------------|
| `Exchange` | exchanges | Échanges clients |
| `ExchangeItem` | exchange_items | Articles échangés |
| `Voucher` | vouchers | Bons d'achat/crédits |

### Fabrique
| Modèle | Table | Description |
|--------|-------|-------------|
| `RawMaterial` | raw_materials | Matières premières |
| `Recipe` | recipes | Recettes de production |
| `Production` | productions | Ordres de production |

---

## Services Métier

### `/app/Services/`
| Service | Fichier | Rôle |
|---------|---------|------|
| `ExchangeService` | 22.5KB | Gestion complète des échanges/retours |
| `VoucherService` | 6KB | Création/validation vouchers |
| `TelegramService` | 1.4KB | Notifications Telegram |

---

## Commandes Artisan Utiles

```bash
# Développement
php artisan serve                    # Serveur local
php artisan tinker                   # REPL

# Base de données
php artisan migrate                  # Exécuter migrations
php artisan migrate:fresh --seed     # Reset + seed

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recherche (Meilisearch)
php artisan scout:import "App\Models\Product"

# Queues
php artisan queue:work

# Custom
php artisan vouchers:expire          # Expirer vouchers
php artisan fix:sale-totals          # Corriger totaux ventes
```

---

## Structure des Fichiers Clés

```
/var/www/kabas/
├── app/
│   ├── Http/Controllers/
│   │   ├── Financial/           # Comptabilité
│   │   ├── POS/                 # API POS
│   │   ├── Reception/           # PWA Réception
│   │   ├── Factory/             # Module production
│   │   ├── BI/                  # Business Intelligence
│   │   └── *.php                # Contrôleurs principaux
│   ├── Models/                  # 85 modèles Eloquent
│   └── Services/                # Logique métier
├── routes/
│   ├── web.php                  # Routes web (600+)
│   ├── api.php                  # Routes API
│   └── console.php              # Commandes
├── resources/
│   ├── views/                   # 150+ templates Blade
│   ├── js/pos/                  # Application POS Vue.js
│   └── lang/                    # Traductions (en, fr)
├── database/
│   └── migrations/              # 151 migrations
└── config/
    └── menu.php                 # Configuration menu BO

/var/www/kabas-site/
├── app/
│   ├── Http/Controllers/        # 11 contrôleurs
│   │   └── Customer/            # Espace client
│   └── Models/                  # 20+ modèles
├── routes/web.php               # Routes multilingues
└── resources/views/             # Templates e-commerce
```

---

## Configuration Importante

### .env (kabas)
```env
APP_URL=https://bo.kabasconceptstore.com
DB_DATABASE=kabas
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
TELEGRAM_BOT_TOKEN=...
TELEGRAM_CHAT_ID=...
```

### .env (kabas-site)
```env
APP_URL=https://www.kabasconceptstore.com
DB_DATABASE=kabas
BACKOFFICE_DB_DATABASE=kabas
BO_API_URL=https://bo.kabasconceptstore.com/api
```

---

## Conventions de Code

### Nommage
- Contrôleurs: `PascalCase` + `Controller` suffix
- Modèles: `PascalCase` singulier
- Tables: `snake_case` pluriel
- Routes: `kebab-case`
- Vues: `snake_case` ou `kebab-case`

### Patterns Utilisés
- Repository Pattern (partiel)
- Service Layer (ExchangeService, VoucherService)
- Observer Pattern (modèles)
- Event/Listener (notifications)

---

## Module Website Orders (ajouté 2026-02-09)

Gestion des commandes e-commerce depuis le BO. Les commandes sont créées par le site (`/var/www/kabas-site`) et partagent la même base de données.

### Fichiers créés

| Fichier | Rôle |
|---------|------|
| `app/Models/WebsiteOrder.php` | Modèle `orders` (préfixé "Website" pour éviter conflit avec supplier orders). Relations: items, transactions. Scopes: byStatus, byPaymentStatus, search |
| `app/Models/WebsiteOrderItem.php` | Modèle `order_items`. Relations: order, product, giftBox, giftCard |
| `app/Models/WebsitePaymentTransaction.php` | Modèle `payment_transactions`. Accessor: status_description (codes PayWay 0-11) |
| `app/Http/Controllers/WebsiteOrderController.php` | index, show, updateStatus (+ annulation complète), updateNotes |
| `resources/views/website-orders/index.blade.php` | Liste paginée avec filtres, badges colorés, compteurs |
| `resources/views/website-orders/show.blade.php` | Détail: client, adresse, items, totaux, transactions PayWay, formulaires status/notes |
| `config/payway.php` | Config PayWay pour le BO (merchant_id, api_key, refund_url, rsa_public_key) |

### Fichiers modifiés

| Fichier | Modifications |
|---------|---------------|
| `config/menu.php` | +Website Orders dans submenu Website (icon bi-bag-check) |
| `routes/web.php` | +4 routes: website-orders (index, show, update-status, update-notes) |
| `resources/lang/en/messages.php` | +menu.website_orders, +main_dashboard.*, +website_order.* (~70 clés) |
| `resources/lang/fr/messages.php` | Idem en français |
| `app/Http/Controllers/DashboardController.php` | +Widget commandes payées ventilées par statut |
| `resources/views/dashboard.blade.php` | +Tableau paid website orders (status, count, amount) |

### Routes

```
GET  /website-orders              → index (liste + filtres)
GET  /website-orders/{order}      → show (détail)
POST /website-orders/{order}/status → updateStatus
POST /website-orders/{order}/notes  → updateNotes
```

### Annulation d'une commande payée

Quand on annule une commande payée depuis le BO, `updateStatus()` exécute :

1. **Remboursement PayWay** (`refundPayWay()`) : RSA encrypt merchant_auth + HMAC-SHA512 hash + POST refund API
2. **Réversion stock** (`reverseStock()`) : Re-ajoute les quantités au dernier lot, crée stock_transaction (type=in, reason=website_cancellation)
3. **Réversion financière** (`reverseFinancialTransaction()`) : Crée transaction debit sur compte 701, running balance
4. Met à jour `payment_transactions` (internal_status=refunded, refunded_at, refund_amount)

**Dégradation gracieuse** : Si le remboursement PayWay échoue (pas de clef RSA, erreur API), la réversion interne (stock + finance) est quand même effectuée avec un warning.

**Constantes** : `FINANCIAL_ACCOUNT_ID=17` (701 Shop Sales), `SYSTEM_USER_ID=1`, store par défaut `store_id=3` (Warehouse).

### TODO PayWay

- **RAPPEL** : Demander à ABA Bank la clef RSA publique pour les remboursements → `PAYWAY_RSA_PUBLIC_KEY` dans `.env`
- **Ajouter au `.env` du BO** : `PAYWAY_MERCHANT_ID`, `PAYWAY_API_KEY` (mêmes valeurs que le site)

---

## Notes de Développement

### Migrations Récentes (Dec 2025)
- Échanges clients avec vouchers
- Intégration comptable des échanges
- Services personnalisés dans ventes
- Retours fournisseur
- Cash in/out pour shifts

### Migrations Récentes (Feb 2026)
- `add_store_id_to_orders_table` : store_id (default 3 = Warehouse) sur table orders

### Points d'Attention
1. **Multi-magasins**: Toujours filtrer par `store_id`
2. **Stock par lot**: Utiliser `StockBatch` pour traçabilité
3. **Offline POS**: Sync via `SyncController`
4. **Traductions**: JSON dans colonnes pour produits/catégories
5. **Comptabilité**: Toujours créer `FinancialTransaction` pour mouvements d'argent
6. **Commandes website**: Stock déduit du Warehouse (store_id=3) en FIFO, transaction financière sur compte 701

---

## Contacts & Ressources

- **Meilisearch**: http://127.0.0.1:7700
- **Logs**: `/var/www/kabas/storage/logs/laravel.log`
- **PHPUnit**: `./vendor/bin/phpunit`

---

*Ce fichier est destiné à être lu par Claude Code pour reprendre le contexte du projet.*
