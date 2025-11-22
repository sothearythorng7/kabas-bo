# DOCUMENTATION ARCHITECTURE - PROJET KABAS

Bienvenue dans la documentation complète du projet KABAS !

## Point de départ

Vous commencez juste ? Lisez dans cet ordre :

1. **ARCHITECTURE_QUICKSTART.md** (5-10 min) - Les points clés
2. **ARCHITECTURE_SUMMARY.md** (20 min) - Vue d'ensemble complète
3. Puis selon votre besoin → voir navigation rapide ci-dessous

---

## Les 4 fichiers de documentation

| Fichier | Taille | Contenu | Pour qui ? |
|---------|--------|---------|-----------|
| **QUICKSTART** | 3 pages | Essentiel + dépannage | Tout le monde |
| **SUMMARY** | 7 pages | Vue d'ensemble structurée | Devs, architects |
| **ANALYSIS** | 42 KB | Analyse complète détail | Senior devs, architects |
| **DIAGRAMS** | 39 KB | ASCII diagrams + flux | Visualeux, presenters |
| **INDEX** | 8 KB | Guide de navigation | Pour trouver info |

---

## Navigation rapide par besoin

### Je veux comprendre le projet en 30 minutes
→ Lisez :
1. ARCHITECTURE_QUICKSTART.md (5 min)
2. ARCHITECTURE_SUMMARY.md (25 min)

### Je dois implémenter une nouvelle feature
→ Lisez :
1. QUICKSTART.md (comprendre la stack)
2. Routes concernées dans SUMMARY.md
3. Détail du contrôleur dans ANALYSIS.md
4. Modèle dans ANALYSIS.md

### Je dois debugger une vente POS qui ne se sync pas
→ Lisez :
1. "Flux de travail POS" dans QUICKSTART.md
2. "SyncController" dans ANALYSIS.md
3. "Vente en POS" dans DIAGRAMS.md (diagramme complet)
4. Logs : `tail -f storage/logs/laravel.log`

### Je dois ajouter une nouvelle route API
→ Lisez :
1. Routes API existantes dans SUMMARY.md ou ANALYSIS.md
2. Le contrôleur cible dans ANALYSIS.md
3. Le modèle concerné

### Je dois optimiser les performances
→ Lisez :
1. "Modèle de données" dans DIAGRAMS.md
2. Relations des modèles dans ANALYSIS.md
3. Puis identifier les N+1 queries

### Je dois présenter l'architecture aux stakeholders
→ Utilisez :
1. QUICKSTART.md pour l'intro
2. DIAGRAMS.md sections 1, 2, 7, 8 (visuels)
3. SUMMARY.md "Cas d'usage typiques"

---

## Structure du projet en 1 minute

```
BACKOFFICE (/dashboard)
├─ 50+ contrôleurs
├─ 45+ vues Blade
├─ Gestion produits, stocks, fournisseurs, finances
└─ Interface: Blade + Bootstrap 5

POS (/pos)
├─ Vue 3 SPA
├─ Offline-capable (IndexedDB)
├─ Caissier vend en temps réel
└─ Sync quand connexion

FRONT API (/api/*)
├─ Endpoints JSON
├─ Pour app mobile/web externe
└─ Authentification Sanctum

Tous partagent: Laravel 11 + MySQL
```

---

## Technologies principales

- **Backend**: Laravel 11
- **Database**: MySQL
- **BO Frontend**: Blade templates + Bootstrap 5
- **POS Frontend**: Vue 3 + Pinia + IndexedDB
- **Build**: Vite 7
- **Search**: Meilisearch (Scout)
- **Roles**: Spatie Permission
- **API**: Sanctum

---

## Les 3 entrées principales

```
https://monsite.com/
├─ /dashboard          → Admin/Backoffice
├─ /pos                → Caissier/POS
└─ /api/*              → Frontend externe
```

---

## Cas d'usage critiques à comprendre

1. **Création produit** (Admin → Backoffice)
   - Produit créé, indexed par Meilisearch
   - Attaché à tous les magasins (stock=0)
   - Synchronisé vers POS via /api/pos/catalog

2. **Vente en caisse** (Caissier → POS)
   - Produit ajouté au panier (offline)
   - Paiement choisi (cash/card/split)
   - POST /api/pos/sales/sync
   - Stock décrémenté (FIFO), transaction créée

3. **Comptabilité** (Admin → Backoffice)
   - FinancialTransaction créée à chaque vente
   - Solde cumulatif (balance_after)
   - Visible dans /financial/{store}/transactions

4. **Gestion fournisseur** (Admin → Backoffice)
   - Commande créée, validée
   - Réception → Stock incrémenté
   - Facture → FinancialTransaction

---

## Base de données - 80+ tables

**Groupées par domaine :**
- **Produits** : products, categories, brands, images, variations
- **Stocks** : stock_batches, stock_transactions, stock_movements
- **Ventes** : sales, sale_items, shifts, cash_transactions
- **Finances** : financial_accounts, financial_transactions, journals
- **Fournisseurs** : suppliers, supplier_orders, purchase_price_history
- **Revendeurs** : resellers, deliveries, sales_reports, invoices
- **Utilisateurs** : users, roles, permissions, model_has_roles
- **Contenus** : pages, blog_posts, gift_boxes, hero_slides
- **Autres** : contacts, warehouses, invoices, etc.

---

## Middlewares clés

```
Backoffice:
├─ auth                   : Utilisateur connecté
├─ role:admin            : Role admin requis
└─ SetUserLocale         : Langue de l'utilisateur

POS:
└─ (aucun - public)      : PIN auth en client-side

Front API:
└─ auth:sanctum          : Token Sanctum requis
```

---

## Commandes essentielles

```bash
# Voir routes
php artisan route:list | grep /dashboard

# Console interactive
php artisan tinker
>>> Product::count()

# Migration
php artisan migrate

# Reindex search
php artisan scout:import "App\\Models\\Product"

# Logs
tail -f storage/logs/laravel.log

# Test
php artisan test
```

---

## Fichiers clés du projet

| Fichier | Rôle |
|---------|------|
| `/routes/web.php` | Toutes les routes (403 lignes) |
| `/routes/api.php` | Routes API frontend |
| `/app/Http/Controllers/` | 60+ contrôleurs |
| `/app/Models/` | 80+ modèles |
| `/resources/views/` | 45+ vues Blade |
| `/resources/js/pos/` | Vue 3 SPA |
| `/public/js/pos/` | JavaScript offline |
| `/config/` | Configuration |
| `/database/migrations/` | Schéma DB |

---

## Architecture simplifiée

```
Utilisateur
    ↓
Navigateur (BO, POS, ou App externe)
    ↓
Laravel 11 (Routes, Controllers)
    ↓
MySQL (Models, Data)
    ↓
Utilisateur voit le résultat
```

---

## Questions fréquentes

**Q: Où ajouter une nouvelle page d'admin?**
A: Route dans `/routes/web.php` (avec `role:admin` middleware), puis contrôleur + vue Blade

**Q: Comment faire fonctionner POS hors ligne?**
A: LocalStorage + IndexedDB store automatiquement données, sync quand connexion revient

**Q: Où enregistrer une vente?**
A: POST `/api/pos/sales/sync` → `SyncController::sales()` décrémente stock + crée FinancialTransaction

**Q: Comment voir les transactions comptables?**
A: `/financial/{store}/transactions` → `FinancialTransactionController::index()`

**Q: Comment ajouter une permission?**
A: Via Spatie Permission (voir `config/permission.php`)

---

## Support et dépannage

**Logs**: `storage/logs/laravel.log`

**Console**: `php artisan tinker`

**Recherche rapide**: Ctrl+F dans le fichier approprié (SUMMARY, ANALYSIS, ou DIAGRAMS)

---

## Mises à jour et historique

| Date | Change | Fichier |
|------|--------|---------|
| 2025-02-14 | Analyse complète | ARCHITECTURE_*.md |
| 2025-02-14 | Tous fichiers créés | v1.0 |

---

**Pour plus de détails, voir les autres fichiers ARCHITECTURE_*.md**

BONNE LECTURE !

