# Plan d'implémentation : Création de magasin (Store Provisioning Wizard)

> **Statut** : PLAN — ne rien implémenter avant validation
> **Date** : 2026-02-24
> **Objectif** : Permettre à un admin de créer un nouveau magasin depuis Settings > Stores, avec provisioning automatique de tout ce qui est nécessaire pour qu'il soit opérationnel (finances, stock, POS, dashboard, réception, etc.)

---

## Problème actuel

Le `StoreController::store()` actuel crée uniquement la ligne dans la table `stores`. Rien d'autre n'est provisionné. De plus, le **Dashboard principal** a du code en dur pour les 2 magasins existants (store_id 1 et 2), et la **Reception PWA** a des constantes figées. Un nouveau magasin serait invisible dans le dashboard et partiellement non-fonctionnel.

### Code en dur identifié (à corriger)

| Fichier | Lignes | Problème | Sévérité |
|---------|--------|----------|----------|
| `DashboardController.php` | 101-168 | C.A. et graphique mensuels codés en dur pour store_id=1 et store_id=2 | CRITIQUE |
| `dashboard.blade.php` | 235-301, 384-394 | Cartes KPI "Siem Reap"/"Phnom Penh" en dur + datasets graphique + liens store_id=1/2 | CRITIQUE |
| `pos/screens/dashboard.blade.php` | 143-165 | `PRINTER_CONFIG.stores` hardcodé avec seulement stores 1 et 2 (nom, adresse, tel pour tickets) + fallback sur store 1 | CRITIQUE |
| `ReceptionController.php` | 31-33 | Constantes `STORE_PHNOM_PENH=1`, `STORE_SIEM_REAP=2`, `STORE_WAREHOUSE=3` | CRITIQUE |
| `ReceptionController.php` | 683-684 | Logique d'accès inter-magasins codée en dur (PP voit Warehouse) | CRITIQUE |
| `SupplierOrderController.php` | 126 | `destination_store_id => 3` en dur pour matières premières | HAUTE |
| `SpecialOrderController.php` | 74, 122 | `store_id => 1` en dur (Phnom Penh) | HAUTE |
| `SpecialOrderService.php` | 79, 139 | `$order->store_id ?? 3` fallback warehouse hardcodé | HAUTE |
| `WebsiteOrderController.php` | 17-18, 343 | Constantes `FINANCIAL_ACCOUNT_ID=17`, `SYSTEM_USER_ID=1`, fallback `store_id ?? 3` | HAUTE |
| `resources/lang/{en,fr}/messages.php` | 1382-1383, 2029-2030 | Clés de traduction `ca_siem_reap`, `ca_phnom_penh` hardcodées | MOYENNE |
| `resellers/reports/invoice.blade.php` | 154 | Adresse "65 Street 178, Phnom Penh" en dur dans le PDF | FAIBLE (cosmétique) |
| `resellers/deliveries/delivery-note-pdf.blade.php` | 132 | "Phnom Penh, Cambodia" en dur dans le PDF | FAIBLE (cosmétique) |

---

## Architecture de la solution

### Approche : Service `StoreProvisioningService`

Un service centralisé qui orchestre toute la création. Le wizard BO collecte les informations, puis appelle le service qui effectue tout dans une transaction DB.

```
StoreController (wizard multi-étapes)
  └─> StoreProvisioningService::provision($data)
        ├── Crée le Store
        ├── Crée la transaction financière d'ouverture (solde initial)
        ├── Crée/affecte les StaffMembers + Users
        └── Retourne le Store créé
```

---

## Étapes d'implémentation

### Étape 1 : Service de provisioning

**Créer** `app/Services/StoreProvisioningService.php`

```php
class StoreProvisioningService
{
    /**
     * Crée un magasin et provisionne tout ce qui est nécessaire.
     *
     * @param array $storeData   Données du magasin (name, address, phone, email, type, opening_time, closing_time, is_reseller)
     * @param array $staffData   Liste de staff [{name, email, phone, hire_date, create_user_account, pin_code, role}]
     * @param float $openingBalance  Solde d'ouverture (optionnel, défaut 0)
     * @return Store
     */
    public function provision(array $storeData, array $staffData = [], float $openingBalance = 0): Store
    {
        return DB::transaction(function() use ($storeData, $staffData, $openingBalance) {
            // 1. Créer le Store
            $store = Store::create($storeData);

            // 2. Transaction financière d'ouverture (si solde > 0)
            if ($openingBalance > 0) {
                $this->createOpeningBalance($store, $openingBalance);
            }

            // 3. Créer/affecter le staff
            foreach ($staffData as $staff) {
                $this->provisionStaffMember($store, $staff);
            }

            return $store;
        });
    }
}
```

**Méthodes privées du service :**

| Méthode | Rôle |
|---------|------|
| `createOpeningBalance($store, $amount)` | Crée une `FinancialTransaction` credit sur le compte 530 (Caisse) avec label "Opening balance" |
| `provisionStaffMember($store, $data)` | Crée un `StaffMember` + optionnellement un `User` (avec `store_id`, `pin_code`, rôle Spatie) |

> **Note** : Les comptes financiers (`FinancialAccount`) et méthodes de paiement (`FinancialPaymentMethod`) sont **globaux** — rien à créer par magasin. Le stock (`StockBatch`) se crée dynamiquement lors des réceptions/transferts — rien à initialiser.

---

### Étape 2 : Wizard multi-étapes dans le StoreController

**Modifier** `app/Http/Controllers/StoreController.php`

Remplacer le `create()`/`store()` basique par un wizard à **3 étapes** (tout dans une seule page avec des écrans JS, pas de redirections serveur entre étapes) :

**Écran 1 — Informations du magasin**
- Nom (requis)
- Adresse (requis)
- Téléphone (requis)
- Email (requis, unique)
- Type : `shop` ou `warehouse` (select)
- Horaires d'ouverture/fermeture (optionnel)
- Est revendeur ? (checkbox)

**Écran 2 — Staff initial**
- Tableau dynamique pour ajouter des membres du staff
- Par personne : nom, email, téléphone, date d'embauche
- Checkbox "Créer un compte utilisateur" → si coché : PIN à 6 chiffres + rôle (select parmi les rôles Spatie)
- Bouton "+ Ajouter un membre"
- Optionnel : on peut passer cette étape (0 staff)

**Écran 3 — Finance & Confirmation**
- Solde d'ouverture de caisse (défaut: 0$)
- Récapitulatif de tout ce qui va être créé
- Bouton "Créer le magasin"

**Nouvelles routes :**

```php
// Le wizard utilise les routes resource existantes :
// GET  /stores/create  → Formulaire wizard (3 écrans JS)
// POST /stores         → store() reçoit tout en un seul POST
```

**Nouvelles méthodes controller :**

| Méthode | Rôle |
|---------|------|
| `create()` | Affiche le wizard (charge les rôles Spatie pour le select) |
| `store()` | Valide tout, appelle `StoreProvisioningService::provision()`, redirige vers `stores.show` |

**Validation dans `store()` :**

```php
$request->validate([
    // Store
    'name' => 'required|string|max:255',
    'address' => 'required|string',
    'phone' => 'required|string|max:50',
    'email' => 'required|email|max:255|unique:stores,email',
    'type' => 'required|in:shop,warehouse',
    'opening_time' => 'nullable|date_format:H:i',
    'closing_time' => 'nullable|date_format:H:i',
    'is_reseller' => 'sometimes|boolean',
    // Staff
    'staff' => 'nullable|array',
    'staff.*.name' => 'required|string|max:255',
    'staff.*.email' => 'required|email|max:255',
    'staff.*.phone' => 'required|string|max:50',
    'staff.*.hire_date' => 'required|date',
    'staff.*.create_user' => 'sometimes|boolean',
    'staff.*.pin_code' => 'required_if:staff.*.create_user,true|nullable|string|size:6',
    'staff.*.role' => 'required_if:staff.*.create_user,true|nullable|string|exists:roles,name',
    // Finance
    'opening_balance' => 'nullable|numeric|min:0',
]);
```

---

### Étape 3 : Vue wizard

**Modifier** `resources/views/stores/create.blade.php`

Formulaire multi-étapes en JS (3 écrans, style cohérent avec le reste du BO). Un seul `<form>` qui POST tout à la fin. Navigation avant/arrière entre écrans sans requête serveur.

Structure :
```html
<div class="wizard-step active" id="step1"> ... Infos magasin ... </div>
<div class="wizard-step" id="step2"> ... Staff (lignes dynamiques) ... </div>
<div class="wizard-step" id="step3"> ... Finance + Récapitulatif ... </div>
```

Le step 2 utilise du JS pour ajouter/supprimer des lignes staff dynamiquement (template clonable).

---

### Étape 4 : Dynamiser le Dashboard principal

**Modifier** `app/Http/Controllers/DashboardController.php`

Remplacer le code en dur (store_id=1, store_id=2) par une boucle dynamique sur tous les shops :

```php
// Remplacer les ~70 lignes hardcodées par :
$shops = Store::where('type', 'shop')->orderBy('name')->get();

$storeStats = [];
foreach ($shops as $shop) {
    $dailySales = Sale::where('store_id', $shop->id)
        ->whereBetween('created_at', [$startOfDay, $endOfDay])->get();
    $monthlySales = Sale::where('store_id', $shop->id)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();

    $storeStats[] = [
        'store' => $shop,
        'daily_revenue' => Sale::sumRealRevenue($dailySales),
        'daily_count' => $dailySales->count(),
        'monthly_revenue' => Sale::sumRealRevenue($monthlySales),
        'monthly_count' => $monthlySales->count(),
    ];
}

// Graphique mensuel : une série par shop
$monthlyRevenueByStore = [];
foreach ($shops as $shop) {
    $series = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = Carbon::now()->subMonths($i);
        $series[] = Sale::sumRealRevenue(
            Sale::where('store_id', $shop->id)
                ->whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
                ->get()
        );
    }
    $monthlyRevenueByStore[] = ['store' => $shop, 'data' => $series];
}
```

**Modifier** `resources/views/dashboard.blade.php`

Remplacer les cartes KPI hardcodées par une boucle `@foreach($storeStats as $stat)` qui génère une carte par magasin. Remplacer les datasets du graphique par une boucle sur `$monthlyRevenueByStore`.

Palette de couleurs dynamique pour le graphique :
```javascript
const colors = ['#4e73df', '#f6c23e', '#1cc88a', '#e74a3b', '#36b9cc', '#858796'];
```

---

### Étape 5 : Corriger tous les store_id hardcodés

#### 5a. ReceptionController.php

**Modifier** `app/Http/Controllers/Reception/ReceptionController.php`

1. **Supprimer** les constantes `STORE_PHNOM_PENH`, `STORE_SIEM_REAP`, `STORE_WAREHOUSE`.

2. **Réécrire** `getAccessibleStoreIds()` :
```php
private function getAccessibleStoreIds(): array
{
    $userStoreId = session('reception_store_id');

    // Le staff d'un shop voit aussi le Warehouse pour les commandes fournisseur
    $warehouseIds = Store::where('type', 'warehouse')->pluck('id')->toArray();

    return array_unique(array_merge([$userStoreId], $warehouseIds));
}
```

> **Logique** : tout staff de n'importe quel shop voit aussi les warehouses. C'est le comportement actuel généralisé (PP voyait Warehouse), appliqué à tous les magasins.

#### 5b. SupplierOrderController.php (ligne 126)

Remplacer `destination_store_id => 3` par :
```php
'destination_store_id' => Store::where('type', 'warehouse')->first()?->id,
```

#### 5c. SpecialOrderController.php (lignes 74, 122)

Remplacer `store_id => 1` par le `store_id` du user authentifié :
```php
'store_id' => auth()->user()->store_id ?? Store::where('type', 'shop')->first()?->id,
```

#### 5d. SpecialOrderService.php (lignes 79, 139)

Remplacer `$order->store_id ?? 3` par :
```php
$storeId = $order->store_id ?? Store::where('type', 'warehouse')->first()?->id;
```

#### 5e. WebsiteOrderController.php (lignes 17-18, 343)

Remplacer les constantes hardcodées :
```php
// Avant :
const FINANCIAL_ACCOUNT_ID = 17;
const SYSTEM_USER_ID = 1;

// Après :
// Résoudre dynamiquement par code comptable
$account = FinancialAccount::where('code', '701')->first();
// Pour SYSTEM_USER_ID : utiliser auth()->id() avec fallback User::first()->id
```

Et remplacer `$order->store_id ?? 3` par :
```php
$storeId = $order->store_id ?? Store::where('type', 'warehouse')->first()?->id;
```

#### 5f. POS Printer Config — `pos/screens/dashboard.blade.php` (lignes 143-165)

Le `PRINTER_CONFIG.stores` est hardcodé avec les infos de 2 magasins. Remplacer par des données dynamiques injectées depuis le serveur :

```javascript
// Avant (hardcodé) :
const PRINTER_CONFIG = {
    stores: {
        1: { prefix: "PP", name: "Phnom Penh", address: "...", phone: "..." },
        2: { prefix: "SR", name: "Siem Reap", address: "...", phone: "..." }
    }
};

// Après (dynamique) :
const PRINTER_CONFIG = {
    url: "http://localhost:8888",
    stores: @json($printerStores),
    footer: "Thank you for your visit!"
};
```

Côté controller POS, injecter `$printerStores` construit depuis `Store::where('type', 'shop')` avec les champs `name`, `address`, `phone` et un `prefix` dérivé des initiales du nom.

Le fallback `PRINTER_CONFIG.stores[1]` doit devenir `Object.values(PRINTER_CONFIG.stores)[0]` (premier magasin trouvé).

> **Note** : Il faudra ajouter un champ `prefix` (ou `receipt_prefix`) à la table `stores` via une migration, pour que chaque magasin ait son préfixe de ticket configurable dans le wizard.

#### 5g. PDFs revendeurs (cosmétique, priorité basse)

- `resellers/reports/invoice.blade.php` ligne 154 : remplacer l'adresse hardcodée par `{{ $store->address ?? config('app.company_address') }}`
- `resellers/deliveries/delivery-note-pdf.blade.php` ligne 132 : idem

---

### Étape 6 : Migration pour le champ `receipt_prefix`

**Créer** une migration `add_receipt_prefix_to_stores_table.php` :

```php
Schema::table('stores', function (Blueprint $table) {
    $table->string('receipt_prefix', 10)->nullable()->after('type');
});
```

Mettre à jour les stores existants :
```php
// Dans la migration (up) :
DB::table('stores')->where('id', 1)->update(['receipt_prefix' => 'PP']);
DB::table('stores')->where('id', 2)->update(['receipt_prefix' => 'SR']);
DB::table('stores')->where('id', 3)->update(['receipt_prefix' => 'WH']);
```

Ajouter le champ au wizard (Étape 1, optionnel). Si vide, généré automatiquement depuis les initiales du nom.

---

### Étape 7 : Traductions

**Modifier** `resources/lang/en/messages.php` et `resources/lang/fr/messages.php`

Ajouter dans la section `stores` (ou créer si elle n'existe pas) :

```php
// EN
'store_wizard' => [
    'title' => 'Create New Store',
    'step1_title' => 'Store Information',
    'step2_title' => 'Initial Staff',
    'step3_title' => 'Finance & Confirmation',
    'type_shop' => 'Shop (retail)',
    'type_warehouse' => 'Warehouse',
    'add_staff' => 'Add Staff Member',
    'create_user_account' => 'Create user account (BO/POS access)',
    'pin_code' => 'PIN Code (6 digits)',
    'role' => 'Role',
    'opening_balance' => 'Opening Cash Balance',
    'summary' => 'Summary',
    'summary_store' => 'Store: :name (:type)',
    'summary_staff' => ':count staff member(s) will be created',
    'summary_users' => ':count user account(s) with POS/BO access',
    'summary_balance' => 'Opening balance: $:amount',
    'confirm_create' => 'Create Store',
    'created_success' => 'Store ":name" created successfully with all dependencies.',
    'no_staff' => 'No staff — you can add them later from Staff Management.',
    'step' => 'Step :current of :total',
    'next' => 'Next',
    'previous' => 'Previous',
],

// FR
'store_wizard' => [
    'title' => 'Créer un nouveau magasin',
    'step1_title' => 'Informations du magasin',
    'step2_title' => 'Personnel initial',
    'step3_title' => 'Finance & Confirmation',
    'type_shop' => 'Magasin (vente)',
    'type_warehouse' => 'Entrepôt',
    'add_staff' => 'Ajouter un membre du personnel',
    'create_user_account' => 'Créer un compte utilisateur (accès BO/POS)',
    'pin_code' => 'Code PIN (6 chiffres)',
    'role' => 'Rôle',
    'opening_balance' => 'Solde d\'ouverture de caisse',
    'summary' => 'Récapitulatif',
    'summary_store' => 'Magasin : :name (:type)',
    'summary_staff' => ':count membre(s) du personnel seront créés',
    'summary_users' => ':count compte(s) utilisateur avec accès POS/BO',
    'summary_balance' => 'Solde d\'ouverture : :amount $',
    'confirm_create' => 'Créer le magasin',
    'created_success' => 'Magasin ":name" créé avec succès avec toutes les dépendances.',
    'no_staff' => 'Aucun personnel — vous pourrez en ajouter plus tard depuis la gestion du personnel.',
    'step' => 'Étape :current sur :total',
    'next' => 'Suivant',
    'previous' => 'Précédent',
],
```

---

## Fichiers à créer

| Fichier | Description |
|---------|-------------|
| `app/Services/StoreProvisioningService.php` | Service de provisioning (création store + staff + user accounts + transaction financière d'ouverture) |
| `database/migrations/xxxx_add_receipt_prefix_to_stores_table.php` | Ajoute le champ `receipt_prefix` pour les tickets POS |

## Fichiers à modifier

| Fichier | Modification |
|---------|-------------|
| `app/Http/Controllers/StoreController.php` | Refonte `create()` et `store()` pour le wizard + appel au service |
| `resources/views/stores/create.blade.php` | Wizard 3 étapes (JS multi-écran, formulaire unique) |
| `app/Http/Controllers/DashboardController.php` | Dynamiser les stats par magasin (boucle sur shops au lieu de hardcoded store_id 1/2) |
| `resources/views/dashboard.blade.php` | Cartes KPI et graphique dynamiques (boucle @foreach) |
| `app/Http/Controllers/Reception/ReceptionController.php` | Supprimer constantes hardcodées, dynamiser `getAccessibleStoreIds()` |
| `app/Http/Controllers/SupplierOrderController.php` | Remplacer `destination_store_id => 3` hardcodé |
| `app/Http/Controllers/SpecialOrderController.php` | Remplacer `store_id => 1` hardcodé (lignes 74 et 122) |
| `app/Services/SpecialOrderService.php` | Remplacer `store_id ?? 3` hardcodé (lignes 79 et 139) |
| `app/Http/Controllers/WebsiteOrderController.php` | Remplacer constantes `FINANCIAL_ACCOUNT_ID=17`, `SYSTEM_USER_ID=1`, fallback `store_id ?? 3` |
| `resources/views/pos/screens/dashboard.blade.php` | Dynamiser `PRINTER_CONFIG.stores` + fallback |
| `resources/views/resellers/reports/invoice.blade.php` | Remplacer adresse hardcodée (ligne 154) |
| `resources/views/resellers/deliveries/delivery-note-pdf.blade.php` | Remplacer "Phnom Penh, Cambodia" hardcodé (ligne 132) |
| `resources/lang/en/messages.php` | Clés `store_wizard.*` + supprimer/remplacer `ca_siem_reap`/`ca_phnom_penh` |
| `resources/lang/fr/messages.php` | Idem FR |
| `app/Models/Store.php` | Ajouter `receipt_prefix` aux fillable |

---

## Ce qui fonctionne déjà dynamiquement (rien à faire)

| Composant | Pourquoi c'est OK |
|-----------|-------------------|
| **Menu financier** | `config/menu.php` utilise `Store::all()` dans `dynamic_submenu` — un nouveau magasin apparaît automatiquement |
| **Routes financières** | `/financial/{store}/*` utilise un paramètre route — fonctionne pour tout store_id |
| **POS Catalog** | `/api/pos/catalog/{storeId}` — dynamique |
| **POS Shifts** | Créés dynamiquement, utilisent le `store_id` du user |
| **Stock batches** | Créés dynamiquement lors des réceptions/transferts |
| **Transferts de stock** | L'UI charge `Store::all()` pour les selects from/to |
| **Inventaire Réception** | L'UI charge `Store::orderBy('name')->get()` dynamiquement |
| **Sale Reports** | Chargent `Store::all()` pour le select |
| **Commandes fournisseur (réception)** | Utilisent `destination_store_id` du formulaire |

---

## Vérification post-implémentation

1. **Créer un magasin** "Kabas - Battambang" (type: shop) via le wizard
2. **Vérifier Dashboard** : la carte KPI du nouveau magasin apparaît (0$ C.A.), la série apparaît dans le graphique
3. **Vérifier Finances** : le magasin apparaît dans le menu Financial, le dashboard financier montre le solde d'ouverture
4. **Créer un staff** avec compte user + PIN via le wizard → vérifier qu'il peut se connecter au POS et à la Réception PWA
5. **POS** : ouvrir un shift sur le nouveau magasin, faire une vente, vérifier la transaction financière
6. **Réception** : se connecter avec le PIN du staff, vérifier que le nouveau magasin apparaît dans les transferts et l'inventaire
7. **Stock** : faire un transfert Warehouse → nouveau magasin, vérifier les batches
8. **Commande fournisseur** : créer une commande avec destination = nouveau magasin, réceptionner
9. **Rapport de vente (consignment)** : si `is_reseller=true`, vérifier que le magasin apparaît dans les rapports fournisseur

---

## Estimation de complexité

| Partie | Complexité |
|--------|-----------|
| StoreProvisioningService | Faible — logique métier simple |
| Wizard create.blade.php | Moyenne — JS multi-étapes + lignes dynamiques staff |
| Dynamiser DashboardController + blade | Moyenne — refactoring du code existant |
| Dynamiser POS Printer Config | Faible — injection JSON + migration prefix |
| Corriger hardcoded store_id (5 fichiers) | Faible — remplacements ponctuels |
| Traductions | Faible |

---

*Ce fichier est un plan. Ne rien implémenter avant validation explicite.*
