# KABAS - ANALYSE DÉTAILLÉE DES FICHIERS CLÉS

## Structure Rapide de Navigation

### 1. POINTS D'ENTRÉE

#### `/routes/web.php` (400+ lignes)
**Importance:** CRITIQUE
**État:** À corriger
**Problèmes identifiés:**
- Ligne 60-90: Route `/track-url` accède directement `$_SESSION` ⚠️
- Pas de validation CSRF explicite sur certaines routes
- Routes POS/Financial sans rate limiting

**À faire:**
```php
// AVANT (Ligne 65, 80, 84)
$_SESSION['url_history'] = $history;

// APRÈS
session(['url_history' => $history]);
```

#### `/app/Http/Controllers/DashboardController.php` (100+ lignes)
**Importance:** CRITIQUE (performance)
**État:** Lent en production
**Problèmes:**
- Lignes 50-55: Boucle N+1 pour vérifier stock = 0
- Lignes 32-34: WhereRaw avec JSON_EXTRACT (MySQL-spécifique)

**Impact:** Dashboard se charge en 5-10 secondes avec gros catalogue

**À faire:** Utiliser scopes Eloquent au lieu de whereRaw

---

### 2. MODÈLES CRITIQUES

#### `/app/Models/Product.php` (255 lignes)
**Importance:** TRÈS HAUTE
**Relations:** 8 relations principales
**Observations:**
- ✓ Bon usage de polymorphe (categories)
- ✓ Support JSON (name, description, slugs)
- ✓ Searchable (Scout/Meilisearch)
- ✓ Traitable FIFO stock (getTotalStock)
- ⚠️ allow_overselling en $fillable mais migration non appliquée

**Concernant allow_overselling:**
```php
protected $fillable = [
    // ... other fields
    'allow_overselling',  // Existe mais table pas migrée
];
```

#### `/app/Models/Sale.php` + `/app/Models/SaleItem.php` (40 lignes total)
**Importance:** TRÈS HAUTE (POS core)
**État:** À corriger
**Problèmes:**

**SaleItem.php problème:**
```php
protected $fillable = [
    'sale_id', 'product_id', 'quantity', 'price', 'discounts', 
    'is_delivery', 'delivery_address'
];
// product_id peut être NULL pour items de livraison
// Mais pas de logique pour différencier
```

**À faire:** 
- Valider que si is_delivery=true, product_id peut être null
- Sinon, product_id est obligatoire
- Documenter le métier (vente produit vs service livraison)

#### `/app/Models/FinancialTransaction.php`
**Importance:** HAUTE (comptabilité)
**État:** Race condition possible
**Problème:** Balance calculée dynamiquement au lieu de stockée

```php
// Actuel (FinancialTransactionController ligne 99)
$last = FinancialTransaction::where('store_id', $store->id)
    ->latest('transaction_date')->first();
$balanceBefore = $last?->balance_after ?? 0;

// Si deux requêtes concurrentes, race condition!
```

**À faire:** 
Option 1: Denormaliser balance_before dans la table
Option 2: Utiliser pessimistic locking

```php
$last = FinancialTransaction::where('store_id', $store->id)
    ->lockForUpdate()
    ->latest('transaction_date')->first();
```

---

### 3. CONTRÔLEURS PROBLÉMATIQUES

#### `/app/Http/Controllers/POS/SyncController.php` (421 lignes)
**Importance:** TRÈS HAUTE (POS core)
**État:** BON, mais à sécuriser
**Validation:** Extensive (lignes 34-51)
**Transactions:** Utilise DB::transaction() correctement

**Points forts:**
- ✓ Validation complète
- ✓ FIFO stock decrement
- ✓ Transactional safety

**À corriger:**
- Ligne 42: `'sales.*.split_payments.*.amount' => 'required|numeric|min:0'`
  **Pas de vérification que sum = total!**

```php
// À ajouter après création de Sale:
if (!empty($saleData['split_payments'])) {
    $sum = array_sum(array_column($saleData['split_payments'], 'amount'));
    if (abs($sum - $saleData['total']) > 0.01) {  // tolerance pour arrondi
        throw ValidationException::withMessages([
            'split_payments' => 'Split payments must sum to total amount'
        ]);
    }
}
```

**À ajouter:** Rate limiting sur `/pos/sync` endpoint

#### `/app/Http/Controllers/ProductController.php` (597 lignes)
**Importance:** TRÈS HAUTE
**État:** Fonctionnel mais complexe
**Particularités:**
- 15+ actions (index, show, create, store, edit, update, destroy, variations, etc.)
- Ligne 28: Search utilise Meilisearch mais configured sur "collection"
- Utilise DB::transaction() correctement (lignes 519+)

**À refactorer:**
```php
// Ligne 24-56: Mélange recherche SQL et Meilisearch
if ($request->filled('q')) {
    $searchQuery = Product::search($request->q)->query(...);
} else {
    $query = Product::with(...);
}
// Problème: Collection driver ne supporte pas .where() dans callback
```

**Recommandation:** Créer ProductSearchService

#### `/app/Http/Controllers/Financial/FinancialTransactionController.php` (267 lignes)
**Importance:** HAUTE
**État:** À sécuriser
**Problèmes:**
- Ligne 99: Race condition sur balance calculation
- Pas de authorization check par store (relying on route parameter?)
- Export Excel répète la logique de filtrage (code duplication)

---

### 4. HELPERS & UTILITIES

#### `/app/Helpers/RedirectHelper.php`
**Problème CRITIQUE:**
```php
function redirectBackLevels(int $levels = 1, string $fallback = null) {
    $history = $_SESSION['url_history'] ?? [];  // Direct $_SESSION!
    
    if (count($history) >= $levels) {
        $target = $history[count($history) - $levels];
    } else {
        $target = $fallback ?? url('/');
    }
    
    return redirect($target);
}
```

**À corriger:**
```php
function redirectBackLevels(int $levels = 1, string $fallback = null) {
    $history = session('url_history', []);  // Utiliser session()
    
    if (count($history) >= $levels) {
        $target = $history[count($history) - $levels];
    } else {
        $target = $fallback ?? url('/');
    }
    
    return redirect($target);
}
```

---

### 5. FICHIERS DE CONFIGURATION

#### `/config/scout.php`
**État:** Configuré mais inactif
```
SCOUT_DRIVER=collection (par défaut)
```

**Pour passer à Meilisearch:**
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your_key
```

Puis:
```bash
php artisan scout:import "App\Models\Product"
```

---

### 6. MIGRATIONS PROBLÉMATIQUES

#### Migration 2025_11_10_085350 vs 2025_11_10_090503
**CONFLIT:**
```
2025_11_10_085350: Ajoute has_delivery, delivery_fee, delivery_address à sales
2025_11_10_090503: Supprime les MÊMES champs de sales
```

**Solution requise:**
1. Vérifier quel fichier est correct
2. Supprimer l'autre OU les fusionner
3. Ajouter migration intermédiaire si nécessaire

**Diagnostic:**
```bash
php artisan migrate --step  # Pour voir l'ordre
php artisan schema:dump    # Pour voir schéma actuel
```

#### Migration 2025_11_11_013343
```php
Schema::table('products', function (Blueprint $table) {
    $table->boolean('allow_overselling')->default(false)->after('is_active');
});
```

**État:** OK, cohérent avec Product.php $fillable

---

### 7. FICHIERS ABSENTS/PROBLÉMATIQUES

#### `/resources/views/scanner.blade.php` - MANQUANT
**Route:** `GET /scanner` → `view('scanner')`
**État:** Fichier n'existe pas mais route active

**À corriger:**
```bash
# Option 1: Créer la vue
touch resources/views/scanner.blade.php

# Option 2: Supprimer la route
# routes/web.php ligne 98-100
```

#### Pas de `app/Exceptions/Handler.php` personnalisé
**Observation:** Pas de gestion d'erreur custom

**À ajouter pour production:**
- Log unhandled exceptions
- Render JSON pour API endpoints
- Sentry integration

#### Pas de tests (`tests/` est vide)
**Critique pour production!**
```bash
# Ajouter au minimum:
tests/Feature/ProductTest.php
tests/Feature/POS/SyncTest.php
tests/Feature/Financial/TransactionTest.php
tests/Unit/Models/ProductTest.php
```

---

### 8. FICHIERS BIEN STRUCTURÉS

#### `/app/Models/` Structure
**72 modèles organisés:**
- ✓ Pas de model god-objects
- ✓ Relations bien définies
- ✓ Proper use of casts
- ✓ Good use of JSON columns

#### `/routes/web.php` Grouping
**✓ Bon usage de:**
- `middleware(['auth', 'SetUserLocale'])`
- `middleware(['role:admin'])`
- `Route::prefix()` et `Route::name()`

---

## FICHIERS À CRÉER (Priorité P0)

### 1. App/Http/Requests/Store/StoreProductRequest.php
```php
class StoreProductRequest extends FormRequest {
    public function rules() {
        return [
            'name' => 'required|array',
            'name.fr' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description' => 'nullable|array',
            'description.fr' => 'nullable|string',
            'description.en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_btob' => 'nullable|numeric|min:0',
            'brand_id' => 'nullable|exists:brands,id',
            'ean' => 'nullable|string|unique:products',
            'is_active' => 'boolean',
            'allow_overselling' => 'boolean',
        ];
    }
}
```

### 2. App/Http/Requests/Sale/StoreSaleRequest.php
```php
class StoreSaleRequest extends FormRequest {
    public function rules() {
        return [
            'shift_id' => 'required|exists:shifts,id',
            'sales' => 'required|array|min:1',
            'sales.*.payment_type' => 'required|string',
            'sales.*.total' => 'required|numeric|min:0.01',
            'sales.*.split_payments' => 'nullable|array',
            'sales.*.split_payments.*.payment_type' => 'required|string',
            'sales.*.split_payments.*.amount' => 'required|numeric|min:0',
            'sales.*.items' => 'required|array|min:1',
            'sales.*.items.*.product_id' => 'nullable|exists:products,id',
            'sales.*.items.*.quantity' => 'required|integer|min:1',
            'sales.*.items.*.price' => 'required|numeric|min:0',
            'sales.*.items.*.is_delivery' => 'nullable|boolean',
        ];
    }
    
    public function withValidator($validator) {
        $validator->after(function ($validator) {
            foreach ($this->input('sales', []) as $index => $sale) {
                if (!empty($sale['split_payments'])) {
                    $sum = array_sum(array_column($sale['split_payments'], 'amount'));
                    if (abs($sum - $sale['total']) > 0.01) {
                        $validator->errors()->add(
                            "sales.$index.split_payments",
                            'Split payments must sum to the total amount'
                        );
                    }
                }
            }
        });
    }
}
```

### 3. App/Models/Scopes/ProductScopes.php ou dans Product.php
```php
public function scopeMissingDescriptionFr($query) {
    return $query->where(function($q) {
        $q->whereNull('description')
          ->orWhereJsonPath('description->fr', '=', null)
          ->orWhereJsonPath('description->fr', '=', '');
    });
}

public function scopeWithoutImages($query) {
    return $query->whereDoesntHave('images');
}

public function scopeOutOfStock($query) {
    return $query->whereDoesntHave('stockBatches')
        ->orWhereHas('stockBatches', function($q) {
            $q->havingRaw('SUM(quantity) = 0');
        });
}
```

---

## FICHIERS À SUPPRIMER

1. `/generate_telegram_session.php` (unused)
2. MadelineProto from composer.json (si confirmé unused)
3. Supprimer la route `/scanner` si la vue n'existe pas

---

## FICHIERS DE DOCUMENTATION CRÉÉS

1. **ANALYSIS_REPORT.md** - Analyse complète (919 lignes)
2. **EXECUTIVE_SUMMARY.txt** - Résumé exécutif
3. **KEY_FILES_ANALYSIS.md** - Ce document

---

**Document généré:** 11 novembre 2025
**Pour questions:** Consulter ANALYSIS_REPORT.md ou EXECUTIVE_SUMMARY.txt
