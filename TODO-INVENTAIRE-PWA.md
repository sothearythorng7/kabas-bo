# Plan : Module Inventaire pour la PWA Réception

## Contexte

Le staff se déplace dans les magasins et chez les revendeurs consignment pour vérifier physiquement les stocks. Aujourd'hui, l'inventaire dans le BO passe par un export/import Excel, inadapté au mobile. On crée une procédure d'inventaire directement dans la PWA Réception, avec scan code-barres (Quagga2, même lib que check-price), saisie des quantités réelles, et application des ajustements alignée sur la logique existante du BO (`InventoryController::apply()`).

## Fichiers à créer

| Fichier | Description |
|---------|-------------|
| `resources/views/reception/inventory.blade.php` | Page unique avec 3 écrans JS (sélection lieu, comptage, revue) |

## Fichiers à modifier

| Fichier | Modification |
|---------|-------------|
| `app/Http/Controllers/Reception/ReceptionController.php` | +3 méthodes : `inventoryIndex`, `inventoryProducts`, `inventoryApply` |
| `routes/web.php` | +3 routes dans le groupe `reception.auth` |
| `resources/views/reception/home.blade.php` | +1 bouton menu "Inventaire" |
| `resources/lang/en/messages.php` | +35 clés traduction (section `reception`) |
| `resources/lang/fr/messages.php` | +35 clés traduction (section `reception`) |

## Aucune migration nécessaire

La table `stock_batches` a déjà `product_id`, `store_id`, `reseller_id`, `quantity`, `unit_price`. La logique d'ajustement crée/modifie des `StockBatch` exactement comme le BO.

## Routes

```
GET  /reception/inventory           → inventoryIndex     (page avec sélection du lieu)
POST /reception/inventory/products  → inventoryProducts  (AJAX: charge la liste produits)
POST /reception/inventory/apply     → inventoryApply     (AJAX: applique les ajustements)
```

## Flow UX (3 écrans dans 1 page, navigation JS)

### Écran 1 : Sélection du lieu
- Dropdown type : Magasin / Revendeur (consignment)
- Dropdown lieu (peuplé dynamiquement selon le type, données passées par le contrôleur via `@json`)
- Bouton "Commencer l'inventaire" → AJAX POST `/inventory/products` → charge la liste → passe à l'écran 2

### Écran 2 : Comptage
- Barre de progression (X / Y comptés)
- Bouton scanner (ouvre overlay Quagga2 plein écran, même config que `check-price.blade.php`)
- Champ recherche texte (filtre côté client par nom ou EAN)
- Onglets filtre : Tous | Restants | Comptés | Différences
- Liste de produits, chaque ligne :
  - Nom, marque, EAN
  - Stock théorique (readonly)
  - Input numérique pour le stock réel
  - Bordure colorée : vert si compté sans écart, orange si écart
- Scan → scroll vers le produit correspondant + focus sur l'input quantité
- Bouton sticky "Vérifier les différences" → passe à l'écran 3

### Écran 3 : Revue et validation
- Résumé : X produits comptés, Y avec différences
- Liste des seuls produits avec écart (théorique vs réel, badge +/-)
- Bouton "Appliquer les ajustements" → POST AJAX `/inventory/apply`
- Bouton "Retour au comptage"
- Sur succès → redirect vers `/reception/home` avec message flash

## Logique d'ajustement (réplique `InventoryController::apply()` lignes 338-383)

**Différence > 0 (stock réel > théorique) :**
- Créer un `StockBatch` avec `quantity = difference`, `unit_price = 0` (ajustement inventaire)
- `store_id` ou `reseller_id` selon le lieu

**Différence < 0 (stock réel < théorique) :**
- Déduction FIFO : query `StockBatch` triés par `created_at ASC`, déduire des plus anciens
- Pour magasins : `where('store_id', $storeId)->whereNull('reseller_id')`
- Pour revendeurs : `where('reseller_id', $resellerId)`
- `lockForUpdate()` pour éviter les conflits

**StockTransaction (audit) :** Créé uniquement pour les magasins (la table exige `store_id` NOT NULL). Pour les revendeurs, pas de `StockTransaction` — cohérent avec le comportement actuel du BO.

## Méthodes contrôleur

### `inventoryIndex()`
- Charge `Store::orderBy('name')->get()` et `Reseller::where('type', 'consignment')->orderBy('name')->get()`
- Retourne la vue avec les données

### `inventoryProducts(Request $request)` — JSON
- Valide `location_type` (store/reseller) et `location_id`
- Query `StockBatch` groupé par `product_id`, `SUM(quantity) > 0`
- Charge les `Product` correspondants avec `brand`
- Retourne JSON : `{ location_name, products: [{id, name, ean, brand, theoretical}] }`

### `inventoryApply(Request $request)` — JSON
- Valide `location_type`, `location_id`, `adjustments[]` (product_id + difference)
- `DB::beginTransaction()` + même logique FIFO que le BO
- Retourne JSON succès/erreur

## État côté client

Tout le comptage est stocké en mémoire JS (array d'objets `{id, name, ean, brand, theoretical, real}`). Pas de persistence `localStorage` en phase 1 — le rechargement de page repart de zéro. Suffisant pour un usage normal (l'inventaire se fait en une session).

## Vérification

1. Aller sur `/reception/inventory`
2. Sélectionner un magasin ou revendeur consignment
3. Vérifier que la liste de produits se charge avec les stocks théoriques corrects
4. Scanner un code-barres → le produit correspondant doit se highlight et l'input recevoir le focus
5. Saisir des quantités réelles différentes du théorique
6. Cliquer "Vérifier les différences" → seuls les écarts apparaissent
7. Appliquer → vérifier dans le BO que les `stock_batches` ont été ajustés correctement
8. Vérifier les `stock_transactions` créés (pour les magasins uniquement)
