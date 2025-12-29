# PWA Reception - Spécification Technique

## Contexte du Projet

### Problème à résoudre
Le personnel de caisse (staff POS) doit pouvoir réceptionner des commandes fournisseur et faire des refills sans avoir accès au backoffice complet. Actuellement, seul le personnel administratif avec accès au BO peut effectuer ces opérations, ce qui crée des délais dans la mise à jour des stocks.

### Solution
Créer une interface PWA simplifiée accessible via `/reception`, utilisant le même système d'authentification PIN que le POS, avec une interface mobile-first adaptée à du personnel non technique.

---

## Décisions Techniques

### Authentification
- **URL** : `bo.kabasconceptstore.com/reception`
- **Pas d'authentification BO classique** : La route est exclue du middleware auth standard
- **Authentification par PIN** : Même code PIN que pour le POS (champ `users.pin_code`)
- **Session** : Le PIN est vérifié côté serveur, l'utilisateur est stocké en session
- **Accès** : Tous les utilisateurs ayant un PIN et un `store_id` peuvent accéder

### Scope des données

#### Commandes Fournisseur
- Afficher uniquement les commandes avec `status = 'waiting_reception'`
- Filtrer par `destination_store_id` selon le magasin de l'utilisateur
- **EXCEPTION HARDCODÉE** : Le personnel de Phnom Penh (store_id = 1) peut aussi voir les commandes de la Warehouse (store_id = 3) car ils sont physiquement au même endroit et c'est le staff PP qui réceptionne les commandes Warehouse

#### Refill
- L'utilisateur choisit un fournisseur dans la liste
- Il voit tous les produits de ce fournisseur
- Il saisit les quantités reçues
- Le refill est créé pour le `destination_store_id` de l'utilisateur

### Logique de Réception Partielle (Commandes Fournisseur)

**Comportement clé** : Les StockBatch sont créés IMMÉDIATEMENT à chaque saisie de quantité, pas à la validation finale.

**Raison** : Permettre au staff de :
1. Commencer une réception
2. Interrompre pour faire une vente (le produit est déjà en stock)
3. Revenir terminer la réception plus tard

**Workflow** :
1. Staff ouvre une commande en `waiting_reception`
2. Pour chaque produit, il saisit la quantité reçue
3. À chaque saisie/modification → Création/MAJ du StockBatch + StockTransaction
4. Le staff peut quitter à tout moment (les stocks sont déjà à jour)
5. Bouton "Finaliser la réception" → Change le statut en `waiting_invoice`
6. La partie facture sera gérée par les admins sur le BO classique

### PWA
- **manifest.json** : Pour permettre "Add to Home Screen" sur iOS/Android
- **Service Worker** : Minimal, juste pour l'installation PWA (pas de mode offline complexe)
- **Icônes** : Nécessaires pour l'affichage sur l'écran d'accueil

---

## Architecture Technique

### Nouveaux Fichiers à Créer

```
app/
├── Http/
│   └── Controllers/
│       └── Reception/
│           └── ReceptionController.php
│
resources/
├── views/
│   └── reception/
│       ├── layouts/
│       │   └── app.blade.php          # Layout mobile-first sans BO
│       ├── login.blade.php            # Page PIN
│       ├── home.blade.php             # 2 boutons : Orders / Refill
│       ├── orders/
│       │   ├── index.blade.php        # Liste commandes en attente
│       │   └── show.blade.php         # Réception d'une commande
│       └── refill/
│           ├── suppliers.blade.php    # Liste fournisseurs
│           └── products.blade.php     # Saisie quantités
│
public/
├── manifest.json
├── sw.js                              # Service worker minimal
└── images/
    └── icons/
        ├── icon-192x192.png
        └── icon-512x512.png
```

### Routes

```php
// routes/web.php - SANS middleware auth

Route::prefix('reception')->group(function () {
    // Login
    Route::get('/', [ReceptionController::class, 'loginForm'])->name('reception.login');
    Route::post('/auth', [ReceptionController::class, 'authenticate'])->name('reception.auth');
    Route::post('/logout', [ReceptionController::class, 'logout'])->name('reception.logout');

    // Protected by session (middleware custom ou vérification dans controller)
    Route::middleware('reception.auth')->group(function () {
        Route::get('/home', [ReceptionController::class, 'home'])->name('reception.home');

        // Supplier Orders
        Route::get('/orders', [ReceptionController::class, 'ordersList'])->name('reception.orders');
        Route::get('/orders/{order}', [ReceptionController::class, 'orderShow'])->name('reception.orders.show');
        Route::post('/orders/{order}/receive-item', [ReceptionController::class, 'receiveItem'])->name('reception.orders.receive-item');
        Route::post('/orders/{order}/finalize', [ReceptionController::class, 'finalizeOrder'])->name('reception.orders.finalize');

        // Refill
        Route::get('/refill', [ReceptionController::class, 'refillSuppliers'])->name('reception.refill');
        Route::get('/refill/{supplier}', [ReceptionController::class, 'refillProducts'])->name('reception.refill.products');
        Route::post('/refill/{supplier}/store', [ReceptionController::class, 'storeRefill'])->name('reception.refill.store');
    });
});
```

### Middleware Custom

```php
// app/Http/Middleware/ReceptionAuth.php
// Vérifie que session('reception_user_id') existe
// Sinon redirige vers reception.login
```

---

## Modèles Existants Utilisés

### SupplierOrder
- `id`, `supplier_id`, `status`, `destination_store_id`, `is_paid`, `order_type`
- Relations : `supplier()`, `products()` (pivot avec `quantity_ordered`, `quantity_received`, `purchase_price`)
- Statuts : `pending` → `waiting_reception` → `waiting_invoice` → `received`

### StockBatch
- `id`, `product_id`, `store_id`, `quantity`, `unit_price`, `source_supplier_order_id`, `source_refill_id`
- Créé lors de la réception

### StockTransaction
- `id`, `stock_batch_id`, `store_id`, `product_id`, `type` (in/out), `quantity`, `reason`
- Reasons : `supplier_reception`, `supplier_refill`

### Refill
- `id`, `supplier_id`, `destination_store_id`, `status`
- Relations : `supplier()`, `products()` (pivot avec `quantity_received`, `purchase_price`)

### User
- `pin_code` : Code PIN 6 chiffres
- `store_id` : Magasin de rattachement

### Store
- IDs importants :
  - Phnom Penh = 1
  - Siem Reap = 2
  - Warehouse = 3

---

## Logique Métier Détaillée

### Réception Commande Fournisseur

```php
// ReceptionController::receiveItem()

public function receiveItem(Request $request, SupplierOrder $order)
{
    $productId = $request->product_id;
    $quantityReceived = $request->quantity_received;

    // 1. Récupérer ou créer le StockBatch pour ce produit/commande
    $batch = StockBatch::firstOrNew([
        'product_id' => $productId,
        'source_supplier_order_id' => $order->id,
        'store_id' => $order->destination_store_id,
    ]);

    $oldQuantity = $batch->quantity ?? 0;
    $difference = $quantityReceived - $oldQuantity;

    if ($batch->exists) {
        // MAJ quantité existante
        $batch->quantity = $quantityReceived;
        $batch->save();
    } else {
        // Nouveau batch
        $purchasePrice = $order->products()->where('product_id', $productId)->first()->pivot->purchase_price;
        $batch->fill([
            'quantity' => $quantityReceived,
            'unit_price' => $purchasePrice,
        ]);
        $batch->save();
    }

    // 2. MAJ pivot table
    $order->products()->updateExistingPivot($productId, [
        'quantity_received' => $quantityReceived
    ]);

    // 3. Créer StockTransaction si différence
    if ($difference != 0) {
        StockTransaction::create([
            'stock_batch_id' => $batch->id,
            'store_id' => $order->destination_store_id,
            'product_id' => $productId,
            'type' => $difference > 0 ? 'in' : 'out',
            'quantity' => abs($difference),
            'reason' => 'supplier_reception',
            'supplier_id' => $order->supplier_id,
            'supplier_order_id' => $order->id,
        ]);
    }

    return response()->json(['success' => true, 'new_quantity' => $quantityReceived]);
}
```

### Finalisation Commande

```php
// ReceptionController::finalizeOrder()

public function finalizeOrder(SupplierOrder $order)
{
    // Vérifier que c'est bien une commande en waiting_reception
    if ($order->status !== 'waiting_reception') {
        return back()->with('error', 'Order cannot be finalized');
    }

    // Changer le statut
    // Si fournisseur consignment → received directement
    // Sinon → waiting_invoice
    if ($order->supplier->type === 'consignment') {
        $order->status = 'received';
    } else {
        $order->status = 'waiting_invoice';
    }
    $order->save();

    return redirect()->route('reception.orders')->with('success', 'Order finalized');
}
```

### Refill

```php
// ReceptionController::storeRefill()

public function storeRefill(Request $request, Supplier $supplier)
{
    $user = User::find(session('reception_user_id'));
    $items = $request->items; // [{product_id, quantity}]

    DB::transaction(function () use ($supplier, $user, $items) {
        // 1. Créer le Refill
        $refill = Refill::create([
            'supplier_id' => $supplier->id,
            'destination_store_id' => $user->store_id,
            'status' => 'received',
        ]);

        foreach ($items as $item) {
            if ($item['quantity'] <= 0) continue;

            $product = Product::find($item['product_id']);
            $purchasePrice = $product->suppliers()
                ->where('supplier_id', $supplier->id)
                ->first()->pivot->purchase_price ?? $product->price;

            // 2. Ajouter au pivot refill_product
            $refill->products()->attach($item['product_id'], [
                'quantity_received' => $item['quantity'],
                'purchase_price' => $purchasePrice,
            ]);

            // 3. Créer StockBatch
            $batch = StockBatch::create([
                'product_id' => $item['product_id'],
                'store_id' => $user->store_id,
                'quantity' => $item['quantity'],
                'unit_price' => $purchasePrice,
                'source_refill_id' => $refill->id,
            ]);

            // 4. Créer StockTransaction
            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $user->store_id,
                'product_id' => $item['product_id'],
                'type' => 'in',
                'quantity' => $item['quantity'],
                'reason' => 'supplier_refill',
                'supplier_id' => $supplier->id,
            ]);
        }
    });

    return redirect()->route('reception.home')->with('success', 'Refill saved');
}
```

---

## Interface Utilisateur

### Principes
- **Mobile-first** : Conçu pour smartphone/tablette
- **Gros boutons** : Minimum 48px de hauteur, facile à toucher
- **Texte lisible** : Font-size minimum 16px
- **Couleurs claires** : Feedback visuel évident (vert = OK, rouge = erreur)
- **Minimal** : Pas de menus complexes, navigation simple

### Écrans

#### 1. Login (PIN)
- Logo Kabas
- Clavier numérique (0-9)
- Affichage masqué du PIN (dots)
- Bouton OK / Effacer

#### 2. Home
- Nom de l'utilisateur connecté
- 2 gros boutons :
  - "📦 Supplier Orders" (avec badge du nombre en attente)
  - "🔄 Refill"
- Bouton logout

#### 3. Liste Commandes
- Cards pour chaque commande :
  - Nom fournisseur
  - Date commande
  - Nombre de produits
  - Bouton "Start Reception"
- Bouton retour

#### 4. Réception Commande
- Header : Nom fournisseur, date
- Liste produits :
  - Photo produit (thumbnail)
  - Nom produit
  - Quantité commandée
  - Input numérique pour quantité reçue (avec +/- buttons)
  - Indicateur si déjà partiellement reçu
- Bouton "Finaliser la réception" (en bas, sticky)
- Bouton "Sauvegarder et quitter" (retour sans finaliser)

#### 5. Sélection Fournisseur (Refill)
- Liste des fournisseurs (cards)
- Bouton retour

#### 6. Saisie Refill
- Header : Nom fournisseur
- Barre de recherche produit
- Liste produits :
  - Photo, nom, (stock actuel optionnel)
  - Input quantité
- Bouton "Valider le refill"

---

## Todo List

1. [ ] Créer la structure PWA (manifest.json, service worker minimal, icônes)
2. [ ] Créer le layout dédié pour /reception (mobile-first, gros boutons)
3. [ ] Créer la page de login PIN
4. [ ] Créer le middleware ReceptionAuth
5. [ ] Créer le ReceptionController avec auth PIN via session
6. [ ] Créer la page d'accueil avec 2 boutons
7. [ ] Créer la page liste des commandes fournisseur (filtrage store + hardcode PP/Warehouse)
8. [ ] Créer la page de réception d'une commande (liste produits, input quantité)
9. [ ] Implémenter la logique de réception partielle (StockBatch immédiat)
10. [ ] Implémenter la finalisation de commande
11. [ ] Créer la page Refill : sélection fournisseur
12. [ ] Créer la page Refill : liste produits avec input quantité
13. [ ] Implémenter la logique Refill (création StockBatch et StockTransaction)
14. [ ] Ajouter les traductions EN/FR
15. [ ] Tests et ajustements UI mobile

---

## Notes Importantes

### Store IDs (CONFIRMÉ en DB)
```php
// Hardcoded dans le code
const STORE_PHNOM_PENH = 1;  // "Kabas - Phnom penh"
const STORE_SIEM_REAP = 2;   // "Kabas - Siem reap"
const STORE_WAREHOUSE = 3;   // "Kabas - Warehouse"

// Logique d'accès aux commandes
if ($user->store_id === STORE_PHNOM_PENH) {
    // Peut voir PP + Warehouse
    $storeIds = [STORE_PHNOM_PENH, STORE_WAREHOUSE];
} else {
    // Ne voit que son store
    $storeIds = [$user->store_id];
}
```

### Référence Code Existant
- `SupplierOrderController::storeReception()` : Logique actuelle de réception (lignes 213-267)
- `RefillController::storeReception()` : Logique actuelle de refill (lignes 35-96)
- `SyncController::users()` : Récupération users avec PIN (lignes 26-32)

### Sécurité
- Les routes `/reception/*` sont exclues de l'auth BO mais protégées par PIN
- Le PIN est vérifié côté serveur (pas en JS côté client comme le POS)
- Session PHP standard pour maintenir l'état connecté
