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

## Workflow Dev / Prod (depuis 2026-05-11)

Le projet dispose désormais d'un environnement de dev parallèle à la prod, sur le même serveur.

### Environnements

| Environnement | BO | Site | DB | Codebase BO | Codebase site |
|---|---|---|---|---|---|
| **PROD** | bo.kabasconceptstore.com | www.kabasconceptstore.com | `kabas` | `/var/www/kabas/` | `/var/www/kabas-site/` |
| **DEV** | testing-bo.kabasconceptstore.com (basic auth) | testing.kabasconceptstore.com (basic auth) | `kabas_dev` | `/var/www/kabas-dev/` | `/var/www/kabas-site-dev/` |

DEV partage l'IP de prod ; les vhosts Apache distinguent par ServerName + HTTP basic auth (`kabas-dev` user, mot de passe à demander).

### Services externes en dev

| Service | Prod | Dev |
|---|---|---|
| SMTP | Gmail | `log` (`MAIL_MAILER=log`) |
| Telegram | actif | vide |
| GA4 / Google Ads | actif | désactivé via `TRACKING_ENABLED=false` |
| PayWay | sandbox ABA | **non testable** (domaine pas en whitelist ABA) |
| Meilisearch | indexes `products`, `raw_materials`, `suppliers` | indexes `dev_products`, `dev_raw_materials`, `dev_suppliers` |
| Crons | actifs | actifs (inoffensifs car services externes silencieux) |

### Branches et PR

```
[contributeur] feature/<topic>  ou  fix/<topic>  (depuis dev)
      ↓ commits, push
[GitHub PR] feature/* → dev
      ↓ review + merge (Alexis ou Mickaël)
[Mickaël seul] PR dev → main
      ↓ merge
[Mickaël] SSH prod : `kabas-deploy bo`  ou  `kabas-deploy site`
      ↓ git pull --ff-only + migrate + cache + (npm build pour site)
[prod live]
```

Convention de noms : `feature/<topic-kebab>` pour les nouveautés, `fix/<topic>` pour les correctifs. Pas de préfixe auteur.

**Hotfix prod** : pas de raccourci, le passage par dev est obligatoire même pour un bug critique.

### Storage photos

- `/var/www/kabas-dev/storage/app/public/products/` est un **symlink readonly** vers `/var/www/kabas/storage/app/public/products/` (les photos prod sont visibles depuis dev en lecture). Idem pour `blog`, `hero_slides`, `stores`.
- `/var/www/kabas-dev/storage/app/public/products-dev/` est **writable** pour les uploads dev (si jamais le code les y dirige).
- `/var/www/kabas-site-dev/public/storage` symlink vers `/var/www/kabas-dev/storage/app/public/` (mirror de la prod : le site lit le storage du BO).

### Backup `kabas_dev`

Cron nocturne `/etc/cron.d/kabas-dev-backup` : mysqldump quotidien à 03:00, rétention 7 jours, sauvegardes dans `/var/backups/kabas_dev/`.

### Alexis (consultant SEO + dev)

- Compte Linux `alexis-claude` (uid 1003) avec accès :
  - **Prod** : lecture code OK, écriture interdite. SQL prod limité aux tables SEO via user `alexis_seo`.
  - **Dev** : R/W complet sur les deux codebases (via ACL Linux). SQL `kabas_dev` complet via user `alexis-claude`.
- Sudoers étendu : peut lancer `php artisan` sur les deux dev paths en tant que `www-data`. Sur la prod, uniquement le wrapper `seo-fix` (`products:apply-seo-fixes`).
- Doc dédiée : `/home/alexis-claude/seo/CLAUDE.md` et `/home/alexis-claude/seo/APP_BRIEF.md`.

### Communication Claude↔Claude

Dossier partagé `/var/shared/claude-comms/` (groupe `claude-shared`) :
- `from-alexis/YYYY-MM-DD-<topic>.md` : briefs/demandes d'Alexis vers Mickaël
- `from-mickael/YYYY-MM-DD-<topic>.md` : réponses/instructions de Mickaël

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

## Module Règles Promotionnelles (à implémenter — en attente validation client)

> Statut : **SPEC VALIDÉE EN INTERNE** — En attente de validation client avant implémentation.
> Discussion : 2026-02-12

### Objectif

Module permettant de définir des règles promotionnelles applicables sur le site e-commerce et/ou le POS. Architecture extensible pour ajouter de nouveaux types de règles à l'avenir.

### Types de règles (phase 1)

| Type | ID | Déclencheur | Effet |
|------|----|------------|-------|
| **Livraison gratuite** | `free_shipping` | Total panier (après remises) >= seuil | Frais de livraison = 0$ |
| **Buy X Get Y Free** | `buy_x_get_y` | Quantité d'un produit >= X | +Y unités gratuites du même produit ajoutées automatiquement |
| **Produit cadeau** | `gift_product` | Total panier >= seuil OU achat de produits spécifiques | Produit cadeau (défini dans la règle) ajouté automatiquement |

### Paramètres communs à chaque règle

| Paramètre | Type | Obligatoire | Description |
|-----------|------|-------------|-------------|
| Nom | string | oui | Nom interne de la promo |
| Description | text | non | Description interne |
| Type de règle | enum | oui | `free_shipping`, `buy_x_get_y`, `gift_product` |
| Canal | enum | oui | `pos`, `website`, `both` |
| Cumulative | boolean | oui | Si `false` (exclusive) et conflit → la plus avantageuse pour le client gagne |
| Date début | date | oui | Début de validité |
| Date fin | date | non | `null` = permanente (ex: livraison gratuite permanente) |
| Limite utilisations globale | int | non | `null` = illimité |
| Limite par client | int | non | `null` = illimité |
| Budget max ($) | decimal | non | Coût cumulé max des cadeaux offerts ; désactivation auto si dépassé |
| Pays ciblés | relation | oui | Tous les pays livrables OU sélection spécifique |
| Actif | boolean | oui | On/off manuel |

### Paramètres spécifiques par type

**`free_shipping`** :
- Seuil minimum ($) — basé sur le total après remises

**`buy_x_get_y`** :
- Produit(s) concernés (relation products)
- Quantité X requise (int)
- Quantité Y offerte (int)

**`gift_product`** :
- Produit(s) déclencheurs OU seuil en $
- Produit cadeau à offrir (relation product)

### Comportements automatiques

1. **Ajout auto** des produits gratuits au panier (site) et à la vente (POS)
2. **Messages traduits** (FR/EN) **générés automatiquement** :
   - Message d'explication quand la promo est appliquée ("1 x Produit offert grâce à la promo X !")
   - Message incitatif dans le panier quand le client est proche du seuil ("Plus que X$ pour bénéficier de...")
3. **Désactivation automatique** si :
   - Rupture de stock du produit cadeau (types `buy_x_get_y` et `gift_product`)
   - Budget max atteint
   - Date fin dépassée
4. **Résolution de conflits** : si plusieurs règles exclusives s'appliquent → la plus avantageuse pour le client est retenue

### Impact comptable et stock

- Produit offert : **sortie stock normale** (Warehouse FIFO, comme une vente)
- **Transaction financière spécifique** sur un compte dédié "charges promotionnelles" (nouveau `FinancialAccount` à créer)
- Le budget max se calcule sur le cumul de ces transactions
- Traçabilité complète : chaque produit offert est lié à la règle promo qui l'a déclenché

### Gestion depuis le Back Office

- CRUD complet des règles promotionnelles
- Dashboard avec coût et nombre d'utilisations par promo
- Activation/désactivation manuelle

### Périmètre d'implémentation

| Composant | Répertoire | Impact |
|-----------|------------|--------|
| BO (gestion règles) | `/var/www/kabas` | Nouveau module : contrôleur, modèles, vues, migrations |
| Site (application règles) | `/var/www/kabas-site` | Logique panier, checkout, messages, affichage |
| POS (application règles) | `/var/www/kabas` (js/pos/) | Logique vente, messages staff |
| Comptabilité | `/var/www/kabas` | Nouveau compte financier, transactions promo |

### Décisions techniques à prendre lors de l'implémentation

- Structure des tables (1 table générique avec JSON config ? ou tables séparées par type ?)
- Service dédié (`PromotionService`) pour centraliser la logique d'évaluation
- API endpoint pour le site (`/api/promotions/evaluate` ou intégré au cart ?)
- Comment le POS offline gère les promos (sync des règles actives ?)

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

## Module Analytics (ajouté 2026-04-24)

Dashboard analytique complet pour le site public. Données capturées côté `/var/www/kabas-site`, agrégées quotidiennement, affichées dans le BO sous `/analytics/*`.

### Architecture en 4 couches

```
[site] middleware + JS → queue job → events bruts (90j)
                                      ↓ cron 01:00
                           tables _daily (conservées à vie)
                                      ↓
                           [BO] AnalyticsDashboardController → 8 vues Blade
```

### Tables

| Table | Role | Rétention |
|-------|------|-----------|
| `analytics_visitors` | 1 ligne par visiteur unique (cookie UUID) | jamais purgé |
| `analytics_sessions` | 1 ligne par session (fermée après 30min d'inactivité) | 90 jours |
| `analytics_events` | events bruts (page_view, product_view, etc.) | 90 jours |
| `analytics_daily` | rollup global par jour | jamais purgé |
| `analytics_product_daily` | rollup par produit | jamais purgé |
| `analytics_source_daily` | rollup par source+UTM | jamais purgé |
| `analytics_geo_daily` | rollup par pays | jamais purgé |
| `analytics_search_daily` | rollup par terme de recherche | jamais purgé |

### Event types instrumentés (kabas-site)

| Event | Controller | Déclenché quand |
|-------|------------|----------------|
| `page_view` | `AnalyticsMiddleware` (auto) | toute GET HTML 2xx/3xx hors paths exclus |
| `product_view` | `PublicProductController@show` | fiche produit chargée |
| `add_to_cart` | `CartController@add` | item ajouté (incl. gift_box, gift_card) |
| `cart_remove` | `CartController@remove` | item retiré du panier |
| `checkout_start` | `CheckoutController@show` | arrive sur /checkout avec panier non vide |
| `order_placed` | `CheckoutController@handleOrderPaid` | paiement confirmé |
| `search` | `SearchController@index` | recherche avec term non vide |
| `wishlist_add` / `wishlist_remove` | `Customer/WishlistController` | toggle + remove |
| `404` | `bootstrap/app.php` renderable hook | NotFoundHttpException |

### Exclusion staff

2 mécanismes :
1. **Email whitelist** : `ANALYTICS_EXCLUDED_EMAILS` dans `.env` du site (actuellement `adsofts@gmail.com`)
2. **Cookie opt-out** : visite de `https://www.kabasconceptstore.com/_analytics_opt_out` pose un cookie 1 an

Events stockés avec `is_staff=true` mais **exclus** de toutes les agrégations et dashboards.

### Commandes artisan (kabas-site)

```bash
php artisan analytics:aggregate-daily                      # rollup hier
php artisan analytics:aggregate-daily --date=2026-04-20    # un jour précis
php artisan analytics:aggregate-daily --days=30            # backfill
php artisan analytics:close-stale-sessions                 # ferme sessions idle >30min
php artisan analytics:purge-old-events                     # delete events + sessions >90j
php artisan geoip:update-database                          # télécharge GeoLite2
```

Schedule (routes/console.php) :
- `close-stale-sessions` every 5 min
- `aggregate-daily` daily 01:00
- `purge-old-events` weekly lundi 02:00
- `geoip:update-database` monthly 1er à 02:30

### Routes BO

```
/analytics           → overview (vue d'ensemble)
/analytics/products  → funnel produit (tri + filtres)
/analytics/sources   → acquisition (in-house + GA4)
/analytics/search    → top termes + zero-results
/analytics/customers → LTV + cohort + repeat rate
/analytics/geo       → carte Leaflet + top pays
/analytics/checkout  → funnel 5 étapes
/analytics/marketing → codes promo + UTM + récupération
```

Accès : `role:admin|manager`.

### Ga4AnalyticsService (BO)

Wrapper autour de la Google Analytics Data API (package `google/analytics-data` à installer quand actif). **Mode dégradé par défaut** — `isAvailable() = false` tant que :
- `GA4_PROPERTY_ID` n'est pas défini dans `.env`
- Le JSON service account n'existe pas à `storage/app/ga4/credentials.json`
- Le package `google/analytics-data` n'est pas installé

Les dashboards affichent "GA4 non configuré" proprement jusqu'à ce que ces 3 conditions soient remplies.

### Runbook : ajouter un nouveau type d'event

1. Ajouter la constante dans `kabas-site/app/Models/Analytics/AnalyticsEvent.php` :
   ```php
   public const TYPE_MY_NEW_EVENT = 'my_new_event';
   ```
2. Ajouter le type à `ALLOWED_EVENT_TYPES` dans `AnalyticsController::track()` (si poussé depuis le JS).
3. Dans le controller métier qui déclenche l'event :
   ```php
   app(\App\Services\Analytics\AnalyticsService::class)->track(
       eventType: \App\Models\Analytics\AnalyticsEvent::TYPE_MY_NEW_EVENT,
       request: $request,
       payload: ['foo' => 'bar'],
       productId: $pid, // optionnel
   );
   ```
4. Si l'event a un impact sur les agrégations (ex: doit compter dans les funnel steps), mettre à jour `AggregateAnalyticsDaily` et/ou `TrackEventJob::upsertSession()`.
5. Si besoin d'affichage : ajouter une colonne/graph dans les dashboards BO.

Le pattern `dispatchAfterResponse()` dans `TrackEventJob` exécute le job en-process après envoi de la réponse → pas besoin de worker queue actif pour l'ingest (worker reste nécessaire pour les crons d'agrégation si jamais on en fait des jobs).

### Runbook : divergences GA4 vs in-house

**Normales** — ne pas paniquer. Causes typiques :

| Cause | Impact |
|-------|--------|
| GA4 filtre les bots automatiquement, in-house les marque comme `device_type=bot` mais les compte | in-house > GA4 sur sessions |
| GA4 attribue les conversions au "last non-direct click" (fenêtre 30j), in-house à la session en cours | sources différentes pour même commande |
| GA4 utilise client-side JS uniquement, in-house = middleware serveur | in-house > GA4 (JS bloqué par adblocker, pages AMP, etc.) |
| Staff exclu côté in-house (cookie + email), staff non exclu côté GA4 | in-house < GA4 sur trafic staff |
| GA4 sampling sur gros volumes | GA4 approximatif à partir de ~200k sessions/mois |

**Quand s'inquiéter** : divergence > 50% sur une métrique clé (sessions, orders, revenue). Vérifier d'abord qu'aucun adblocker n'est actif côté test, puis investiguer via les events bruts.

### Fichiers clés

**Côté site** (`/var/www/kabas-site`) :
- `app/Http/Middleware/AnalyticsMiddleware.php`
- `app/Services/Analytics/{AnalyticsService,GeolocationService,UserAgentParser}.php`
- `app/Jobs/Analytics/TrackEventJob.php`
- `app/Http/Controllers/AnalyticsController.php`
- `app/Console/Commands/{AggregateAnalyticsDaily,PurgeOldAnalyticsEvents,CloseStaleAnalyticsSessions,UpdateGeoIpDatabase}.php`
- `app/Models/Analytics/*` (8 modèles)
- `public/js/analytics.js`
- `config/analytics.php`
- `storage/app/geoip/GeoLite2-City.mmdb` (DB GeoIP, ~62 MB)

**Côté BO** (`/var/www/kabas`) :
- `app/Http/Controllers/AnalyticsDashboardController.php`
- `app/Services/Ga4AnalyticsService.php`
- `config/analytics.php`
- `resources/views/analytics/*` (8 vues + partial period-picker)

### Actions encore pending

1. **Worker supervisor kabas-site** : préparé dans `/tmp/kabas-site-worker.conf`, à copier dans `/etc/supervisor/conf.d/` par admin. **Non bloquant pour le tracking** (qui utilise `dispatchAfterResponse`), mais nécessaire pour d'éventuels futurs jobs queue-based.
2. **GA4 service account** : attendre que l'admin crée le service account dans Google Cloud Console, pose le JSON dans `storage/app/ga4/credentials.json`, renseigne `GA4_PROPERTY_ID` dans `.env`, puis `composer require google/analytics-data` dans le BO.

---

## Contacts & Ressources

- **Meilisearch**: http://127.0.0.1:7700
- **Logs**: `/var/www/kabas/storage/logs/laravel.log`
- **PHPUnit**: `./vendor/bin/phpunit`

---

*Ce fichier est destiné à être lu par Claude Code pour reprendre le contexte du projet.*
