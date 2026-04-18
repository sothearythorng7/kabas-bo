# KABAS CONCEPT STORE - DOCUMENTATION EXHAUSTIVE DU BACK OFFICE

> Ce document contient la description complète et détaillée de TOUTES les fonctionnalités du Back Office Kabas.
> Son objectif est de permettre la reproduction exacte du système dans un nouveau projet.
> Dernière mise à jour: 2026-03-26

---

## TABLE DES MATIÈRES

1. [Vue d'Ensemble & Architecture](#1-vue-densemble--architecture)
2. [Dashboard Principal](#2-dashboard-principal)
3. [Module Produits](#3-module-produits)
4. [Module Catégories & Marques](#4-module-catégories--marques)
5. [Module Gift Boxes & Gift Cards](#5-module-gift-boxes--gift-cards)
6. [Module Inventaire](#6-module-inventaire)
7. [Module Vouchers (Bons d'achat)](#7-module-vouchers)
8. [Module Stocks](#8-module-stocks)
9. [Module Fournisseurs](#9-module-fournisseurs)
10. [Module Comptabilité & Finance](#10-module-comptabilité--finance)
11. [Module Revendeurs](#11-module-revendeurs)
12. [Module Commandes Website](#12-module-commandes-website)
13. [Module Commandes Spéciales](#13-module-commandes-spéciales)
14. [Module Gestion de Contenu Website](#14-module-gestion-de-contenu-website)
15. [Module Shipping](#15-module-shipping)
16. [Module Popup Events](#16-module-popup-events)
17. [Module POS (Point of Sale)](#17-module-pos)
18. [Module Réception PWA](#18-module-réception-pwa)
19. [Module Fabrique](#19-module-fabrique)
20. [Module BI (Business Intelligence)](#20-module-bi)
21. [Module Staff/RH](#21-module-staffrh)
22. [Module Paramètres](#22-module-paramètres)
23. [Services Métier](#23-services-métier)
24. [Architecture Technique](#24-architecture-technique)

---

## 1. VUE D'ENSEMBLE & ARCHITECTURE

### Stack Technique
- **Backend**: PHP 8.2+, Laravel 12.0, MySQL
- **Frontend BO**: Bootstrap 5 + Blade templates, Chart.js
- **Frontend POS**: Vue.js 3, Pinia (state), Dexie.js (IndexedDB offline), Vue Router
- **Recherche**: Meilisearch via Laravel Scout
- **Auth**: Laravel standard (users table) + Spatie Permission (rôles)
- **i18n**: Spatie Translatable pour les produits, messages.php pour l'interface (FR/EN)
- **PDF**: DomPDF
- **Excel**: Maatwebsite Excel
- **Notifications**: Telegram Bot API
- **Queue/Cache/Session**: Database driver
- **Build**: Vite 7

### Multi-Magasins
Le système gère plusieurs magasins (stores):
- **Store ID 1**: Phnom Penh (boutique)
- **Store ID 2**: Siem Reap (boutique)
- **Store ID 3**: Warehouse (entrepôt, default pour website)

Chaque magasin a son propre stock (via StockBatch), ses transactions financières, et ses shifts POS.

### Modèle de Stock (FIFO)
- Le stock est géré par **lots** (`stock_batches`) avec traçabilité de l'origine
- Chaque lot a: `product_id`, `store_id`, `quantity`, `unit_price`, `source_*_id`
- Les déductions se font en **FIFO** (First In, First Out) par `created_at ASC`
- Chaque mouvement crée un `StockTransaction` (type: in/out, reason: sale, exchange_return, etc.)

### Authentification & Rôles
- **BO**: Login email/password classique Laravel
- **POS**: PIN à 6 chiffres stocké dans `users.pin_code`
- **Réception PWA**: PIN via middleware custom `reception.auth`
- **Rôles**: Admin, Manager, Staff, ACCOUNTING (via Spatie Permission)
- Certains menus sont restreints par rôle (`allowed_roles` dans config/menu.php)

### Structure du Menu (config/menu.php)
Le menu principal est défini dans `config/menu.php` comme un tableau PHP avec support de:
- Menus simples avec `label`, `icon` (Bootstrap Icons), `route`, `active_pattern`
- Sous-menus via `submenu` array
- Sous-menus dynamiques via `dynamic_submenu` (closure)
- Restriction par rôle via `allowed_roles`
- Le rendu est fait dans `resources/views/partials/menu.blade.php` avec la fonction récursive `renderMenuItems()`


---

## 2. DASHBOARD PRINCIPAL

**URL**: `/dashboard`
**Contrôleur**: `DashboardController`
**Vue**: `resources/views/dashboard.blade.php`

### Sélection de Date
- Input date en haut de page (max: aujourd'hui)
- Bouton "Back to Today" quand on consulte une date passée
- La date sélectionnée filtre toutes les données affichées

### Alertes Produits (Collapsible)
Section montrant le nombre de produits ayant des problèmes, avec un lien "Voir" pour chaque type:

| Type d'alerte | Détection | Badge |
|---|---|---|
| **Sans image** | `whereDoesntHave('images')` | warning |
| **Sans description FR** | JSON `description['fr']` null/vide | danger |
| **Sans description EN** | JSON `description['en']` null/vide | info |
| **Rupture de stock** | StockBatch sum = 0 | danger |
| **EAN invalide** | `ean IS NULL OR ean LIKE 'FAKE-%'` | warning |
| **Sans catégorie** | `whereDoesntHave('categories')` | primary |
| **Produits inactifs** | `is_active = false` | secondary |
| **Sans poids** | `shipping_weight IS NULL OR = 0` (actifs uniquement) | warning |

Cliquer sur "Voir" mène à une page paginée (50/page) listant les produits concernés avec lien vers l'édition.

### Alertes Opérationnelles (3 cards conditionnelles)
- **Factures revendeurs impayées**: Nombre + montant total (invoices consignment + buyer avec status `unpaid`/`partially_paid`)
- **Factures consignment impayées**: Nombre + total (sale reports avec status `invoiced` et `is_paid = false`)
- **Messages contact non lus**: Nombre uniquement

### Tableau Commandes Website (conditionnel)
Affiché si des commandes payées existent. Tableau pivot avec colonnes:
- **Statut**: Badge coloré (pending=warning, confirmed=info, processing=primary, shipped=cyan, delivered=success, cancelled=danger)
- **Nombre**: Count par statut
- **Montant**: Total $ par statut
- **Footer**: Totaux

### Alertes Commandes Spéciales (conditionnel)
- **En attente de paiement**: Commandes status=`pending` AND payment_status=`pending` (alert jaune)
- **À traiter**: Commandes payment_status=`paid` AND status NOT IN (`delivered`, `cancelled`) (alert bleue)

### Cards KPI (3 colonnes)

**Card 1: Factures à payer**
- Total count (grand chiffre) + montant total $
- Sources: `GeneralInvoice` (status='pending') + `SupplierOrder` (status='received', type buyer, non payé)
- Bouton lien vers `/financial/overview`

**Card 2: CA Siem Reap (store_id=2)**
- Toggle Jour/Mois
- Vue journalière: Revenue (via `Sale::sumRealRevenue()`) + nombre de ventes
- Vue mensuelle: Même calcul sur le mois entier
- Bouton lien vers page détail ventes journalières

**Card 3: CA Phnom Penh (store_id=1)**
- Même structure que Card 2

### Graphiques (2 colonnes, Chart.js)

**Graphique 1: Factures par statut (Bar Chart)**
- X: ["To Pay", "Paid"]
- Data: Counts de GeneralInvoice + SupplierOrder par statut
- Couleurs: Bleu (impayé), Vert (payé)

**Graphique 2: Tendance CA mensuel (Line Chart)**
- X: 6 derniers mois
- 3 lignes: Total, Siem Reap, Phnom Penh
- Couleurs: Bleu (total), Jaune (SR), Vert (PP)

### Page Détail Ventes Journalières
**URL**: `/dashboard/daily-sales/{store}?date=YYYY-MM-DD`

Affiche pour un magasin et une date donnés:
- **Revenue total**: Via `Sale::sumRealRevenue()` (exclut paiements voucher)
- **Total avant remise**: Somme prix × quantité de tous les items
- **Total remises**: Remises item-level (% ou fixe) + remises sale-level
- Table détaillée de chaque vente avec items, shifts, échanges


---

## 3. MODULE PRODUITS

**URL**: `/products`
**Contrôleur**: `App\Http\Controllers\ProductController`
**Vues**: `resources/views/products/`
**Modèle**: `App\Models\Product`

### Modèle de Données

**Table**: `products`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | Identifiant auto-incrémenté |
| `ean` | string (nullable) | Code EAN / code-barres principal |
| `name` | json | Nom traduit `{"en": "...", "fr": "..."}` |
| `description` | json (nullable) | Description traduite `{"en": "...", "fr": "..."}` |
| `slugs` | json (nullable) | Slugs par locale `{"en": "...", "fr": "..."}` |
| `seo_title` | json (nullable) | Titre SEO par locale |
| `meta_description` | json (nullable) | Meta description par locale |
| `price` | decimal(10,2) | Prix de vente TTC |
| `price_btob` | decimal(10,2) (nullable) | Prix revendeur (B2B) |
| `shipping_weight` | integer (nullable) | Poids d'expédition en grammes |
| `brand_id` | foreignId (nullable) | Référence vers `brands` |
| `color` | string (nullable) | Couleur du produit |
| `size` | string (nullable) | Taille du produit |
| `is_active` | boolean (default false) | Actif sur le site web |
| `is_active_pos` | boolean (default false) | Actif sur le POS |
| `is_best_seller` | boolean (default false) | Marqué "best seller" |
| `is_resalable` | boolean (default false) | Disponible pour les revendeurs |
| `allow_overselling` | boolean (default false) | Autoriser la vente même si stock = 0 |
| `attributes` | json (nullable) | Attributs supplémentaires dynamiques |
| `variation_group_id` | foreignId (nullable) | Groupe de variations |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Casts Eloquent

```php
'name' => 'array',
'description' => 'array',
'slugs' => 'array',
'seo_title' => 'array',
'meta_description' => 'array',
'attributes' => 'array',
'shipping_weight' => 'integer',
'is_active' => 'boolean',
'is_active_pos' => 'boolean',
'is_best_seller' => 'boolean',
'is_resalable' => 'boolean',
'allow_overselling' => 'boolean',
```

### Relations

| Relation | Type | Modèle cible | Description |
|----------|------|-------------|-------------|
| `brand` | belongsTo | Brand | Marque du produit |
| `categories` | belongsToMany | Category | Catégories (table pivot `category_product`) |
| `suppliers` | belongsToMany | Supplier | Fournisseurs avec `purchase_price` dans le pivot |
| `stores` | belongsToMany | Store | Magasins avec `alert_stock_quantity` dans le pivot |
| `images` | hasMany | ProductImage | Images triées par `sort_order` ASC |
| `primaryImage` | hasOne | ProductImage | Première image (`sort_order` le plus bas) |
| `barcodes` | hasMany | ProductBarcode | Codes-barres multiples |
| `stockBatches` | hasMany | StockBatch | Lots de stock dans tous les magasins |
| `variationGroup` | belongsTo | VariationGroup | Groupe de variations partagé |
| `variationAttributes` | hasMany | ProductVariationAttribute | Attributs de variation propres au produit |
| `recipes` | hasMany | Recipe | Recettes de fabrication |

### Méthodes Métier

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `getTotalStock` | `getTotalStock(?Store $store): int` | Somme des quantités de stock_batches filtrées par store (ou toutes) |
| `getResellerStock` | `getResellerStock(Reseller $reseller): int` | Stock actuellement chez un revendeur |
| `getReservedQuantity` | `getReservedQuantity(Store $store): int` | Quantité réservée par les popup events actifs |
| `getAvailableStock` | `getAvailableStock(Store $store): int` | `getTotalStock(store) - getReservedQuantity(store)` |
| `removeStock` | `removeStock(Store $store, int $qty): void` | Déduction FIFO des stock_batches |
| `findByBarcode` | `static findByBarcode(string $code): ?Product` | Recherche par EAN ou via table barcodes |

### Page Index (Liste des produits)

**URL**: `/products`
**Vue**: `resources/views/products/index.blade.php`

#### Barre de recherche et filtres
- **Champ de recherche texte**: Utilise Meilisearch via Laravel Scout en priorité. En cas d'échec de Meilisearch, fallback sur une requête SQL `LIKE` sur le champ `name` et `ean`.
- **Filtre par marque**: Select dropdown avec toutes les marques
- **Pagination**: Sélecteur avec options 25, 50, 100 résultats par page

#### Colonnes du tableau

| Colonne | Contenu | Interaction |
|---------|---------|-------------|
| **Actions** | Menu dropdown (Éditer, Dupliquer, Voir sur le site) | Liens |
| **Alerte** | Icône d'alerte si problèmes détectés (pas d'image, pas de description, EAN fake, etc.) | Tooltip avec la liste des problèmes |
| **EAN** | Code EAN du produit | Texte statique |
| **Nom** | Nom du produit (locale courante) | Texte statique |
| **Marque** | Nom de la marque | Texte statique |
| **Prix** | Prix de vente | Texte statique |
| **Prix B2B** | Prix revendeur | Texte statique |
| **Actif Website** | Statut actif/inactif pour le site | **Select inline** (Oui/Non) → AJAX POST |
| **Actif POS** | Statut actif/inactif pour le POS | **Select inline** (Oui/Non) → AJAX POST |
| **Best Seller** | Marqueur best seller | **Select inline** (Oui/Non) → AJAX POST |
| **Resalable** | Disponible pour revendeurs | **Select inline** (Oui/Non) → AJAX POST |
| **Poids** | Poids d'expédition (grammes) | **Input inline** → AJAX POST on blur |
| **Photos** | Nombre de photos | Badge numérique |

#### Édition inline (AJAX)
Chaque champ inline envoie une requête AJAX `POST` vers `/products/{id}/toggle-field` avec :
- `field`: nom du champ (`is_active`, `is_active_pos`, `is_best_seller`, `is_resalable`, `shipping_weight`)
- `value`: nouvelle valeur
- Retour JSON `{success: true}` avec notification toast

### Formulaire de Création

**URL**: `/products/create`
**Vue**: `resources/views/products/create.blade.php`

#### Champs du formulaire

| Champ | Type HTML | Validation | Description |
|-------|-----------|------------|-------------|
| **EAN** | text + bouton | nullable, unique | Code EAN avec bouton "Generate Fake EAN" qui génère `FAKE-{8 chiffres aléatoires}`. Vérification d'unicité en AJAX avant soumission |
| **Marque** | select | nullable | Liste des marques |
| **Prix** | number (step=0.01) | required, min:0 | Prix de vente TTC en $ |
| **Prix B2B** | number (step=0.01) | nullable, min:0 | Prix revendeur |
| **Poids** | number (integer) | nullable, min:0 | Poids en grammes |
| **Nom EN** | text | required | Nom en anglais (obligatoire) |
| **Nom FR** | text | nullable | Nom en français |
| **Actif Website** | checkbox | boolean | Coché = visible sur le site |
| **Actif POS** | checkbox | boolean | Coché = visible au POS |
| **Best Seller** | checkbox | boolean | Coché = marqué best seller |
| **Autoriser Overselling** | checkbox | boolean | Coché = vente possible sans stock |

#### Boutons
- **Enregistrer**: Crée le produit et redirige vers la page d'édition
- **Annuler**: Retour à la liste

### Formulaire d'Édition

**URL**: `/products/{id}/edit`
**Vue**: `resources/views/products/edit.blade.php`

#### Alertes produit
En haut de la page d'édition, un bandeau d'alertes est affiché si le produit a des problèmes :
- Pas d'image
- Description manquante (FR ou EN)
- EAN invalide (FAKE-*)
- Pas de catégorie assignée
- Pas de poids d'expédition

#### Organisation en 9 onglets

**Onglet 1 : Général**
- Mêmes champs que le formulaire de création (EAN, Marque, Prix, etc.)
- Bouton "Enregistrer"

**Onglet 2 : Catégories**
- Liste des catégories actuellement assignées avec bouton "Retirer" (X) chacune
- Select dropdown pour ajouter une nouvelle catégorie → bouton "Ajouter"
- Les opérations d'attach/detach se font via requêtes séparées

**Onglet 3 : Fournisseurs**
- Tableau des fournisseurs assignés : Nom, Prix d'achat, Actions (Modifier prix, Retirer)
- Select dropdown pour ajouter un nouveau fournisseur avec champ "Prix d'achat" (purchase_price)
- Modification du prix d'achat via modal ou inline edit
- Opérations: attach (avec purchase_price), detach, update pivot price

**Onglet 4 : Variations**
- Gestion du groupe de variations (`variation_group_id`)
- Un groupe de variations relie plusieurs produits qui sont des variantes d'un même article (ex: même t-shirt en S, M, L)
- Affichage des autres produits du même groupe
- Attributs de variation (ProductVariationAttribute) : type (taille, couleur) + valeur
- Gestion du groupe : créer, assigner à un groupe existant, retirer du groupe

**Onglet 5 : Magasins (Stock)**
- Tableau avec une ligne par magasin : Nom du magasin, Stock actuel (somme des batches), Seuil d'alerte, Actions
- Le seuil d'alerte est stocké dans la table pivot `product_store` (`alert_stock_quantity`)
- Modification du seuil d'alerte via input inline
- **Ajustement manuel de stock** : Input quantité + bouton pour ajouter ou retirer du stock (crée un StockBatch ou fait une déduction FIFO)
- Badge couleur : vert si stock >= seuil, rouge si stock < seuil

**Onglet 6 : Photos**
- Zone d'upload (max 4 Mo par image, formats jpg/png/webp)
- Galerie des images existantes avec :
  - **Drag & drop** pour réordonner (`sort_order`)
  - Bouton **"Définir comme principale"** (met le `sort_order` le plus bas)
  - Bouton **"Supprimer"** avec confirmation
- Stockage dans `storage/app/public/products/`

**Onglet 7 : Descriptions**
- Éditeur de texte riche (TinyMCE ou textarea) pour chaque locale :
  - Description FR
  - Description EN
- Enregistrement séparé des descriptions

**Onglet 8 : Codes-barres**
- Tableau des codes-barres existants : Code, Type, Principal (oui/non), Actions
- Types supportés : `ean13`, `ean8`, `upc`, `internal`
- Ajout d'un nouveau code-barres : champ code + select type + checkbox "Principal"
- Un seul code-barres peut être marqué comme principal à la fois
- Suppression avec confirmation

**Onglet 9 : SEO**
- Pour chaque locale (FR et EN) :
  - **Titre SEO** : input text, recommandation max 70 caractères, compteur de caractères
  - **Meta description** : textarea, recommandation max 160 caractères, compteur de caractères
- Enregistrement via bouton commun

### Fonction Dupliquer

**URL**: `POST /products/{id}/duplicate`

La duplication d'un produit :
1. Copie tous les champs du produit source
2. Génère un nouveau EAN de type `FAKE-{8 chiffres}`
3. Préfixe le nom avec `COPY -` dans toutes les locales
4. Met `is_active` et `is_active_pos` à `false` par défaut
5. Copie les relations : catégories, fournisseurs (avec prix d'achat), images
6. Ne copie PAS le stock
7. Redirige vers la page d'édition du nouveau produit

### Routes

```
GET    /products                       → index
GET    /products/create                → create
POST   /products                       → store
GET    /products/{product}/edit        → edit
PUT    /products/{product}             → update
POST   /products/{product}/duplicate   → duplicate
POST   /products/{product}/toggle-field → toggleField (AJAX)
DELETE /products/{product}             → destroy
```


---

## 4. MODULE CATÉGORIES & MARQUES

### 4.1 Catégories

**URL**: `/categories`
**Contrôleur**: `App\Http\Controllers\CategoryController`
**Vue**: `resources/views/categories/index.blade.php`
**Modèles**: `Category`, `CategoryTranslation`, `CategorySlugHistory`

#### Modèle de Données

**Table**: `categories`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `parent_id` | foreignId (nullable) | Référence vers catégorie parente (hiérarchie 3 niveaux max) |
| `sort_order` | integer (default 0) | Ordre d'affichage |
| `is_active` | boolean (default true) | Actif/inactif |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Table**: `category_translations`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `category_id` | foreignId | Référence vers `categories` |
| `locale` | string | Code locale (`fr`, `en`) |
| `name` | string | Nom traduit |
| `full_slug` | string | Slug complet (incluant le slug du parent, ex: `vetements/robes`) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Table**: `category_slug_histories`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `category_id` | foreignId | |
| `locale` | string | |
| `old_slug` | string | Ancien slug pour redirections 301 |
| `created_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `parent` | belongsTo(Category) | Catégorie parente |
| `children` | hasMany(Category) | Sous-catégories |
| `translations` | hasMany(CategoryTranslation) | Traductions |
| `products` | belongsToMany(Product) | Produits assignés |
| `slugHistories` | hasMany(CategorySlugHistory) | Historique des slugs |

#### Interface

L'interface est une **page unique** avec un arbre hiérarchique et des **modals** pour le CRUD.

**Affichage en arbre** :
- Structure `<ul>` / `<li>` imbriquée sur 3 niveaux maximum
- Chaque nœud affiche : Nom (locale courante), nombre de produits, boutons (Éditer, Ajouter enfant, Supprimer)
- Les catégories sans enfants et sans produits peuvent être supprimées

**Modal de création** :
- **Parent** : Select dropdown (optionnel, vide = catégorie racine)
- **Nom FR** : Input text (onglet FR)
- **Nom EN** : Input text (onglet EN)
- Bouton Enregistrer

**Modal d'édition** :
- Mêmes champs que la création + possibilité de changer le parent
- Le changement de parent déclenche une mise à jour récursive des slugs de tous les enfants

#### Logique métier

1. **Génération de slugs** : Le contrôleur génère automatiquement un slug unique à partir du nom de chaque locale. Le `full_slug` est composé du slug parent + `/` + slug courant.
2. **Mise à jour récursive** : Quand un slug parent change, tous les `full_slug` des enfants sont recalculés récursivement.
3. **Historique des slugs** : Chaque ancien slug est sauvegardé dans `category_slug_histories` pour permettre des redirections 301 sur le site public.
4. **Invalidation du cache** : Après chaque modification, le cache front du site public est vidé (`Cache::tags('categories')->flush()` ou équivalent).

#### Routes

```
GET    /categories              → index (affichage arbre + modals)
POST   /categories              → store
PUT    /categories/{category}   → update
DELETE /categories/{category}   → destroy
```

### 4.2 Marques

**URL**: `/brands`
**Contrôleur**: `App\Http\Controllers\BrandController`
**Vue**: `resources/views/brands/index.blade.php`
**Modèle**: `App\Models\Brand`

#### Modèle de Données

**Table**: `brands`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom de la marque (non traduit) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `products` | hasMany(Product) | Produits de cette marque |

#### Interface

- **Liste paginée** : 20 marques par page
- Chaque ligne : Nom de la marque, Nombre de produits, Boutons (Éditer, Supprimer)
- **Création** : Formulaire simple avec champ `name` unique
- **Édition** : Même formulaire, pré-rempli
- **Suppression** : Possible uniquement si aucun produit n'est associé

#### Routes

```
GET    /brands              → index
POST   /brands              → store
PUT    /brands/{brand}      → update
DELETE /brands/{brand}      → destroy
```


---

## 5. MODULE GIFT BOXES & GIFT CARDS

### 5.1 Gift Boxes (Coffrets Cadeaux)

**URL**: `/gift-boxes`
**Contrôleur**: `App\Http\Controllers\GiftBoxController`
**Vues**: `resources/views/gift-boxes/`
**Modèle**: `App\Models\GiftBox`

#### Modèle de Données

**Table**: `gift_boxes`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `ean` | string (nullable) | Code EAN |
| `name` | json | Nom traduit `{"en": "...", "fr": "..."}` |
| `description` | json (nullable) | Description traduite |
| `slugs` | json (nullable) | Slugs par locale |
| `price` | decimal(10,2) | Prix de vente |
| `price_btob` | decimal(10,2) (nullable) | Prix revendeur |
| `brand_id` | foreignId (nullable) | Marque |
| `is_active` | boolean (default false) | Actif sur le site |
| `is_best_seller` | boolean (default false) | Marqué best seller |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `brand` | belongsTo(Brand) | Marque |
| `categories` | belongsToMany(Category) | Catégories |
| `images` | hasMany(GiftBoxImage) | Images triées par `sort_order` |
| `primaryImage` | hasOne(GiftBoxImage) | Première image |
| `products` | belongsToMany(Product) | Produits contenus, via `gift_box_items` avec colonne pivot `quantity` |
| `items` | hasMany(GiftBoxItem) | Lignes de contenu du coffret |

#### Page Index

- Liste paginée avec colonnes : EAN, Nom, Marque, Prix, Prix B2B, Actif, Best Seller, Nb photos
- Boutons : Créer, Éditer, Supprimer

#### Formulaire Création/Édition

**Champs principaux** (onglet Général) :
- EAN (text)
- Marque (select)
- Prix (number step=0.01)
- Prix B2B (number step=0.01)
- Nom EN, Nom FR (text)
- Description EN, Description FR (textarea/rich text)
- Actif (checkbox)
- Best Seller (checkbox)

**Onglet Images** :
- Upload d'images (max 2048 Ko par fichier)
- Galerie avec réordonnancement drag & drop
- Bouton "Définir comme principale" par image
- Bouton "Supprimer" par image

**Onglet Catégories** :
- Select dropdown pour ajouter une catégorie
- Liste des catégories assignées avec bouton retirer

**Onglet Produits (Contenu du coffret)** :
- Recherche de produit (select/autocomplete)
- Champ quantité par produit
- Tableau des produits inclus : Nom, Quantité, Actions (Modifier quantité, Retirer)
- Ajout d'un produit avec quantité
- Modification de la quantité d'un produit existant
- Retrait d'un produit du coffret

#### Routes

```
GET    /gift-boxes                  → index
GET    /gift-boxes/create           → create
POST   /gift-boxes                  → store
GET    /gift-boxes/{giftBox}/edit   → edit
PUT    /gift-boxes/{giftBox}        → update
DELETE /gift-boxes/{giftBox}        → destroy
```

### 5.2 Gift Cards (Cartes Cadeaux)

**URL**: `/gift-cards`
**Contrôleur**: `App\Http\Controllers\GiftCardController`
**Vues**: `resources/views/gift-cards/`
**Modèle**: `App\Models\GiftCard`

#### Modèle de Données

**Table**: `gift_cards`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | json | Nom traduit `{"en": "...", "fr": "..."}` |
| `description` | json (nullable) | Description traduite |
| `amount` | decimal(10,2) | Montant de la carte |
| `is_active` | boolean (default true) | Active/inactive |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `categories` | belongsToMany(Category) | Catégories |
| `codes` | hasMany(GiftCardCode) | Codes générés lors des ventes |

#### Fonctionnement

- Les Gift Cards sont des templates avec un montant prédéfini
- Quand un client achète une Gift Card sur le site, un `GiftCardCode` unique est généré automatiquement
- Ce code peut ensuite être utilisé comme moyen de paiement sur le site

#### Interface

- CRUD simple avec liste paginée
- Création : Nom FR/EN, Description FR/EN, Montant, Actif
- Édition : mêmes champs
- Pas de gestion des codes depuis le BO (générés automatiquement par le site)

#### Routes

```
GET    /gift-cards                    → index
GET    /gift-cards/create             → create
POST   /gift-cards                    → store
GET    /gift-cards/{giftCard}/edit    → edit
PUT    /gift-cards/{giftCard}         → update
DELETE /gift-cards/{giftCard}         → destroy
```


---

## 6. MODULE INVENTAIRE

**URL**: `/inventory`
**Contrôleur**: `App\Http\Controllers\InventoryController`
**Vues**: `resources/views/inventory/`
**Export**: `App\Exports\InventoryExport`

### Workflow Complet

L'inventaire suit un processus en 5 étapes :

```
1. Sélection (store/reseller + marque)
   → 2. Export template Excel
      → 3. Remplissage terrain (quantités réelles)
         → 4. Import + Prévisualisation des écarts
            → 5. Confirmation + Application des ajustements
```

### Étape 1 : Sélection

**Formulaire de sélection** :
- **Type de cible** : Radio (Magasin / Revendeur)
- **Magasin** : Select dropdown (si type = Magasin)
- **Revendeur** : Select dropdown (si type = Revendeur)
- **Marque** : Select dropdown (filtre les produits par marque)
- Bouton "Exporter le template"

### Étape 2 : Export du Template Excel

Le template Excel généré contient :

| Colonne | Contenu | Éditable |
|---------|---------|----------|
| A: Product ID | ID du produit | **NON** (cellule verrouillée) |
| B: Name | Nom du produit | **NON** (verrouillé) |
| C: Brand | Nom de la marque | **NON** (verrouillé) |
| D: EAN | Code EAN | **NON** (verrouillé) |
| E: Theoretical Qty | Stock théorique actuel | **NON** (verrouillé) |
| F: Real Qty | Quantité réelle comptée | **OUI** (à remplir) |
| G: Difference | Formule `=F-E` | **NON** (formule auto) |

- La feuille est protégée par le mot de passe `inventory2024`
- Seule la colonne F (Real Qty) est déverrouillée pour l'édition
- Le stock théorique est calculé au moment de l'export

### Étape 3 : Remplissage terrain

L'utilisateur remplit la colonne "Real Qty" sur le terrain avec les quantités réellement comptées.

### Étape 4 : Import et Prévisualisation

**Formulaire d'import** :
- Upload du fichier Excel rempli
- Sélection du magasin/revendeur cible (doit correspondre à l'export)

**Tableau de prévisualisation des écarts** :

| Colonne | Description |
|---------|-------------|
| Produit | Nom du produit |
| EAN | Code EAN |
| Stock Théorique | Quantité attendue |
| Stock Réel | Quantité comptée |
| Différence | Écart (+ ou -) avec code couleur (vert positif, rouge négatif) |

- Seuls les produits avec une différence ≠ 0 sont affichés
- Résumé en bas : Total surplus, Total manquants, Total ajustements

### Étape 5 : Confirmation et Application

**Bouton "Appliquer les ajustements"** déclenche :

Pour chaque produit avec une différence :
- **Si différence positive** (surplus) : Création d'un `StockBatch` avec `quantity = différence`, `source_type = 'inventory_adjustment'`
- **Si différence négative** (manque) : Déduction FIFO depuis les `stock_batches` existants du magasin. Création d'un `StockTransaction` avec `type = 'out'`, `reason = 'inventory_adjustment'`

Après application :
- Message de succès avec résumé des ajustements
- Retour à la page de sélection

### Routes

```
GET  /inventory              → index (sélection)
POST /inventory/export       → export (génère le template Excel)
GET  /inventory/import       → importForm (formulaire upload)
POST /inventory/import       → import (traitement + prévisualisation)
POST /inventory/apply        → apply (application des ajustements)
```


---

## 7. MODULE VOUCHERS (BONS D'ACHAT)

**URL**: `/vouchers`
**Contrôleur**: `App\Http\Controllers\VoucherController`
**Vues**: `resources/views/vouchers/`
**Service**: `App\Services\VoucherService`
**Modèle**: `App\Models\Voucher`

### Modèle de Données

**Table**: `vouchers`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `code` | string (unique) | Code du voucher, format `KBA` + 9 chiffres aléatoires |
| `amount` | decimal(10,2) | Montant du bon en $ |
| `status` | enum | `active`, `used`, `expired`, `cancelled` |
| `source_type` | enum | `exchange` (créé suite à un échange) ou `manual` (créé manuellement) |
| `source_exchange_id` | foreignId (nullable) | Référence vers l'échange source |
| `used_at` | timestamp (nullable) | Date d'utilisation |
| `used_in_sale_id` | foreignId (nullable) | Référence vers la vente qui a utilisé le voucher |
| `used_at_store_id` | foreignId (nullable) | Magasin où le voucher a été utilisé |
| `expires_at` | timestamp | Date d'expiration |
| `created_by_user_id` | foreignId (nullable) | Utilisateur qui a créé le voucher |
| `created_at_store_id` | foreignId (nullable) | Magasin de création |
| `cancelled_by_user_id` | foreignId (nullable) | Utilisateur qui a annulé le voucher |
| `cancelled_at` | timestamp (nullable) | Date d'annulation |
| `cancellation_reason` | text (nullable) | Raison de l'annulation |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### VoucherService

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `generateCode` | `generateCode(): string` | Génère un code unique `KBA` + 9 chiffres, vérifie l'unicité en base |
| `createFromExchange` | `createFromExchange(Exchange $exchange, float $amount, int $storeId): Voucher` | Crée un voucher depuis un échange, source_type = exchange |
| `createManual` | `createManual(float $amount, int $storeId, ?string $validityPeriod, int $userId): Voucher` | Crée un voucher manuellement depuis le BO |
| `validate` | `validate(string $code): array` | Retourne `{valid: bool, error_code: ?string, voucher: ?Voucher}`. Codes d'erreur possibles : `not_found`, `already_used`, `expired`, `cancelled` |
| `applyToSale` | `applyToSale(Voucher $voucher, Sale $sale, int $storeId): void` | Marque le voucher comme `used`, enregistre la vente et le magasin |
| `cancel` | `cancel(Voucher $voucher, int $userId, string $reason): void` | Annule le voucher avec raison et utilisateur |
| `expireVouchers` | `expireVouchers(): int` | Expire tous les vouchers actifs dont `expires_at` < maintenant. Retourne le nombre expiré. Exécuté par cron artisan. |
| `getStatistics` | `getStatistics(): array` | Retourne les statistiques globales (counts et montants par statut) |

### Périodes de Validité

| Valeur | Durée | Utilisation |
|--------|-------|-------------|
| `1_month` | 1 mois | |
| `3_months` | 3 mois | |
| `6_months` | 6 mois | **Par défaut** (échanges) |
| `1_year` | 1 an | |
| `5_years` | 5 ans | |

### Page Index

**URL**: `/vouchers`

#### 4 Cards Statistiques (en haut)

| Card | Contenu |
|------|---------|
| **Total** | Nombre total de vouchers + montant total $ |
| **Actifs** | Nombre actifs + montant total $ (badge vert) |
| **Utilisés** | Nombre utilisés + montant total $ (badge bleu) |
| **Expirés** | Nombre expirés + montant total $ (badge gris) |

#### Filtres

| Filtre | Type | Description |
|--------|------|-------------|
| **Code** | text | Recherche par code (partiel) |
| **Statut** | select | active / used / expired / cancelled |
| **Magasin** | select | Magasin de création |
| **Date début** | date | Créé après... |
| **Date fin** | date | Créé avant... |

#### Boutons
- **Créer un voucher** : Ouvre le formulaire de création
- **Exporter CSV** : Exporte la liste filtrée en CSV

#### Tableau

| Colonne | Description |
|---------|-------------|
| Code | Code du voucher (format KBA + 9 chiffres) |
| Montant | Montant en $ |
| Statut | Badge coloré (active=vert, used=bleu, expired=gris, cancelled=rouge) |
| Source | "Échange" ou "Manuel" |
| Magasin de création | Nom du magasin |
| Créé par | Nom de l'utilisateur |
| Date de création | Date |
| Date d'expiration | Date |
| Actions | Voir / Annuler |

### Formulaire de Création

**URL**: `/vouchers/create`

| Champ | Type HTML | Validation | Description |
|-------|-----------|------------|-------------|
| **Montant** | number (step=0.01) | required, min:0.01, max:10000 | Montant du bon en $ |
| **Magasin** | select | required | Magasin de création |
| **Période de validité** | select | required | 1_month / 3_months / 6_months / 1_year / 5_years |

- Bouton "Créer" → crée le voucher avec code auto-généré, redirige vers la page show
- Bouton "Annuler" → retour à la liste

### Page Show (Détail)

**URL**: `/vouchers/{voucher}`

Affiche tous les détails du voucher :
- Code (grand format, copyable)
- Montant
- Statut (badge)
- Source (Échange avec lien, ou Manuel)
- Dates : création, expiration, utilisation (si applicable)
- Magasin de création
- Créé par (utilisateur)
- Si utilisé : Vente associée (lien), Magasin d'utilisation
- Si annulé : Annulé par, Date d'annulation, Raison

**Bouton "Annuler le voucher"** (visible uniquement si status = active) :
- Ouvre une **modal** avec champ "Raison de l'annulation" (textarea, required)
- Bouton "Confirmer l'annulation" dans la modal
- L'annulation met le status à `cancelled`, enregistre la raison, l'utilisateur et la date

### Routes

```
GET    /vouchers              → index
GET    /vouchers/create       → create
POST   /vouchers              → store
GET    /vouchers/{voucher}    → show
POST   /vouchers/{voucher}/cancel → cancel
GET    /vouchers/export       → export (CSV)
```

### Cron Artisan

```
php artisan vouchers:expire
```

Commande planifiée (quotidienne) qui :
1. Trouve tous les vouchers avec `status = active` et `expires_at < now()`
2. Met leur `status` à `expired`
3. Logge le nombre de vouchers expirés


---

## 8. MODULE STOCKS

### 8.1 Vue d'Ensemble des Stocks

**URL**: `/stocks`
**Contrôleur**: `App\Http\Controllers\StockController`
**Vue**: `resources/views/stocks/index.blade.php`

#### Filtres
- **Recherche texte** : Recherche par nom ou EAN
- **Filtre par marque** : Select dropdown

#### Tableau

| Colonne | Description |
|---------|-------------|
| EAN | Code EAN du produit |
| Nom | Nom du produit |
| Marque | Marque du produit |
| Stock [Magasin 1] | Quantité dans le magasin 1, avec badge couleur |
| Stock [Magasin 2] | Quantité dans le magasin 2, avec badge couleur |
| Stock [Warehouse] | Quantité dans le warehouse |
| ... | Une colonne par magasin actif |

**Badges de stock** :
- 🟢 Vert : Stock >= seuil d'alerte (`alert_stock_quantity` du pivot `product_store`)
- 🔴 Rouge : Stock < seuil d'alerte
- Le seuil est défini par produit par magasin

### 8.2 Stock Revendeur

**URL**: `/stocks/reseller`
**Vue**: `resources/views/stocks/reseller.blade.php`

#### Filtres
- **Revendeur** : Select dropdown
- **Magasin/Boutique du revendeur** : Select (si le revendeur a plusieurs points de vente)

#### Tableau
- Même structure que le stock magasin mais pour un revendeur spécifique
- Affiche le stock par produit chez le revendeur sélectionné

### 8.3 Mouvements de Stock / Transferts

**URL**: `/stock-movements`
**Contrôleur**: `App\Http\Controllers\StockMovementController`
**Vues**: `resources/views/stock-movements/`
**Modèle**: `App\Models\StockMovement`

#### Modèle de Données

**Table**: `stock_movements`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `from_store_id` | foreignId | Magasin source |
| `to_store_id` | foreignId | Magasin destination |
| `status` | enum | `validated`, `received`, `cancelled` |
| `note` | text (nullable) | Notes libres |
| `validated_by` | foreignId (nullable) | Utilisateur qui a validé |
| `received_by` | foreignId (nullable) | Utilisateur qui a réceptionné |
| `received_at` | timestamp (nullable) | Date de réception |
| `cancelled_at` | timestamp (nullable) | Date d'annulation |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Table**: `stock_movement_items`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `stock_movement_id` | foreignId | Référence vers le mouvement |
| `product_id` | foreignId | Produit |
| `quantity` | integer | Quantité transférée |

#### Workflow des Transferts

```
Création (status = validated)
  → Le stock est immédiatement déduit du magasin source (FIFO)
  → Les produits sont "en transit"
  ├── Réception (status = received)
  │   → Le stock est créé dans le magasin destination (StockBatch)
  │   → Facture interne PDF générée automatiquement
  │   → 2 transactions financières créées :
  │     - CREDIT sur compte 701 du magasin source
  │     - DEBIT sur compte 701 du magasin destination
  └── Annulation (status = cancelled)
      → Le stock est restauré dans le magasin source (StockBatch créé)
```

**Important** : À la création, le statut est directement `validated` (pas de brouillon). Le stock est déduit immédiatement du magasin source.

#### Types de Facture Interne

| De → Vers | Type de facture |
|-----------|----------------|
| Warehouse → Shop | "Approvisionnement" |
| Shop → Warehouse | "Retour de stock" |
| Shop → Shop | "Transfert inter-magasins" |

#### Formulaire de Création

| Champ | Type | Validation | Description |
|-------|------|------------|-------------|
| **Magasin source** | select | required | Magasin d'où part le stock |
| **Magasin destination** | select | required, different de source | Magasin qui reçoit |
| **Note** | textarea | nullable | Commentaire libre |
| **Produits** | zone de recherche | Au moins 1 produit | Recherche produit (AJAX) |

**Zone d'ajout de produits** :
- Champ de recherche AJAX (nom ou EAN)
- Résultats en dropdown : Nom + EAN + Stock disponible dans le magasin source
- Champ quantité par produit ajouté
- Validation : quantité <= stock disponible dans le magasin source
- Tableau récapitulatif des produits ajoutés avec bouton retirer

#### Page Index

- Liste paginée des mouvements avec colonnes : ID, Date, De → Vers, Nb produits, Statut (badge), Actions
- Filtres : magasin source, magasin destination, statut, date range
- Actions : Voir, Réceptionner (si validated), Annuler (si validated)

#### Page Show

- Détails du mouvement : magasins, statut, dates, notes
- Tableau des produits : Nom, EAN, Quantité
- Boutons d'action selon le statut :
  - `validated` : "Réceptionner" + "Annuler"
  - `received` : Télécharger la facture PDF
  - `cancelled` : Aucune action

### 8.4 Pertes de Stock (Stock Losses)

**URL**: `/stock-losses`
**Contrôleur**: `App\Http\Controllers\StockLossController`
**Vues**: `resources/views/stock_losses/`
**Modèle**: `App\Models\StockLoss`

#### Modèle de Données

**Table**: `stock_losses`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `store_id` | foreignId | Magasin concerné |
| `type` | enum | `pure_loss` (perte sèche) ou `supplier_refund` (remboursement fournisseur) |
| `status` | enum | Selon le type (voir workflow ci-dessous) |
| `supplier_id` | foreignId (nullable) | Fournisseur concerné (si type = supplier_refund) |
| `reason` | string | Raison de la perte |
| `notes` | text (nullable) | Notes complémentaires |
| `refund_amount` | decimal(10,2) (nullable) | Montant remboursé par le fournisseur |
| `created_by` | foreignId | Utilisateur créateur |
| `validated_at` | timestamp (nullable) | Date de validation |
| `validated_by` | foreignId (nullable) | Utilisateur qui a validé |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Table**: `stock_loss_items`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `stock_loss_id` | foreignId | Référence vers stock_loss |
| `product_id` | foreignId | Produit perdu |
| `quantity` | integer | Quantité perdue |

#### Workflows par Type

**Perte sèche (`pure_loss`)** :
```
draft → validated
```
- Validation : déduction FIFO du stock + création d'une transaction financière DEBIT sur le compte `60001` (Achats/Dépenses)

**Remboursement fournisseur (`supplier_refund`)** :
```
draft → validated → refund_requested → refund_received
```
- Validation : même déduction FIFO + transaction DEBIT 60001
- Demande de remboursement : changement de statut (traçabilité)
- Réception du remboursement : saisie du montant remboursé → création d'une transaction CREDIT sur le compte 60001

#### Formulaire de Création

| Champ | Type | Validation | Description |
|-------|------|------------|-------------|
| **Magasin** | select | required | Magasin où la perte a lieu |
| **Type** | select | required | pure_loss / supplier_refund |
| **Fournisseur** | select | required si type=supplier_refund | Fournisseur concerné |
| **Raison** | text | required | Raison de la perte |
| **Notes** | textarea | nullable | Commentaires |
| **Produits** | recherche AJAX | Au moins 1 | Recherche + ajout produits avec quantité |

#### Actions

- **Valider** : Confirme la perte, déduit le stock (FIFO), crée la transaction financière
- **Demander remboursement** (supplier_refund uniquement) : Passe en `refund_requested`
- **Confirmer remboursement** (supplier_refund uniquement) : Modal avec champ "Montant remboursé" → crée transaction CREDIT

### Routes

```
GET    /stocks                              → index (vue d'ensemble)
GET    /stocks/reseller                     → resellerStock

GET    /stock-movements                     → index
GET    /stock-movements/create              → create
POST   /stock-movements                     → store
GET    /stock-movements/{movement}          → show
POST   /stock-movements/{movement}/receive  → receive
POST   /stock-movements/{movement}/cancel   → cancel

GET    /stock-losses                        → index
GET    /stock-losses/create                 → create
POST   /stock-losses                        → store
GET    /stock-losses/{loss}                 → show
POST   /stock-losses/{loss}/validate        → validate
POST   /stock-losses/{loss}/request-refund  → requestRefund
POST   /stock-losses/{loss}/confirm-refund  → confirmRefund
```


---

## 9. MODULE FOURNISSEURS

**URL**: `/suppliers`
**Contrôleur**: `App\Http\Controllers\SupplierController`, `App\Http\Controllers\SupplierOrderController`
**Vues**: `resources/views/suppliers/`, `resources/views/supplier_orders/`
**Modèles**: `Supplier`, `SupplierOrder`, `SupplierReturn`, `Refill`

### 9.1 Modèle Fournisseur

**Table**: `suppliers`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom du fournisseur |
| `type` | enum | `buyer` (achat ferme) ou `consignment` (dépôt-vente) |
| `address` | text (nullable) | Adresse |
| `phone` | string (nullable) | Téléphone |
| `email` | string (nullable) | Email |
| `notes` | text (nullable) | Notes |
| `is_raw_material` | boolean (default false) | Si true, c'est un fournisseur de matières premières (exclu de la liste principale) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `contacts` | hasMany(SupplierContact) | Contacts du fournisseur (nom, email, phone, role) |
| `products` | belongsToMany(Product) | Produits fournis avec `purchase_price` dans le pivot |
| `orders` | hasMany(SupplierOrder) | Commandes |
| `returns` | hasMany(SupplierReturn) | Retours |
| `refills` | hasMany(Refill) | Réapprovisionnements rapides |

**Important** : La liste principale des fournisseurs exclut les fournisseurs avec `is_raw_material = true`. Ceux-ci sont gérés dans le module Fabrique.

### 9.2 Page d'Édition du Fournisseur (5 onglets)

**URL**: `/suppliers/{supplier}/edit`
**Vue**: `resources/views/suppliers/edit.blade.php`

#### Onglet 1 : Informations Générales

| Champ | Type | Validation |
|-------|------|------------|
| Nom | text | required |
| Type | select | required (buyer/consignment) |
| Adresse | textarea | nullable |
| Téléphone | text | nullable |
| Email | email | nullable |
| Notes | textarea | nullable |

**Contacts** (sous-section) :
- Tableau des contacts existants : Nom, Email, Téléphone, Rôle, Actions (Éditer/Supprimer)
- Bouton "Ajouter un contact" → formulaire inline ou modal

#### Onglet 2 : Produits

- **Recherche** : Champ recherche Meilisearch (autocomplete)
- **Tableau paginé** des produits assignés :

| Colonne | Description |
|---------|-------------|
| EAN | Code EAN |
| Nom | Nom du produit |
| Prix d'achat | `purchase_price` du pivot (éditable inline) |
| Prix de vente | Prix catalogue |
| Actions | Modifier prix, Retirer |

- **Ajout** : Rechercher un produit → saisir le prix d'achat → Ajouter
- **Modification du prix** : Input inline sur le prix d'achat, sauvegarde AJAX
- **Retrait** : Détache le produit du fournisseur (confirmation)

#### Onglet 3 : Commandes

- Liste des commandes de ce fournisseur, filtrées par statut
- Filtres : statut (pending/waiting_reception/waiting_invoice/received), date range
- Chaque ligne : Numéro, Date, Magasin destination, Nombre d'articles, Total, Statut (badge), Actions
- Bouton "Nouvelle commande" → redirige vers le formulaire de création

#### Onglet 4 : Suivi des Ventes

Suivi des ventes **multi-canal** pour les produits de ce fournisseur :

- **Filtre de période** : date début / date fin
- **3 sections** :
  1. **Ventes POS** : ventes en magasin des produits de ce fournisseur
  2. **Ventes Website** : commandes en ligne
  3. **Ventes Revendeurs** : rapports de vente des revendeurs

Pour chaque canal :
- Tableau avec : Produit, Quantité vendue, Montant total, Prix d'achat moyen, Marge
- Totaux en bas de chaque tableau

#### Onglet 5 : Statut des Paiements

- Résumé des montants : Total commandé, Total payé, Solde restant
- Liste des paiements effectués avec dates et montants
- Liste des commandes en attente de paiement

### 9.3 Commandes Fournisseur

**Modèle**: `SupplierOrder`
**Table**: `supplier_orders`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `supplier_id` | foreignId | Fournisseur |
| `store_id` | foreignId | Magasin destination |
| `status` | enum | `pending`, `waiting_reception`, `waiting_invoice`, `received` |
| `deposit_amount` | decimal(10,2) (default 0) | Montant de l'acompte |
| `deposit_paid` | boolean (default false) | Acompte payé ou non |
| `total` | decimal(10,2) (nullable) | Total de la commande |
| `invoice_number` | string (nullable) | Numéro de facture fournisseur |
| `invoice_date` | date (nullable) | Date de la facture fournisseur |
| `invoice_file` | string (nullable) | Chemin du fichier facture uploadé |
| `notes` | text (nullable) | Notes |
| `ordered_at` | timestamp (nullable) | Date de commande |
| `received_at` | timestamp (nullable) | Date de réception |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Workflow des Commandes

**Pour les fournisseurs de type `buyer` (achat)** :
```
pending → waiting_reception → waiting_invoice → received
```

**Pour les fournisseurs de type `consignment` (dépôt-vente)** :
```
pending → waiting_reception → received
```
(Pas d'étape `waiting_invoice` car pas de facture d'achat pour le consignment)

#### Formulaire de Création

**URL**: `/suppliers/{supplier}/orders/create`
**Vue**: `resources/views/supplier_orders/create.blade.php`

| Champ | Type | Validation | Description |
|-------|------|------------|-------------|
| **Magasin destination** | select | required | Où le stock sera reçu |
| **Acompte** | number (step=0.01) | nullable, min:0 | Montant de l'acompte |
| **Notes** | textarea | nullable | |
| **Produits** | zone de recherche | Au moins 1 | Recherche AJAX (produits du fournisseur) |

**Zone d'ajout de produits** :
- Recherche filtrée sur les produits déjà associés au fournisseur
- Chaque produit ajouté : Nom, EAN, Prix d'achat (pré-rempli depuis le pivot), Quantité commandée
- Possibilité de modifier le prix d'achat pour cette commande spécifiquement
- Tableau récapitulatif avec total

#### Réception des Marchandises

**URL**: `/suppliers/{supplier}/orders/{order}/reception`
**Vue**: `resources/views/supplier_orders/reception.blade.php`

Pour chaque produit de la commande :
- **Quantité commandée** : Affichée (non éditable)
- **Quantité reçue** : Input number (défaut = quantité commandée)
- Les quantités reçues peuvent être différentes des quantités commandées

**À la validation de la réception** :
1. Pour chaque produit avec quantité reçue > 0 :
   - Création d'un `StockBatch` dans le magasin destination
   - Création d'un `StockTransaction` (type=in, reason=supplier_order)
2. Passage du statut à `waiting_invoice` (buyer) ou `received` (consignment)
3. Enregistrement de `received_at`

#### Réception de Facture (Buyer uniquement)

**URL**: `/suppliers/{supplier}/orders/{order}/invoice-reception`
**Vue**: `resources/views/supplier_orders/invoice_reception.blade.php`

| Champ | Type | Description |
|-------|------|-------------|
| **Numéro de facture** | text | Numéro de la facture fournisseur |
| **Date de facture** | date | Date indiquée sur la facture |
| **Fichier facture** | file upload | PDF/image de la facture |
| **Prix facturés** | par produit | Prix unitaire facturé pour chaque produit reçu |
| **Mettre à jour le prix de référence** | checkbox par produit | Si coché, met à jour le `purchase_price` dans le pivot fournisseur-produit et crée un enregistrement `PurchasePriceHistory` |

**À la validation** :
1. Enregistrement de la facture (numéro, date, fichier)
2. Mise à jour optionnelle des prix de référence avec historique (`PurchasePriceHistory`)
3. Passage du statut à `received`

#### Paiement d'une Commande

**Modal de paiement** sur la page show de la commande :

| Champ | Type | Description |
|-------|------|-------------|
| **Montant** | number | Montant payé |
| **Date** | date | Date du paiement |
| **Méthode** | select | Méthode de paiement (cash, virement, etc.) |
| **Référence** | text | Référence de paiement |
| **Justificatif** | file upload | Preuve de paiement |

**À la validation** :
- Création d'une `FinancialTransaction` : DEBIT sur le compte `401` (Fournisseurs à payer)
- Mise à jour du total payé sur la commande

#### Gestion de l'Acompte

- Modal "Payer l'acompte" : montant pré-rempli avec `deposit_amount`
- Crée une `FinancialTransaction` DEBIT sur compte 401
- Met `deposit_paid` à `true`

### 9.4 Réapprovisionnement Rapide (Quick Refill)

**URL**: `/suppliers/{supplier}/refill`
**Contrôleur**: Via `SupplierController` ou `RefillController`
**Modèle**: `Refill`

Processus simplifié de réapprovisionnement sans commande formelle :
1. Sélection du magasin destination
2. Recherche et ajout de produits avec quantités
3. Validation → Crée immédiatement un `Refill` + les `StockBatch` correspondants
4. Pas de workflow de statut : le stock est ajouté instantanément

### 9.5 Retours Fournisseur

**URL**: `/supplier-returns`
**Contrôleur**: `App\Http\Controllers\SupplierReturnController`
**Modèle**: `SupplierReturn`

Concerne les **fournisseurs consignment** uniquement (retour d'invendus).

#### Formulaire de Création

| Champ | Type | Description |
|-------|------|-------------|
| **Fournisseur** | select | Fournisseurs consignment uniquement |
| **Magasin** | select | Magasin d'où partent les produits |
| **Produits** | recherche | Produits avec quantité à retourner |
| **Notes** | textarea | Commentaires |

**À la validation** :
1. Déduction immédiate du stock (FIFO) du magasin sélectionné
2. Création du `SupplierReturn` avec statut
3. **Validation** → génère un PDF de bon de retour

### 9.6 Rapports de Vente Fournisseur (Consignment)

**URL**: `/suppliers/{supplier}/sale-reports`
**Contrôleur**: Via `SupplierController` ou dédié

Processus de réconciliation pour les fournisseurs en dépôt-vente, en **3 étapes** :

**Étape 1 : Sélection**
- Sélection du magasin
- Sélection de la période (date début / date fin)

**Étape 2 : Remplissage automatique**
Les données sont pré-remplies depuis les ventes POS :
- Pour chaque produit du fournisseur vendu durant la période :
  - **Stock initial** (`old_stock`) = Stock actuel + Ventes - Retours + Réapprovisionnements (calcul inverse)
  - **Quantité vendue** : Depuis les données POS (SaleItems)
  - **Quantité retournée** : Depuis les retours fournisseur
  - **Réapprovisionnements** : Depuis les refills
  - **Stock final** : Stock actuel

**Étape 3 : Finalisation**
- Validation du rapport
- Génération du PDF
- Envoi par email aux contacts du fournisseur

**Workflow de statut** :
```
waiting_invoice → invoiced → invoiced_paid
```

**Réception de facture du rapport** :
- Upload du fichier de facture
- Saisie des prix facturés par produit (le fournisseur consignment facture les produits vendus)
- Passage à `invoiced`

**Paiement** :
- Même modal que pour les commandes buyer
- Crée une `FinancialTransaction`
- Passage à `invoiced_paid`

### Routes Fournisseurs

```
GET    /suppliers                                    → index
GET    /suppliers/create                             → create
POST   /suppliers                                    → store
GET    /suppliers/{supplier}/edit                     → edit
PUT    /suppliers/{supplier}                          → update
DELETE /suppliers/{supplier}                          → destroy

GET    /suppliers/{supplier}/orders/create            → createOrder
POST   /suppliers/{supplier}/orders                   → storeOrder
GET    /suppliers/{supplier}/orders/{order}            → showOrder
GET    /suppliers/{supplier}/orders/{order}/reception  → receptionForm
POST   /suppliers/{supplier}/orders/{order}/reception  → processReception
GET    /suppliers/{supplier}/orders/{order}/invoice-reception → invoiceReceptionForm
POST   /suppliers/{supplier}/orders/{order}/invoice-reception → processInvoiceReception
POST   /suppliers/{supplier}/orders/{order}/payment   → processPayment
POST   /suppliers/{supplier}/orders/{order}/deposit    → processDeposit

GET    /suppliers/{supplier}/refill                   → refillForm
POST   /suppliers/{supplier}/refill                   → processRefill

GET    /supplier-returns                              → index
GET    /supplier-returns/create                       → create
POST   /supplier-returns                              → store
POST   /supplier-returns/{return}/validate            → validate

GET    /suppliers/{supplier}/sale-reports              → saleReports
POST   /suppliers/{supplier}/sale-reports              → storeSaleReport
GET    /suppliers/{supplier}/sale-reports/{report}     → showSaleReport
POST   /suppliers/{supplier}/sale-reports/{report}/invoice → processInvoice
POST   /suppliers/{supplier}/sale-reports/{report}/payment → processPayment
```


---

## 10. MODULE COMPTABILITÉ & FINANCE

**URL Préfixe**: `/financial/{store}`
**Contrôleurs**: `Financial/FinancialDashboardController`, `Financial/FinancialTransactionController`, `Financial/FinancialAccountController`, `Financial/FinancialJournalController`, `Financial/GeneralInvoiceController`, `Financial/FinancialShiftController`, `Financial/PaymentMethodController`, `Financial/FinancialBilanController`, `Financial/FinancialReportController`
**Vues**: `resources/views/financial/`

### 10.1 Dashboard Financier

**URL**: `/financial/{store}/dashboard`
**Vue**: `resources/views/financial/dashboard.blade.php`

Affiche un résumé financier **par magasin** :

#### Cards KPI
- **Solde actuel** : Somme des running balances de tous les comptes du magasin
- **Crédits du mois** : Total des transactions CREDIT du mois en cours
- **Débits du mois** : Total des transactions DEBIT du mois en cours
- **Top 5 comptes** : Les 5 comptes les plus actifs avec leurs balances

#### Graphiques
- **Répartition des paiements** : Pie chart par méthode de paiement (mois en cours)
- **Évolution** : Line chart avec sélecteur de période (mois / 6 mois / tout)
- **Alerte factures impayées** : Nombre et montant des factures non réglées

### 10.2 Comptes Financiers

**URL**: `/financial/{store}/accounts`
**Modèle**: `FinancialAccount`

**Table**: `financial_accounts`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `code` | string (unique) | Code comptable (ex: 701, 401, 60001) |
| `name` | string | Nom du compte |
| `type` | enum | `ASSET`, `LIABILITY`, `EXPENSE`, `REVENUE` |
| `parent_id` | foreignId (nullable) | Hiérarchie de comptes |
| `is_active` | boolean (default true) | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Comptes Clés du Système

| Code | Nom | Type | Utilisation |
|------|-----|------|-------------|
| `701` | Shop Sales | REVENUE | Toutes les ventes (POS, website, transferts) |
| `401` | Supplier Payable | LIABILITY | Paiements fournisseurs |
| `60001` | Purchases / Expenses | EXPENSE | Achats, pertes de stock, dépenses générales |
| `50001` | Salaries | EXPENSE | Paiements de salaires |
| `50002` | Utilities | EXPENSE | Services publics |
| `50003` | Supplies | EXPENSE | Fournitures |
| `50004` | Rental | EXPENSE | Loyers |
| `50005` | Other Expenses | EXPENSE | Divers |
| `50006` | Taxes | EXPENSE | Impôts et taxes |
| `50007` | Equipment | EXPENSE | Équipement |

#### Interface
- Liste hiérarchique des comptes avec code, nom, type, balance
- CRUD avec formulaire : code (unique), nom, type (select), parent (select optionnel)

### 10.3 Transactions Financières

**URL**: `/financial/{store}/transactions`
**Modèle**: `FinancialTransaction`

**Table**: `financial_transactions`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `store_id` | foreignId | Magasin |
| `account_id` | foreignId | Compte financier |
| `amount` | decimal(12,2) | Montant |
| `currency` | string (default 'USD') | Devise |
| `direction` | enum | `debit` ou `credit` |
| `balance_before` | decimal(12,2) | Solde du compte avant la transaction |
| `balance_after` | decimal(12,2) | Solde du compte après (running balance) |
| `label` | string | Libellé court |
| `description` | text (nullable) | Description détaillée |
| `status` | enum | `validated`, `pending`, `rejected` |
| `transaction_date` | date | Date de la transaction |
| `payment_method_id` | foreignId (nullable) | Méthode de paiement |
| `user_id` | foreignId | Utilisateur qui a créé la transaction |
| `external_reference` | string (nullable) | Référence externe (n° de facture, etc.) |
| `attachment` | string (nullable) | Chemin du fichier joint |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Running Balance

Chaque transaction enregistre le solde avant et après. Le `balance_after` est calculé :
- `balance_before` = dernier `balance_after` du même compte + même magasin
- Si direction = `credit` : `balance_after = balance_before + amount`
- Si direction = `debit` : `balance_after = balance_before - amount`

#### Page Index (Liste des transactions)

**Filtres** :

| Filtre | Type | Description |
|--------|------|-------------|
| Date début | date | Transactions après cette date |
| Date fin | date | Transactions avant cette date |
| Comptes | multi-select | Filtrer par compte(s) |
| Montant min | number | Montant minimum |
| Montant max | number | Montant maximum |
| Méthodes de paiement | multi-select | Filtrer par méthode |

**Colonnes du tableau** :

| Colonne | Description |
|---------|-------------|
| Date | Date de la transaction |
| Compte | Code + nom du compte |
| Libellé | Label de la transaction |
| Débit | Montant si direction = debit |
| Crédit | Montant si direction = credit |
| Solde | Balance_after |
| Méthode | Méthode de paiement |
| Statut | Badge (validated=vert, pending=jaune, rejected=rouge) |
| Actions | Voir détail, pièce jointe |

**Bouton Export Excel** : Exporte la liste filtrée au format XLSX

#### Pièces Jointes
- Chaque transaction peut avoir un fichier joint (justificatif)
- Upload lors de la création ou ajout ultérieur
- Téléchargement depuis la page de détail

### 10.4 Journaux Financiers

**URL**: `/financial/{store}/journals`
**Modèle**: `FinancialJournal`

**Table**: `financial_journals`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `store_id` | foreignId | Magasin |
| `type` | enum | `in` (entrée) ou `out` (sortie) |
| `account_id` | foreignId | Compte financier |
| `amount` | decimal(12,2) | Montant |
| `reference` | string (nullable) | Référence |
| `description` | text (nullable) | Description |
| `document_path` | string (nullable) | Chemin du document joint |
| `date` | date | Date du journal |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

- Générés automatiquement depuis les transactions
- Vue filtrée par type (in/out), compte, période
- Export disponible

### 10.5 Factures Générales

**URL**: `/financial/{store}/general-invoices`
**Modèle**: `GeneralInvoice`

**Table**: `general_invoices`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `store_id` | foreignId | Magasin |
| `account_id` | foreignId | Compte financier de destination |
| `category_id` | foreignId (nullable) | Catégorie de facture |
| `label` | string | Libellé |
| `note` | text (nullable) | Notes |
| `amount` | decimal(10,2) | Montant |
| `invoice_date` | date (nullable) | Date de la facture |
| `due_date` | date (nullable) | Date d'échéance |
| `payment_date` | date (nullable) | Date de paiement effectif |
| `payment_proof` | string (nullable) | Chemin du justificatif de paiement |
| `status` | enum | `pending` (à payer) ou `paid` (payé) |
| `attachment` | string (nullable) | Fichier facture joint |
| `transaction_id` | foreignId (nullable) | Transaction financière de paiement |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Interface

**Page Index** :
- Vue mixte combinant les factures générales (`GeneralInvoice`) et les factures de commandes fournisseur (`SupplierOrder` avec statut received non payé)
- Filtres : statut (pending/paid), compte, date range
- Colonnes : Date, Libellé, Montant, Échéance, Statut (badge), Actions

**Formulaire Création/Édition** :

| Champ | Type | Validation |
|-------|------|------------|
| Libellé | text | required |
| Montant | number (step=0.01) | required, min:0.01 |
| Compte | select | required (liste des comptes) |
| Catégorie | select | nullable |
| Date de facture | date | nullable |
| Date d'échéance | date | nullable |
| Note | textarea | nullable |
| Pièce jointe | file upload | nullable |

**Action "Marquer comme payée"** :
- Modal avec : date de paiement, méthode de paiement, justificatif (file upload)
- Crée une `FinancialTransaction` (DEBIT sur le compte sélectionné)
- Met le statut à `paid`, enregistre `payment_date` et `transaction_id`

**Export Excel** : Export de la liste filtrée

### 10.6 Shifts Financiers (Réconciliation de caisse)

**URL**: `/financial/{store}/shifts`

- Affiche le shift actuel (ouvert) du magasin ou la liste des shifts passés
- Pour chaque shift : ouverture (cash), fermeture (cash), différence, cash in/out
- **Action "Forcer la fermeture"** (admin) : Ferme un shift resté ouvert par erreur

### 10.7 Méthodes de Paiement

**URL**: `/financial/payment-methods`
**Modèle**: `PaymentMethod`

**Table**: `payment_methods`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom affiché |
| `code` | string (unique) | Code interne |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Méthodes prédéfinies** :

| Code | Nom |
|------|-----|
| `CASH` | Cash |
| `BANK_TRANSFER` | Bank Transfer |
| `VISA_CARD` | Visa Card |
| `ABA_KHQR` | ABA KHQR |

CRUD simple pour ajouter/modifier/supprimer des méthodes.

### 10.8 Bilan Financier

**URL**: `/financial/{store}/bilan`
**Contrôleur**: `Financial/FinancialBilanController`
**Vue**: `resources/views/financial/bilan.blade.php`

Bilan mensuel Profit & Loss par magasin.

#### Structure du Bilan

**Revenus** :
- Compte 701 (Shop Sales) : Total des CREDIT du mois

**Dépenses** (détaillées par catégorie) :

| Catégorie | Source |
|-----------|--------|
| Salaires | Depuis `salary_payments` du mois |
| Fournisseurs | Transactions sur compte 401 |
| Utilities | Transactions sur compte 50002 |
| Supplies | Transactions sur compte 50003 |
| Rental | Transactions sur compte 50004 |
| Other | Transactions sur compte 50005 |
| Taxes | Transactions sur compte 50006 |
| Equipment | Transactions sur compte 50007 |

**Rapports additionnels** :
- **Expense Book Daily** : Journal des dépenses jour par jour
- **Staff Payroll Report** : Détail des paies du mois
- **Monthly Sales by Brand** : Ventes ventilées par marque
- **Supplier Expense Report** : Dépenses fournisseurs détaillées

### 10.9 Rapport Financier Étendu

**URL**: `/financial/{store}/report` ou `/financial/report-overview`
**Contrôleur**: `Financial/FinancialReportController`
**Vue**: `resources/views/financial/report.blade.php`, `resources/views/financial/report-overview.blade.php`

Version enrichie du bilan incluant :
- **Commandes website** : Revenus des ventes en ligne
- **Commandes spéciales** : Revenus des commandes sur mesure
- **Ventes revendeurs** : Revenus de la distribution

**Report Overview** :
- Vue consolidée multi-magasins
- Comparaison côte à côte des performances par magasin
- Totaux consolidés

### Routes Comptabilité

```
GET  /financial/{store}/dashboard               → dashboard
GET  /financial/{store}/accounts                 → accounts.index
POST /financial/{store}/accounts                 → accounts.store
PUT  /financial/{store}/accounts/{account}       → accounts.update
DELETE /financial/{store}/accounts/{account}      → accounts.destroy

GET  /financial/{store}/transactions             → transactions.index
GET  /financial/{store}/transactions/create      → transactions.create
POST /financial/{store}/transactions             → transactions.store
GET  /financial/{store}/transactions/{tx}        → transactions.show
GET  /financial/{store}/transactions/export      → transactions.export

GET  /financial/{store}/journals                 → journals.index

GET  /financial/{store}/general-invoices         → generalInvoices.index
GET  /financial/{store}/general-invoices/create  → generalInvoices.create
POST /financial/{store}/general-invoices         → generalInvoices.store
GET  /financial/{store}/general-invoices/{inv}/edit → generalInvoices.edit
PUT  /financial/{store}/general-invoices/{inv}   → generalInvoices.update
POST /financial/{store}/general-invoices/{inv}/mark-paid → generalInvoices.markPaid
GET  /financial/{store}/general-invoices/export  → generalInvoices.export

GET  /financial/{store}/shifts                   → shifts.index
POST /financial/{store}/shifts/{shift}/force-close → shifts.forceClose

GET  /financial/payment-methods                  → paymentMethods.index
POST /financial/payment-methods                  → paymentMethods.store
PUT  /financial/payment-methods/{method}         → paymentMethods.update
DELETE /financial/payment-methods/{method}        → paymentMethods.destroy

GET  /financial/{store}/bilan                    → bilan
GET  /financial/{store}/report                   → report
GET  /financial/report-overview                  → reportOverview
```


---

## 11. MODULE REVENDEURS

**URL**: `/resellers`
**Contrôleur**: `App\Http\Controllers\ResellerController`, `App\Http\Controllers\ResellerInvoiceController`
**Vues**: `resources/views/resellers/`, `resources/views/reseller_invoices/`
**Modèle**: `App\Models\Reseller`

### 11.1 Modèle Revendeur

**Table**: `resellers`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom du revendeur |
| `type` | enum | `buyer` (achat ferme) ou `consignment` (dépôt-vente) |
| `address` | text (nullable) | Adresse |
| `city` | string (nullable) | Ville |
| `country` | string (nullable) | Pays |
| `phone` | string (nullable) | Téléphone |
| `email` | string (nullable) | Email |
| `tax_id` | string (nullable) | Numéro fiscal |
| `is_active` | boolean (default true) | Actif/inactif |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `contacts` | hasMany(ResellerContact) | Contacts du revendeur |
| `deliveries` | hasMany(ResellerStockDelivery) | Livraisons de stock |
| `salesReports` | hasMany(ResellerSalesReport) | Rapports de vente (consignment) |
| `productPrices` | belongsToMany(Product) | Prix personnalisés par produit |
| `stockBatches` | hasMany(StockBatch) | Stock actuellement chez le revendeur |
| `invoices` | hasMany(ResellerInvoice) | Factures |

### 11.2 Page Overview

**URL**: `/resellers/overview`
**Vue**: `resources/views/resellers/overview.blade.php`

#### Statistiques Globales (4 cards)
- **Total Revendeurs** : Nombre total de revendeurs
- **Revendeurs Actifs** : Nombre de revendeurs actifs
- **Valeur du Stock** : Valeur totale du stock chez tous les revendeurs (somme qty × unit_price des StockBatch)
- **Chiffre d'Affaires** : Revenue total depuis les revendeurs

#### Performance par Revendeur (Tableau)

| Colonne | Description |
|---------|-------------|
| Revendeur | Nom |
| Type | buyer / consignment |
| Revenue | CA total |
| Unités vendues | Nombre total d'articles vendus |
| Moy./mois | Moyenne mensuelle |
| Stock actuel | Valeur du stock en cours |
| Factures impayées | Nombre + montant |

#### Stock par Revendeur
- Détail du stock actuel par produit chez chaque revendeur

### 11.3 CRUD Revendeur

**Formulaire Création/Édition** :

| Champ | Type | Validation |
|-------|------|------------|
| Nom | text | required |
| Type | select | required (buyer/consignment) |
| Adresse | textarea | nullable |
| Ville | text | nullable |
| Pays | text | nullable |
| Téléphone | text | nullable |
| Email | email | nullable |
| N° fiscal | text | nullable |
| Actif | checkbox | boolean |

**Gestion des contacts** (sous-section) :
- Ajout/modification/suppression de contacts : nom, email, téléphone, rôle

### 11.4 Livraisons de Stock

**URL**: `/resellers/{reseller}/deliveries`
**Modèle**: `ResellerStockDelivery`

#### Workflow

```
draft → ready_to_ship → shipped → (ou cancelled)
```

#### Formulaire de Création

| Champ | Type | Description |
|-------|------|-------------|
| **Produits** | recherche AJAX | Sélection de produits à livrer |
| **Quantité** | number par produit | Quantité à livrer |
| **Prix unitaire** | number par produit | Prix de vente au revendeur (pré-rempli depuis productPrices si existant) |
| **Notes** | textarea | Commentaires |

#### Statut `ready_to_ship` :
- La livraison est préparée, prête à être expédiée
- Génération du bon de livraison PDF

#### Statut `shipped` :
1. **Déduction FIFO** du stock warehouse (store_id=3)
2. **Création de StockBatch** chez le revendeur (avec `reseller_id`)
3. **Génération automatique de la facture** (ResellerInvoice)

#### Statut `cancelled` :
- Annulation de la livraison (possible uniquement avant shipped)

### 11.5 Rapports de Vente (Consignment)

**URL**: `/resellers/{reseller}/sales-reports`
**Modèle**: `ResellerSalesReport`

Réconciliation mensuelle pour les revendeurs en dépôt-vente :
- Remplissage automatique depuis les données POS
- Même logique que les rapports fournisseur consignment (section 9.6)

### 11.6 Factures Revendeur

**URL**: `/reseller-invoices`
**Contrôleur**: `ResellerInvoiceController`
**Vue**: `resources/views/reseller_invoices/index.blade.php`
**Modèle**: `ResellerInvoice`

**Table**: `reseller_invoices`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `reseller_id` | foreignId | Revendeur |
| `type` | enum | `delivery` (facture de livraison/buyer) ou `consignment` (facture de ventes consignment) |
| `amount` | decimal(10,2) | Montant total |
| `status` | enum | `unpaid`, `partially_paid`, `paid` |
| `due_date` | date (nullable) | Date d'échéance |
| `paid_amount` | decimal(10,2) (default 0) | Montant déjà payé |
| `notes` | text (nullable) | Notes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Page Index

**Filtres** :
- Revendeur (select)
- Statut (unpaid / partially_paid / paid)
- Type (delivery / consignment)
- Date range

**Colonnes** :

| Colonne | Description |
|---------|-------------|
| N° | ID de la facture |
| Revendeur | Nom |
| Type | delivery / consignment |
| Montant | Total $ |
| Payé | Montant payé $ |
| Reste | Montant restant $ |
| Statut | Badge (unpaid=rouge, partially_paid=orange, paid=vert) |
| Échéance | Date |
| Actions | Voir, Paiement, PDF |

#### Paiements Partiels

Le système supporte les **paiements partiels** :
- Modal de paiement avec champ "Montant" (max = montant restant)
- Chaque paiement crée une `FinancialTransaction` (CREDIT)
- Le `paid_amount` est incrémenté
- Le statut passe à `partially_paid` puis `paid` quand tout est réglé

#### Export PDF
- Génération de la facture au format PDF
- Contient : informations du revendeur, détail des produits, totaux

### 11.7 Retours de Stock Revendeur

**URL**: `/resellers/{reseller}/returns`

Quand un revendeur retourne du stock :
1. Sélection des produits et quantités retournées
2. Validation → Création de `StockBatch` au warehouse (store_id=3)
3. Mise à jour du stock revendeur (déduction chez le revendeur)

### Routes Revendeurs

```
GET    /resellers                                    → index
GET    /resellers/overview                           → overview
GET    /resellers/create                             → create
POST   /resellers                                    → store
GET    /resellers/{reseller}/edit                     → edit
PUT    /resellers/{reseller}                          → update
DELETE /resellers/{reseller}                          → destroy

GET    /resellers/{reseller}/deliveries/create        → createDelivery
POST   /resellers/{reseller}/deliveries               → storeDelivery
GET    /resellers/{reseller}/deliveries/{delivery}     → showDelivery
POST   /resellers/{reseller}/deliveries/{delivery}/ship → shipDelivery
POST   /resellers/{reseller}/deliveries/{delivery}/cancel → cancelDelivery

GET    /resellers/{reseller}/sales-reports             → salesReports
POST   /resellers/{reseller}/sales-reports             → storeSalesReport

GET    /reseller-invoices                              → invoices.index
GET    /reseller-invoices/{invoice}                    → invoices.show
POST   /reseller-invoices/{invoice}/payment            → invoices.payment
GET    /reseller-invoices/{invoice}/pdf                → invoices.pdf

POST   /resellers/{reseller}/returns                   → storeReturn
```


---

## 12. MODULE COMMANDES WEBSITE

**URL**: `/website-orders`
**Contrôleur**: `App\Http\Controllers\WebsiteOrderController`
**Vues**: `resources/views/website-orders/`
**Modèle**: `App\Models\WebsiteOrder`

### 12.1 Modèle WebsiteOrder

**Table**: `orders`

> Note : Le modèle est nommé `WebsiteOrder` pour éviter les conflits avec les commandes fournisseur, mais il utilise la table `orders` partagée avec le site public.

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `order_number` | string (unique) | Numéro de commande généré |
| `source` | string (default 'website') | Source de la commande |
| `status` | enum | `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled` |
| `payment_status` | enum | `pending`, `paid` |
| `total` | decimal(10,2) | Total TTC |
| `subtotal` | decimal(10,2) | Sous-total (avant frais) |
| `shipping_cost` | decimal(10,2) (default 0) | Frais de livraison |
| `tax` | decimal(10,2) (default 0) | Taxes |
| `discount` | decimal(10,2) (default 0) | Remise |
| `guest_email` | string (nullable) | Email du client (guest checkout) |
| `guest_phone` | string (nullable) | Téléphone du client |
| `shipping_name` | string (nullable) | Nom du destinataire |
| `shipping_address` | text (nullable) | Adresse de livraison |
| `shipping_city` | string (nullable) | Ville |
| `shipping_country` | string (nullable) | Pays |
| `shipping_postal_code` | string (nullable) | Code postal |
| `store_id` | foreignId (default 3) | Magasin source (Warehouse par défaut) |
| `paid_at` | timestamp (nullable) | Date de paiement |
| `payway_tran_id` | string (nullable) | ID de transaction PayWay |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 12.2 Relations

| Relation | Type | Modèle | Description |
|----------|------|--------|-------------|
| `items` | hasMany | WebsiteOrderItem | Lignes de commande |
| `transactions` | hasMany | WebsitePaymentTransaction | Transactions PayWay |

**WebsiteOrderItem** (`order_items`) :

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `order_id` | foreignId | |
| `product_id` | foreignId (nullable) | Produit standard |
| `gift_box_id` | foreignId (nullable) | Coffret cadeau |
| `gift_card_id` | foreignId (nullable) | Carte cadeau |
| `quantity` | integer | Quantité |
| `unit_price` | decimal(10,2) | Prix unitaire |
| `total` | decimal(10,2) | Total ligne |

Relations : `product`, `giftBox`, `giftCard`

**WebsitePaymentTransaction** (`payment_transactions`) :

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `order_id` | foreignId | |
| `tran_id` | string | ID PayWay |
| `status` | integer | Code statut PayWay (0-11) |
| `amount` | decimal(10,2) | Montant |
| `internal_status` | string (nullable) | Statut interne (ex: `refunded`) |
| `refunded_at` | timestamp (nullable) | Date de remboursement |
| `refund_amount` | decimal(10,2) (nullable) | Montant remboursé |

**Codes statut PayWay** :

| Code | Signification |
|------|---------------|
| 0 | Approved |
| 1 | Created |
| 2 | Pending |
| 3 | Declined |
| 4 | Refunded |
| 5 | Error |
| 6 | Timeout |
| 7 | Voided |
| 8 | Pre-Authorized |
| 9 | Auth Declined |
| 10 | Auth Error |
| 11 | Cancelled |

### 12.3 Page Index

**URL**: `/website-orders`

#### Filtres
- **Recherche** : Par numéro de commande, email, téléphone
- **Statut** : select (pending/confirmed/processing/shipped/delivered/cancelled)
- **Statut paiement** : select (pending/paid)
- **Date range** : date début / date fin

#### Compteurs par Statut
En haut de la page, badges montrant le nombre de commandes par statut avec code couleur :
- `pending` → badge jaune
- `confirmed` → badge bleu clair
- `processing` → badge bleu
- `shipped` → badge cyan
- `delivered` → badge vert
- `cancelled` → badge rouge

#### Tableau

| Colonne | Description |
|---------|-------------|
| N° | Numéro de commande |
| Date | Date de création |
| Client | Email + téléphone |
| Articles | Nombre d'items |
| Total | Montant total $ |
| Paiement | Badge statut paiement |
| Statut | Badge statut commande (couleurs) |
| Actions | Voir |

### 12.4 Page Show (Détail)

**URL**: `/website-orders/{order}`

#### Section Informations Client
- Email, Téléphone
- Adresse de livraison complète (nom, adresse, ville, code postal, pays)

#### Section Articles

| Colonne | Description |
|---------|-------------|
| Produit | Nom (avec lien si Product) ou nom du GiftBox/GiftCard |
| Type | product / gift_box / gift_card |
| Prix unitaire | $ |
| Quantité | |
| Total | $ |

Footer : Sous-total, Frais de livraison, Remise, Taxes, **Total**

#### Section Transactions PayWay
- Tableau des transactions de paiement :
  - ID Transaction, Statut (code + description), Montant, Date
  - Description du statut traduite via accessor `status_description`

#### Formulaire Mise à Jour du Statut

| Champ | Type | Description |
|-------|------|-------------|
| **Nouveau statut** | select | Liste des statuts possibles |

Bouton "Mettre à jour le statut" → POST vers `/website-orders/{order}/status`

#### Formulaire Notes

| Champ | Type | Description |
|-------|------|-------------|
| **Notes** | textarea | Notes internes |

Bouton "Enregistrer les notes" → POST vers `/website-orders/{order}/notes`

### 12.5 Annulation d'une Commande Payée

Quand on passe le statut à `cancelled` sur une commande avec `payment_status = paid`, le processus suivant est exécuté :

#### Étape 1 : Remboursement PayWay (`refundPayWay()`)
1. Lecture de la configuration PayWay (`config/payway.php` : `merchant_id`, `api_key`, `refund_url`, `rsa_public_key`)
2. Chiffrement RSA du `merchant_auth` (merchant_id + tran_id)
3. Calcul du hash HMAC-SHA512
4. POST vers l'API de remboursement PayWay
5. En cas d'échec (pas de clé RSA, erreur API), **la réversion interne continue quand même** (dégradation gracieuse) avec un message de warning

#### Étape 2 : Réversion du Stock (`reverseStock()`)
- Pour chaque item de la commande :
  - Ré-ajout de la quantité au dernier `StockBatch` du warehouse (store_id=3) pour ce produit
  - Création d'un `StockTransaction` : `type = 'in'`, `reason = 'website_cancellation'`

#### Étape 3 : Réversion Financière (`reverseFinancialTransaction()`)
- Création d'une `FinancialTransaction` :
  - `direction = 'debit'` sur le compte 701 (Shop Sales)
  - `amount` = total de la commande
  - Running balance recalculé
  - `label` = "Annulation commande website #{order_number}"

#### Étape 4 : Mise à Jour de la Transaction de Paiement
- `internal_status` = `'refunded'`
- `refunded_at` = `now()`
- `refund_amount` = montant de la commande

**Constantes système** :
- `FINANCIAL_ACCOUNT_ID = 17` (compte 701 Shop Sales)
- `SYSTEM_USER_ID = 1`
- Store par défaut : `store_id = 3` (Warehouse)

### Routes

```
GET  /website-orders                     → index
GET  /website-orders/{order}             → show
POST /website-orders/{order}/status      → updateStatus
POST /website-orders/{order}/notes       → updateNotes
```


---

## 13. MODULE COMMANDES SPÉCIALES

**URL**: `/special-orders`
**Contrôleur**: `App\Http\Controllers\SpecialOrderController`
**Vues**: `resources/views/special-orders/`
**Service**: `App\Services\SpecialOrderService`

### 13.1 Description

Les commandes spéciales sont des commandes créées manuellement dans le Back Office (source = `backoffice`), pour des clients qui commandent directement en boutique ou par téléphone. Elles supportent 3 types de paiement : lien de paiement PayWay, espèces, ou virement bancaire.

### 13.2 Modèle de Données

Le modèle utilise la même table `orders` que les commandes website, avec des champs supplémentaires :

| Champ | Type | Description |
|-------|------|-------------|
| *Tous les champs de WebsiteOrder* | | Voir section 12 |
| `source` | string | `'backoffice'` pour les commandes spéciales |
| `payment_type` | enum | `payment_link`, `cash`, `bank_transfer` |
| `payment_token` | string (nullable) | Token aléatoire pour le lien de paiement |
| `payment_link_url` | string (nullable) | URL complète du lien de paiement |
| `deposit_amount` | decimal(10,2) (default 0) | Montant de l'acompte |
| `deposit_paid` | boolean (default false) | Acompte payé |
| `tracking_url` | string (nullable) | URL de suivi de livraison |
| `admin_notes` | text (nullable) | Notes internes (non visibles par le client) |
| `created_by_user_id` | foreignId (nullable) | Utilisateur BO qui a créé la commande |

### 13.3 Formulaire de Création

**URL**: `/special-orders/create`
**Vue**: `resources/views/special-orders/create.blade.php`

#### Section Client

| Champ | Type | Validation | Description |
|-------|------|------------|-------------|
| **Nom du client** | text | required | Nom complet |
| **Email** | email | nullable | Email pour notification / lien paiement |
| **Téléphone** | text | nullable | |

#### Section Commande

| Champ | Type | Validation | Description |
|-------|------|------------|-------------|
| **Magasin** | select | required | Magasin source pour le stock |
| **Type de paiement** | select | required | payment_link / cash / bank_transfer |
| **Acompte** | number (step=0.01) | nullable, min:0 | Montant de l'acompte demandé |

#### Section Livraison (Toggle)

| Champ | Type | Validation | Description |
|-------|------|------------|-------------|
| **Livraison** | checkbox toggle | | Si coché, affiche les champs d'adresse |
| **Nom destinataire** | text | required si livraison | |
| **Adresse** | textarea | required si livraison | |
| **Ville** | text | required si livraison | |
| **Pays** | text | required si livraison | |
| **Code postal** | text | nullable | |

#### Section Produits

- **Recherche AJAX** de produits (nom ou EAN)
- Pour chaque produit ajouté :
  - Nom (affiché)
  - **Prix personnalisé** : Input number, pré-rempli avec le prix catalogue mais modifiable
  - **Quantité** : Input number
  - Bouton retirer

#### Section Options (lignes libres)

- Bouton "Ajouter une option"
- Chaque option : **Label** (text) + **Montant** (number step=0.01)
- Permet d'ajouter des frais personnalisés (gravure, emballage spécial, etc.)

#### Section Remise

| Champ | Type | Description |
|-------|------|-------------|
| **Remise** | number (step=0.01) | Montant de la remise globale |

#### Section Notes

| Champ | Type | Description |
|-------|------|-------------|
| **Notes admin** | textarea | Notes internes |

#### Récapitulatif
- Sous-total (somme des lignes produits + options)
- Remise
- Frais de livraison (si applicable)
- **Total**

Bouton "Créer la commande"

### 13.4 Flux de Paiement par Lien (payment_link)

1. **Création** : Un token aléatoire est généré (`payment_token`)
2. L'URL de paiement est construite : `{APP_URL}/payment/{token}`
3. **Page publique de paiement** (non authentifiée) :
   - Affiche le résumé de la commande (articles, totaux)
   - Intègre le popup iframe PayWay pour le paiement par carte
4. **Callback PayWay** : Quand le paiement est complété :
   - `SpecialOrderService::handleOrderPaid()` est appelé
   - Mise à jour `payment_status = 'paid'`, `paid_at = now()`
   - Création d'une `FinancialTransaction` (CREDIT sur compte 701)
   - Envoi d'email de confirmation au client
   - Notification Telegram

### 13.5 Transitions de Statut

```
pending → processing → shipped → delivered
   └──────────────────────────────→ cancelled
```

#### Actions par Statut

**`pending` → `processing`** :
- Simple changement de statut

**`processing` → `shipped`** :
- Déduction FIFO du stock depuis le magasin sélectionné
- Optionnel : saisie d'une URL de suivi

**`shipped` → `delivered`** :
- Simple changement de statut

**Toute étape → `cancelled`** :
- Si le stock a déjà été déduit : réversion du stock
- Si le paiement a été effectué : même processus d'annulation que les commandes website (section 12.5)

#### Paiement Cash/Virement

Pour les commandes avec `payment_type = cash` ou `bank_transfer` :
- Bouton "Marquer comme payée" sur la page show
- `SpecialOrderService::handleDirectPayment()` :
  - Création d'une `FinancialTransaction` (CREDIT sur compte 701)
  - Mise à jour `payment_status = 'paid'`, `paid_at = now()`

### 13.6 Facture PDF

**Génération** via bouton "Télécharger la facture" sur la page show.

**Format du numéro** : `KB-{id sur 5 chiffres}/{année sur 2 chiffres}`
Exemple : `KB-00042/26`

**Contenu de la facture** :
- Informations de l'entreprise
- Informations du client
- Tableau des articles (produits + options)
- Sous-total, remise, frais de livraison, total
- Informations de paiement

**Vue**: `resources/views/special-orders/invoice-pdf.blade.php`

### 13.7 Actions Supplémentaires

- **Régénérer le lien de paiement** : Génère un nouveau token et une nouvelle URL (invalide l'ancien)
- **Envoyer le lien par email** : Envoie un email au client avec le lien de paiement

### Routes

```
GET    /special-orders                          → index
GET    /special-orders/create                   → create
POST   /special-orders                          → store
GET    /special-orders/{order}                  → show
GET    /special-orders/{order}/edit             → edit
PUT    /special-orders/{order}                  → update
POST   /special-orders/{order}/status           → updateStatus
POST   /special-orders/{order}/mark-paid        → markPaid
GET    /special-orders/{order}/invoice-pdf      → invoicePdf
POST   /special-orders/{order}/regenerate-link  → regeneratePaymentLink
POST   /special-orders/{order}/send-link        → sendPaymentLink
GET    /payment/{token}                         → publicPaymentPage (non authentifié)
POST   /payment/{token}/callback                → paymentCallback
```


---

## 14. MODULE GESTION DE CONTENU WEBSITE

### 14.1 Blog

**URL**: `/blog`
**Contrôleurs**: `App\Http\Controllers\Blog\PostController`, `Blog\CategoryController`, `Blog\TagController`
**Vues**: `resources/views/blog/`

#### Posts (Articles)

**Modèle**: `BlogPost`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `title` | json | Titre traduit `{"en": "...", "fr": "..."}` |
| `slug` | json | Slug par locale |
| `body` | json | Contenu traduit (HTML) |
| `excerpt` | json (nullable) | Résumé traduit |
| `featured_image` | string (nullable) | Image principale |
| `category_id` | foreignId (nullable) | Catégorie du blog |
| `status` | enum | `draft`, `published` |
| `published_at` | timestamp (nullable) | Date de publication |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Relations : `category`, `tags` (belongsToMany)

**Formulaire d'édition** (`resources/views/blog/posts/edit.blade.php`) :
- Onglets FR/EN pour le contenu traduit
- **Titre** : input text par locale
- **Body** : Éditeur riche TinyMCE par locale (HTML)
- **Excerpt** : textarea par locale
- **Image** : Upload / aperçu / supprimer
- **Catégorie** : select dropdown
- **Tags** : multi-select ou input tags
- **Statut** : select (draft / published)
- **Date de publication** : datetime picker (si published)

#### Catégories Blog

CRUD simple : nom traduit (FR/EN), slug auto-généré.

#### Tags

CRUD simple : nom, slug.

### 14.2 Pages Statiques

**URL**: `/pages`
**Contrôleur**: `App\Http\Controllers\PageController`

Pages avec contenu multilingue :
- **Titre** : traduit FR/EN
- **Slug** : par locale
- **Body** : contenu HTML traduit, éditeur riche
- CRUD standard

### 14.3 Hero Slides (Bannières Accueil)

**URL**: `/hero-slides`
**Contrôleur**: `App\Http\Controllers\HeroSlideController`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `image` | string | Image de la bannière |
| `title` | json (nullable) | Titre traduit |
| `subtitle` | json (nullable) | Sous-titre traduit |
| `link` | string (nullable) | URL de destination au clic |
| `sort_order` | integer (default 0) | Ordre d'affichage |
| `is_active` | boolean (default true) | Actif/inactif |

- Upload d'image
- Drag & drop pour réordonner
- Toggle actif/inactif

### 14.4 Home Content (Contenu Accueil)

**URL**: `/home-content`
**Contrôleur**: `App\Http\Controllers\HomeContentController`

Personnalisation de la page d'accueil du site :
- Sections configurables
- Sélection de produits mis en avant (featured products)
- Textes et images par section

### 14.5 Barre Promotionnelle

**URL**: `/promotion-bar`
**Contrôleur**: `App\Http\Controllers\PromotionBarController`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `message` | json | Message traduit FR/EN |
| `background_color` | string | Code couleur hex |
| `text_color` | string | Code couleur hex du texte |
| `start_date` | datetime (nullable) | Début d'affichage |
| `end_date` | datetime (nullable) | Fin d'affichage |
| `is_active` | boolean | Actif/inactif |

Barre affichée en haut du site public avec un message promotionnel (ex: "Livraison gratuite dès 50$").

### 14.6 Messages de Contact

**URL**: `/contact-messages`
**Contrôleur**: `App\Http\Controllers\ContactMessageController`

- Liste read-only des messages soumis via le formulaire de contact du site
- Colonnes : Date, Nom, Email, Sujet, Message (tronqué), Lu (badge)
- Action "Marquer comme lu" → toggle `is_read`
- Pas de réponse depuis le BO (réponse manuelle par email)

### 14.7 Outils Website

**URL**: `/website-tools`
**Contrôleur**: `App\Http\Controllers\WebsiteToolController`

Actions utilitaires :
- **Vider le cache** : Purge le cache du site public (vues, routes, config)
- **Générer le sitemap** : Régénère le sitemap.xml du site public
- **Réindexer Meilisearch** : Relance l'indexation des produits

### Routes Contenu

```
# Blog
GET    /blog/posts                    → index
GET    /blog/posts/create             → create
POST   /blog/posts                    → store
GET    /blog/posts/{post}/edit        → edit
PUT    /blog/posts/{post}             → update
DELETE /blog/posts/{post}             → destroy
GET    /blog/categories               → categories
GET    /blog/tags                     → tags

# Pages
GET    /pages                         → index
GET    /pages/create                  → create
POST   /pages                         → store
GET    /pages/{page}/edit             → edit
PUT    /pages/{page}                  → update

# Hero Slides
GET    /hero-slides                   → index
POST   /hero-slides                   → store
PUT    /hero-slides/{slide}           → update
DELETE /hero-slides/{slide}           → destroy
POST   /hero-slides/reorder           → reorder

# Promotion Bar
GET    /promotion-bar                 → edit
PUT    /promotion-bar                 → update

# Contact Messages
GET    /contact-messages              → index
POST   /contact-messages/{msg}/read   → markAsRead

# Website Tools
POST   /website-tools/clear-cache     → clearCache
POST   /website-tools/sitemap         → generateSitemap
POST   /website-tools/reindex         → reindex
```


---

## 15. MODULE SHIPPING

**URL**: `/shipping`
**Contrôleur**: `App\Http\Controllers\ShippingController`
**Vues**: `resources/views/shipping/`

### 15.1 Pays de Livraison (Shipping Countries)

**URL**: `/shipping/countries`

**Modèle**: `ShippingCountry`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom du pays |
| `code` | string | Code ISO du pays (ex: KH, US, FR) |
| `is_active` | boolean | Livraison active vers ce pays |

- Liste de tous les pays avec toggle `is_active` inline
- Seuls les pays actifs sont proposés comme destinations sur le site

### 15.2 Transporteurs (Shipping Carriers)

**URL**: `/shipping/carriers`
**Vue**: `resources/views/shipping/carriers.blade.php`

**Modèle**: `App\Models\ShippingCarrier`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom du transporteur |
| `tracking_url_template` | string (nullable) | Template d'URL de suivi (ex: `https://track.cambodiapost.post/?id={tracking_number}`) |
| `is_active` | boolean (default true) | Transporteur actif |
| `logo` | string (nullable) | Chemin du logo |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

- CRUD simple
- Le `tracking_url_template` utilise `{tracking_number}` comme placeholder, remplacé par le vrai numéro de suivi lors de l'affichage

### 15.3 Tarifs de Livraison (Shipping Rates)

**URL**: `/shipping/rates`
**Vue**: `resources/views/shipping/rates.blade.php`

**Modèle**: `App\Models\ShippingRate`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `carrier_id` | foreignId | Transporteur |
| `country_id` | foreignId | Pays de destination |
| `weight_min` | integer | Poids minimum (grammes) |
| `weight_max` | integer | Poids maximum (grammes) |
| `price` | decimal(10,2) | Prix de la livraison en $ |
| `delivery_time` | string (nullable) | Délai de livraison estimé (ex: "3-5 jours") |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Logique de Recherche de Tarif

Pour déterminer le prix de livraison d'une commande :
1. Calcul du poids total de la commande (somme des `shipping_weight` des produits × quantités)
2. Recherche du tarif correspondant : `carrier_id` + `country_id` + `weight_min <= poids <= weight_max`
3. Si aucun tarif trouvé, la livraison n'est pas disponible pour cette combinaison

#### Interface

- **Filtres** : transporteur (select), pays (select)
- **Tableau** :

| Colonne | Description |
|---------|-------------|
| Transporteur | Nom |
| Pays | Nom du pays |
| Poids min | En grammes |
| Poids max | En grammes |
| Prix | $ |
| Délai | Texte libre |
| Actions | Éditer, Supprimer |

- **Formulaire Création/Édition** :
  - Transporteur (select)
  - Pays (select)
  - Poids min (number, grammes)
  - Poids max (number, grammes)
  - Prix (number step=0.01)
  - Délai de livraison (text)

### Routes

```
GET    /shipping/countries                → countries
POST   /shipping/countries/{country}/toggle → toggleCountry

GET    /shipping/carriers                 → carriers
POST   /shipping/carriers                 → storeCarrier
PUT    /shipping/carriers/{carrier}       → updateCarrier
DELETE /shipping/carriers/{carrier}       → destroyCarrier

GET    /shipping/rates                    → rates
POST   /shipping/rates                    → storeRate
PUT    /shipping/rates/{rate}             → updateRate
DELETE /shipping/rates/{rate}             → destroyRate
```


---

## 16. MODULE POPUP EVENTS

**URL**: `/popup-events`
**Contrôleur**: `App\Http\Controllers\PopupEventController`
**Vues**: `resources/views/popup-events/`
**Modèle**: `App\Models\PopupEvent`, `App\Models\PopupEventItem`

### 16.1 Description

Les Popup Events représentent des événements éphémères (marchés, foires, pop-up stores) lors desquels des produits sont sortis du stock d'un magasin pour être vendus temporairement à un autre emplacement.

### 16.2 Modèle PopupEvent

**Table**: `popup_events`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `reference` | string (unique) | Format `EVT-YYYY-NNNN` (ex: EVT-2026-0001) |
| `name` | string | Nom de l'événement |
| `location` | string | Lieu |
| `store_id` | foreignId | Magasin source du stock |
| `start_date` | date | Date de début |
| `end_date` | date | Date de fin |
| `status` | enum | `planned`, `active`, `completed`, `cancelled` |
| `notes` | text (nullable) | Notes |
| `created_by_user_id` | foreignId | Utilisateur créateur |
| `activated_at` | timestamp (nullable) | Date d'activation |
| `completed_at` | timestamp (nullable) | Date de complétion |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 16.3 Modèle PopupEventItem

**Table**: `popup_event_items`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `popup_event_id` | foreignId | Événement |
| `product_id` | foreignId | Produit |
| `quantity_allocated` | integer | Quantité allouée (réservée) |
| `quantity_sold` | integer (default 0) | Quantité vendue (calculée à la complétion) |

### 16.4 Workflow

```
planned → active → completed
   └──────────────→ cancelled
```

#### Création (`planned`)
- Formulaire : nom, location, magasin, dates début/fin, notes
- Ajout de produits avec quantités allouées
- Le stock n'est PAS encore déduit, mais il est **réservé** (soft reservation)

#### Activation (`active`)
- **Vérification de disponibilité** : Le système vérifie que le stock disponible (total - déjà réservé par d'autres events actifs) est suffisant pour chaque produit
- Si insuffisant → erreur avec détail des produits en manque
- Si OK → passage à `active`, enregistrement de `activated_at`

#### Complétion (`completed`)
- Calcul automatique de `quantity_sold` pour chaque produit :
  - Basé sur les ventes des **shifts POS** tagués avec cet événement (`popup_event_id` dans la table shifts)
  - `quantity_sold = somme des SaleItems pour les shifts de cet event`
- Enregistrement de `completed_at`

#### Annulation (`cancelled`)
- Libère les réservations de stock
- Possible uniquement si l'événement est `planned` ou `active`

### 16.5 Réservation de Stock (Soft Reservation)

Le mécanisme de réservation est **logique** (soft), pas physique :
- `Product::getReservedQuantity(Store $store)` : Somme de `quantity_allocated` de tous les `PopupEventItem` dont l'événement est en statut `active` pour ce magasin
- `Product::getAvailableStock(Store $store)` : `getTotalStock(store) - getReservedQuantity(store)`
- Le stock reste physiquement dans le magasin, mais le stock "disponible" est réduit
- Les ventes POS normales (hors event) ne peuvent pas vendre plus que le stock disponible

### 16.6 Intégration POS

- Quand un shift est ouvert, si un popup event est actif dans le même magasin, le staff peut **tagger le shift** avec l'événement
- Le tag est stocké dans `shifts.popup_event_id`
- **Composant Vue** : `ShiftStart.vue` inclut un sélecteur d'événement (dropdown des events actifs du magasin)
- Les ventes réalisées pendant un shift tagué sont attribuées à l'événement pour le calcul des quantités vendues

### 16.7 Page Show (Détail d'un événement)

**URL**: `/popup-events/{event}`

#### Cards KPI
- **Nombre de ventes** : Total des ventes des shifts tagués
- **Chiffre d'affaires** : Revenue total
- **Panier moyen** : Revenue / nombre de ventes

#### Tableau Produits

| Colonne | Description |
|---------|-------------|
| Produit | Nom |
| EAN | Code EAN |
| Qté allouée | Quantité réservée |
| Qté vendue | Quantité vendue (si completed, sinon N/A) |
| Taux d'écoulement | `quantity_sold / quantity_allocated × 100%` |
| Stock restant | `quantity_allocated - quantity_sold` |

#### Liste des Shifts
- Shifts POS associés à cet événement
- Date, utilisateur, nombre de ventes, revenue

### Routes

```
GET    /popup-events                         → index
GET    /popup-events/create                  → create
POST   /popup-events                         → store
GET    /popup-events/{event}                 → show
GET    /popup-events/{event}/edit            → edit
PUT    /popup-events/{event}                 → update
POST   /popup-events/{event}/activate        → activate
POST   /popup-events/{event}/complete        → complete
POST   /popup-events/{event}/cancel          → cancel
```


---

## 17. MODULE POS (POINT OF SALE)

**URL**: `/pos`
**Contrôleurs API**: `App\Http\Controllers\POS\ShiftController`, `POS\SyncController`, `POS\CatalogController`, `POS\ExchangeController`, `POS\VoucherController`
**Frontend**: `resources/js/pos/`
**Vues Blade**: `resources/views/pos/`

### 17.1 Architecture Technique

Le POS est une **PWA offline-first** construite avec :
- **Vue.js 3** : Framework frontend
- **Pinia** : State management (stores)
- **Dexie.js v4** : Abstraction IndexedDB pour stockage local offline
- **Vue Router** : Navigation SPA

Le POS fonctionne en mode offline : les ventes sont stockées localement dans IndexedDB et synchronisées avec le serveur quand la connexion est disponible.

### 17.2 Schéma IndexedDB (Dexie v4)

```javascript
// Stores IndexedDB
products    // Catalogue local, indexé par id et ean
sales       // Ventes non synchronisées
users       // Utilisateurs POS avec PIN
shifts      // Shifts locaux (avec popup_event_id)
popup_events // Événements popup actifs
```

### 17.3 Flux d'Authentification

1. **Écran de Login** (`Login.vue`) : L'utilisateur saisit son **PIN à 6 chiffres**
2. **Vérification locale** : Le PIN est vérifié contre les utilisateurs stockés dans IndexedDB (`resources/js/pos/store/user.js`)
3. **Synchronisation catalogue** : Si connecté, le catalogue est synchronisé (`syncCatalog`)
4. **Synchronisation événements** : Les popup events actifs sont synchronisés (`syncActiveEvents`)
5. **Vérification shift** : Le système vérifie s'il y a un shift ouvert non terminé
   - Si oui → Dashboard POS
   - Si non → Écran d'ouverture de caisse

### 17.4 Gestion des Shifts

#### Ouverture de Shift (`ShiftStart.vue`)

| Champ | Type | Description |
|-------|------|-------------|
| **Fond de caisse** | number | Montant cash à l'ouverture |
| **Événement popup** | select (optionnel) | Si un popup event est actif, sélection optionnelle |

**API** : `POST /api/pos/shifts/start`
- Crée un nouveau `Shift` avec `opening_cash`, `store_id`, `user_id`
- Si événement sélectionné : `popup_event_id` enregistré
- **Notification Telegram** envoyée à l'ouverture

#### Fermeture de Shift (`ShiftEnd.vue`)

| Champ | Type | Description |
|-------|------|-------------|
| **Cash en caisse** | number | Montant cash compté |
| **Nombre de visiteurs** | number | Compteur de visiteurs |
| **Cash in** | number | Entrées de cash supplémentaires |
| **Cash out** | number | Sorties de cash |
| **Notes** | textarea | Commentaires |

**API** : `POST /api/pos/shifts/end`
- Enregistre `closing_cash`, `visitors_count`, `cash_in`, `cash_out`
- Calcule la **différence** : `closing_cash - (opening_cash + sales_cash - sales_card + cash_in - cash_out)`
- **Notification Telegram** envoyée à la fermeture (avec résumé des ventes)

#### Changement d'Utilisateur

**API** : `POST /api/pos/shifts/change-user`
- Permet de changer le caissier en cours de shift sans fermer le shift
- Crée une entrée dans la table de jonction `ShiftUser`
- Le shift reste ouvert, seul l'utilisateur actif change

### 17.5 API Catalogue

**`GET /api/pos/catalog/{storeId}`**

Retourne la liste complète des produits actifs au POS pour un magasin donné.

Champs retournés par produit :

| Champ | Description |
|-------|-------------|
| `id` | ID produit |
| `ean` | Code EAN |
| `barcodes` | Tous les codes-barres additionnels |
| `name` | Nom traduit |
| `description` | Description traduite |
| `price` | Prix de vente |
| `price_btob` | Prix B2B |
| `brand` | Nom de la marque |
| `categories` | Catégories assignées |
| `photos` | URLs des images |
| `total_stock` | Stock disponible dans ce magasin |

**Filtre** : Seuls les produits avec `is_active_pos = true` sont retournés.

**`GET /api/pos/search/{storeId}?q=...`**

Recherche de produits via Meilisearch. Retourne la même structure que le catalogue.

### 17.6 Synchronisation des Ventes

**`POST /api/pos/sales/sync`**

Corps de la requête (tableau de ventes) :

```json
{
  "sales": [
    {
      "pos_local_id": "uuid-local",
      "store_id": 1,
      "shift_id": 42,
      "user_id": 3,
      "items": [
        {
          "product_id": 123,
          "type": "product",        // product | gift_box | gift_card
          "quantity": 2,
          "price": 15.00,
          "discount_type": "percent", // percent | fixed | null
          "discount_value": 10
        }
      ],
      "payment_method": "cash",      // cash | card | split
      "split_payments": [            // si payment_method = split
        {"method": "cash", "amount": 10.00},
        {"method": "card", "amount": 5.00}
      ],
      "voucher_code": "KBA123456789", // null si pas de voucher
      "sale_discount_type": "fixed",   // remise globale sur la vente
      "sale_discount_value": 5.00,
      "created_at": "2026-03-26T14:30:00"
    }
  ]
}
```

#### Traitement côté serveur (SyncController)

Pour chaque vente :

1. **Déduplication** : Vérification par `pos_local_id` pour éviter les doublons en cas de re-sync
2. **Création de la vente** (`Sale`) avec ses items (`SaleItem`)
3. **Déduction de stock FIFO** : Pour chaque item, déduction des `stock_batches` du magasin en ordre chronologique (créé le plus ancien en premier)
4. **Gift Card** : Si un item est de type `gift_card`, un `GiftCardCode` unique est généré
5. **Transaction financière** : Création d'une `FinancialTransaction` CREDIT sur le compte 701 du magasin
6. **Application du voucher** : Si `voucher_code` est fourni, le `VoucherService::applyToSale()` est appelé

**Retour** : Mapping `pos_local_id → sale_id` pour que le POS mette à jour ses enregistrements locaux

### 17.7 Échanges et Retours

#### Recherche de Vente

**`GET /api/pos/exchange/lookup-sale?receipt_number=...`**

- Recherche une vente par son numéro
- **Fenêtre de 30 jours** : Seules les ventes de moins de 30 jours sont éligibles
- Retourne les détails de la vente avec ses items

#### Traitement de l'Échange

**`POST /api/pos/exchange/process`**

```json
{
  "sale_id": 123,
  "store_id": 1,
  "user_id": 3,
  "returned_items": [
    {"sale_item_id": 456, "quantity": 1}
  ],
  "new_items": [
    {"product_id": 789, "quantity": 1, "price": 20.00}
  ]
}
```

Traitement via `ExchangeService::processExchange()` :

1. **Calcul du solde** :
   - `total_returned` = somme des prix des articles retournés
   - `total_new` = somme des prix des nouveaux articles
   - `balance` = `total_returned - total_new`

2. **Si balance positive** (le client a un crédit) :
   - Génération d'un **voucher** d'une valeur = balance
   - Validité : 90 jours (3 mois)

3. **Si balance négative** (le client doit payer) :
   - Le client paie la différence (cash ou carte)

4. **Si balance zéro** :
   - Échange direct, pas de paiement ni de voucher

5. **Stock** :
   - Articles retournés : stock restauré (création de StockBatch)
   - Nouveaux articles : stock décrémenté (FIFO)

### 17.8 Vouchers au POS

**`GET /api/pos/voucher/validate?code=...`**

Vérifie si le voucher est valide : actif, non expiré, non utilisé.
Retourne : `{valid: bool, amount: decimal, error_code: ?string}`

**`POST /api/pos/voucher/apply`**

Applique le voucher à une vente. Le montant du voucher est déduit du total. Si le montant du voucher est supérieur au total, seul le total est utilisé (pas de rendu de monnaie sur voucher).

### 17.9 Composants Vue Principaux

| Composant | Fichier | Description |
|-----------|---------|-------------|
| `Login.vue` | `components/Login.vue` | Écran de connexion par PIN |
| `ShiftStart.vue` | `components/ShiftStart.vue` | Ouverture de caisse + sélecteur événement |
| `ShiftEnd.vue` | `components/ShiftEnd.vue` | Fermeture de caisse avec récapitulatif |
| `Products.vue` | `components/Products.vue` | Grille/liste de produits, recherche, scan |
| `VirtualKeypad.vue` | `components/VirtualKeypad.vue` | Clavier virtuel pour saisie tactile |
| `Layout.vue` | `components/Layout.vue` | Layout principal du POS |
| `ModalManager.vue` | `components/ModalManager.vue` | Gestion centralisée des modals |

### 17.10 Vues Blade POS

| Vue | Description |
|-----|-------------|
| `pos/screens/dashboard.blade.php` | Dashboard POS (résumé shift en cours) |
| `pos/screens/sale-detail.blade.php` | Détail d'une vente |

### 17.11 Pinia Stores

| Store | Fichier | Responsabilité |
|-------|---------|----------------|
| `user` | `store/user.js` | Auth, PIN verification, user info |
| `cart` | `store/cart.js` | Gestion du panier courant |
| `sync` | `store/sync.js` | Synchronisation des ventes |

### Routes API POS

```
GET  /api/pos/catalog/{storeId}          → catalog
GET  /api/pos/search/{storeId}           → search
POST /api/pos/shifts/start               → startShift
POST /api/pos/shifts/end                 → endShift
POST /api/pos/shifts/change-user         → changeUser
POST /api/pos/sales/sync                 → syncSales
GET  /api/pos/exchange/lookup-sale       → lookupSale
POST /api/pos/exchange/process           → processExchange
GET  /api/pos/voucher/validate           → validateVoucher
POST /api/pos/voucher/apply              → applyVoucher
```


---

## 18. MODULE RÉCEPTION PWA

**URL**: `/reception`
**Contrôleur**: `App\Http\Controllers\Reception\ReceptionController`
**Vues**: `resources/views/reception/`
**Middleware**: `reception.auth` (authentification par PIN, session-based)

### 18.1 Authentification

- L'accès à la PWA Réception se fait via un **PIN** dédié
- Le middleware `ReceptionAuth` vérifie que le PIN est valide dans la session
- Pas de lien avec le système de login standard du BO
- Session-based : le PIN est vérifié une fois, puis stocké en session

### 18.2 Dashboard Réception

**URL**: `/reception/home`

Affiche les compteurs de tâches en attente :
- **Commandes fournisseur en attente** : Nombre de `SupplierOrder` avec status `waiting_reception`
- **Commandes fabrique en attente** : Nombre de commandes de production à réceptionner
- **Transferts en attente** : Nombre de `StockMovement` avec status `validated` (en transit)

### 18.3 Réception de Commandes Fournisseur

**URL**: `/reception/orders`

#### Liste des Commandes à Recevoir
- Filtre : commandes avec status `waiting_reception`
- Colonnes : Fournisseur, Date, Magasin destination, Nb articles

#### Réception d'une Commande

**URL**: `/reception/orders/{order}`

Pour chaque produit de la commande :
- **Nom du produit** (affiché)
- **Quantité commandée** (affiché)
- **Quantité reçue** : Input number (à remplir par l'opérateur, défaut = quantité commandée)

Bouton "Valider la réception" :
1. Pour chaque produit avec quantité reçue > 0 :
   - Création d'un `StockBatch` dans le magasin destination
   - Création d'un `StockTransaction` (type=in, reason=supplier_order)
2. Passage du statut de la commande à l'étape suivante

### 18.4 Réception de Commandes Fabrique

**URL**: `/reception/factory-orders`

Même principe que la réception fournisseur, mais pour les commandes de production internes :
- Les produits sont des matières premières (`RawMaterial`)
- La réception crée des `RawMaterialStockBatch` au lieu de `StockBatch`

### 18.5 Réapprovisionnement Rapide (Refill)

**URL**: `/reception/refill`

Processus simplifié pour les fournisseurs de type `buyer` :
1. Sélection du fournisseur
2. Scan ou recherche de produits
3. Saisie des quantités reçues
4. Validation → Crée un `Refill` + `StockBatch` immédiatement
5. Pas de commande préalable nécessaire

### 18.6 Retours Fournisseur

**URL**: `/reception/returns`

Pour les fournisseurs `consignment` :
1. Sélection du fournisseur et du magasin
2. Scan ou recherche des produits à retourner
3. Saisie des quantités
4. Validation → Déduction FIFO immédiate du stock

### 18.7 Vérification de Prix

**URL**: `/reception/check-price`

- **Scanner de code-barres** (ou saisie manuelle du code EAN)
- Recherche le produit par EAN ou via la table `barcodes`
- Affiche :
  - Nom du produit
  - Prix de vente
  - Prix B2B
  - Stock par magasin
  - Photo principale

### 18.8 Transferts Inter-Magasins

**URL**: `/reception/transfers`

- **Liste** des transferts en attente de réception (status `validated`, destination = magasin de l'opérateur)
- **Réception** : Confirme la réception → crée les StockBatch en destination

### 18.9 Inventaire Rapide

**URL**: `/reception/quick-inventory`
**Vue**: `resources/views/reception/quick-inventory.blade.php`

Inventaire simplifié par marque :

1. **Sélection** : Magasin + Marque
2. **Liste des produits** : Affiche tous les produits de la marque dans le magasin sélectionné
3. **Pour chaque produit** :
   - Stock théorique (affiché)
   - Stock réel (input à remplir)
4. **Comparaison** : Affiche les écarts
5. **Application** : Ajuste le stock (StockBatch + ou déduction FIFO -)

Supporte aussi l'inventaire des **matières premières** (raw materials) avec le même processus.

### Routes Réception

```
GET  /reception                          → login (formulaire PIN)
POST /reception                          → authenticate
GET  /reception/home                     → dashboard
GET  /reception/orders                   → orders.index
GET  /reception/orders/{order}           → orders.show
POST /reception/orders/{order}/receive   → orders.receive
GET  /reception/factory-orders           → factoryOrders.index
POST /reception/factory-orders/{order}/receive → factoryOrders.receive
GET  /reception/refill                   → refill.form
POST /reception/refill                   → refill.process
GET  /reception/returns                  → returns.form
POST /reception/returns                  → returns.process
GET  /reception/check-price              → checkPrice
POST /reception/check-price              → checkPriceLookup
GET  /reception/transfers                → transfers.index
POST /reception/transfers/{movement}/receive → transfers.receive
GET  /reception/quick-inventory          → quickInventory.form
POST /reception/quick-inventory          → quickInventory.process
POST /reception/quick-inventory/apply    → quickInventory.apply
```


---

## 19. MODULE FABRIQUE

**URL Préfixe**: `/factory`
**Contrôleurs**: `App\Http\Controllers\Factory\RawMaterialController`, `Factory\RecipeController`, `Factory\ProductionController`, `Factory\FactoryDashboardController`, `Factory\FactoryInventoryController`, `Factory\FactorySupplierController`
**Vues**: `resources/views/factory/`

### 19.1 Matières Premières (Raw Materials)

**URL**: `/factory/raw-materials`
**Modèle**: `App\Models\RawMaterial`

**Table**: `raw_materials`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom de la matière première |
| `sku` | string (nullable) | Code SKU |
| `description` | text (nullable) | Description |
| `unit` | string | Unité de mesure (kg, g, L, mL, pièce) |
| `track_stock` | boolean (default true) | Suivre le stock |
| `alert_quantity` | decimal(10,2) (nullable) | Seuil d'alerte stock bas |
| `is_active` | boolean (default true) | Active/inactive |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Relations

| Relation | Type | Description |
|----------|------|-------------|
| `suppliers` | belongsToMany(Supplier) | Fournisseurs avec `purchase_price` dans le pivot (table `raw_material_supplier`) |
| `stockBatches` | hasMany(RawMaterialStockBatch) | Lots de stock |
| `recipeItems` | hasMany(RecipeItem) | Utilisée dans quelles recettes |

#### Interface

**Page Index** (`resources/views/factory/raw-materials/index.blade.php`) :
- Liste paginée avec colonnes : Nom, SKU, Unité, Stock actuel, Seuil d'alerte, Actif, Actions
- Recherche par nom/SKU

**Formulaire Création/Édition** (`create.blade.php`, `edit.blade.php`) :

| Champ | Type | Validation |
|-------|------|------------|
| Nom | text | required |
| SKU | text | nullable, unique |
| Description | textarea | nullable |
| Unité | select | required (kg/g/L/mL/pièce) |
| Suivre le stock | checkbox | boolean |
| Seuil d'alerte | number | nullable |
| Actif | checkbox | boolean |

**Section Fournisseurs** (dans l'édition) :
- Gestion des fournisseurs multiples avec prix d'achat par fournisseur
- Ajout : Select fournisseur (fournisseurs avec `is_raw_material = true`) + prix d'achat
- Modification du prix d'achat
- Retrait d'un fournisseur

### 19.2 Recettes

**URL**: `/factory/recipes`
**Modèle**: `Recipe`, `RecipeItem`

**Table**: `recipes`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `product_id` | foreignId | Produit fini résultant |
| `name` | string (nullable) | Nom de la recette |
| `notes` | text (nullable) | Notes de production |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Table**: `recipe_items`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `recipe_id` | foreignId | Recette |
| `raw_material_id` | foreignId | Matière première |
| `quantity` | decimal(10,3) | Quantité nécessaire (par unité produite) |

#### Fonctionnalités

- **CRUD** : Création d'une recette liée à un produit, avec la liste des matières premières et quantités
- **Calcul `maxProducible`** : Pour chaque matière première de la recette, calcule `stock_disponible / quantité_par_unité`. Le minimum de tous ces calculs donne le nombre maximum d'unités productibles
- **Clonage** : Dupliquer une recette existante pour créer une variante

### 19.3 Productions

**URL**: `/factory/productions`
**Modèle**: `Production`, `ProductionConsumption`

**Table**: `productions`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `recipe_id` | foreignId | Recette utilisée |
| `quantity_produced` | integer | Nombre d'unités produites |
| `produced_at` | datetime | Date de production |
| `batch_number` | string | Numéro de lot auto-généré |
| `status` | enum | `COMPLETED`, `CANCELLED` |
| `notes` | text (nullable) | |
| `created_by` | foreignId | Utilisateur |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Table**: `production_consumptions`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `production_id` | foreignId | Production |
| `raw_material_id` | foreignId | Matière première consommée |
| `quantity_consumed` | decimal(10,3) | Quantité consommée |

#### Workflow de Production

1. **Sélection** de la recette
2. **Saisie** de la quantité à produire (vérification vs `maxProducible`)
3. **Validation** → Crée la `Production` avec :
   - Génération automatique du `batch_number`
   - Pour chaque matière première de la recette :
     - `ProductionConsumption` créé avec `quantity = recipe_item.quantity × quantity_produced`
     - Déduction FIFO du stock de matière première (`RawMaterialStockBatch`)
   - Création d'un `StockBatch` pour le produit fini dans le warehouse (store_id=3)
4. **Suppression** : Possible uniquement si la production date de moins de 24h ET que le stock du produit fini n'a pas été utilisé (vendu, transféré, etc.)

### 19.4 Inventaire Fabrique

**URL**: `/factory/inventory`

Gestion du stock de matières premières :
- Vue d'ensemble du stock par matière première
- Ajustements manuels (entrées/sorties)
- Alertes de stock bas

### 19.5 Dashboard Fabrique

**URL**: `/factory/dashboard`

- **KPIs** : Nombre de productions du mois, valeur totale produite, matières premières en stock
- **Alertes stock bas** : Matières premières sous le seuil d'alerte
- **Métriques de production** : Productions par période, top recettes

### 19.6 Fournisseurs Fabrique

**URL**: `/factory/suppliers`

Gestion spécifique des fournisseurs de matières premières (`Supplier` avec `is_raw_material = true`).
- CRUD séparé de la liste principale des fournisseurs
- Mêmes champs que le modèle Supplier standard
- Commandes de matières premières

### Routes Fabrique

```
GET    /factory/dashboard                    → dashboard
GET    /factory/raw-materials                → rawMaterials.index
GET    /factory/raw-materials/create         → rawMaterials.create
POST   /factory/raw-materials                → rawMaterials.store
GET    /factory/raw-materials/{rm}/edit      → rawMaterials.edit
PUT    /factory/raw-materials/{rm}           → rawMaterials.update
DELETE /factory/raw-materials/{rm}           → rawMaterials.destroy

GET    /factory/recipes                      → recipes.index
GET    /factory/recipes/create               → recipes.create
POST   /factory/recipes                      → recipes.store
GET    /factory/recipes/{recipe}/edit        → recipes.edit
PUT    /factory/recipes/{recipe}             → recipes.update
DELETE /factory/recipes/{recipe}             → recipes.destroy
POST   /factory/recipes/{recipe}/clone       → recipes.clone

GET    /factory/productions                  → productions.index
GET    /factory/productions/create           → productions.create
POST   /factory/productions                  → productions.store
GET    /factory/productions/{prod}           → productions.show
DELETE /factory/productions/{prod}           → productions.destroy

GET    /factory/inventory                    → inventory
GET    /factory/suppliers                    → suppliers.index
POST   /factory/suppliers                    → suppliers.store
PUT    /factory/suppliers/{supplier}         → suppliers.update
DELETE /factory/suppliers/{supplier}         → suppliers.destroy
```


---

## 20. MODULE BI (BUSINESS INTELLIGENCE)

**URL**: `/bi/dashboard`
**Contrôleur**: `App\Http\Controllers\BI\BIDashboardController`
**Vue**: `resources/views/bi/dashboard.blade.php`

### 20.1 Description

Le module BI fournit un tableau de bord analytique exécutif avec des métriques avancées sur la performance commerciale. Toutes les données sont filtrables par période.

### 20.2 Filtre de Période

| Option | Description |
|--------|-------------|
| **Mois** | Mois en cours |
| **Trimestre** | Trimestre en cours |
| **Année** | Année en cours |
| **Personnalisé** | Date début / Date fin libres |

### 20.3 Métriques de Revenue

| Métrique | Calcul | Description |
|----------|--------|-------------|
| **Revenue Total** | Somme des ventes (exclut les paiements par voucher) | CA global |
| **Revenue par Magasin** | Ventilation par `store_id` | CA par boutique |
| **Revenue Website** | Total des commandes website payées | CA e-commerce |
| **Panier Moyen** | Revenue total / nombre de ventes | Valeur moyenne par transaction |

### 20.4 Métriques de Ventes

| Métrique | Calcul | Description |
|----------|--------|-------------|
| **Total Ventes** | Count des `Sale` de la période | Nombre de transactions |
| **Total Articles Vendus** | Sum des `SaleItem.quantity` | Nombre d'articles |
| **Articles Moyens par Vente** | Total articles / Total ventes, ventilé par magasin | Densité du panier |

### 20.5 Métriques de Croissance

| Métrique | Calcul | Description |
|----------|--------|-------------|
| **Croissance Revenue** | `(revenue_période - revenue_période_précédente) / revenue_période_précédente × 100` | % évolution du CA |
| **Croissance Ventes** | Même calcul sur le nombre de ventes | % évolution des transactions |

Badge : vert si positif, rouge si négatif, avec flèche indicative.

### 20.6 Analyses Produits

#### Top 10 Produits par Quantité

| Colonne | Description |
|---------|-------------|
| Rang | # |
| Produit | Nom |
| Quantité vendue | Total qty |
| Revenue | Total $ |
| Marge brute | Qty × (price - purchase_price) |

#### Analyse de Marge

Pour chaque produit vendu :
- **Marge brute** = `quantity × (selling_price - purchase_price)`
- `purchase_price` récupéré depuis le pivot `product_supplier`
- Classement par marge décroissante

### 20.7 Analyses Revendeurs

#### Top 10 Revendeurs par Quantité
- Revendeur, Quantité totale vendue, Revenue total

#### Top 10 Revendeurs par Revenue
- Revendeur, Revenue total, Nombre de transactions

### 20.8 Analyses Marques

#### Top 10 Marques par Quantité
- Marque, Quantité vendue, Nombre de produits distincts

### 20.9 Analyses Catégories

#### Top 10 Catégories par Revenue
- Catégorie (avec hiérarchie), Revenue total, Quantité vendue
- La catégorie est résolue hiérarchiquement (sous-catégorie → catégorie parente)

### 20.10 Répartition des Paiements

| Colonne | Description |
|---------|-------------|
| Méthode | Nom de la méthode (Cash, Card, KHQR, Split, Voucher) |
| Nombre | Count de ventes avec cette méthode |
| Total | Montant total par méthode |
| % | Pourcentage du total |

Visualisation en Pie Chart.

### 20.11 Valorisation du Stock

| Colonne | Description |
|---------|-------------|
| Magasin | Nom |
| Valeur du stock | `SUM(stock_batches.quantity × stock_batches.unit_price)` par magasin |

Ligne séparée pour le stock en consignment chez les revendeurs.

### 20.12 Walk-in & Taux de Conversion

| Métrique | Source | Calcul |
|----------|--------|--------|
| Visiteurs | `shifts.visitors_count` | Somme sur la période |
| Ventes | Count des `Sale` | Nombre de transactions |
| Taux de conversion | `(ventes / visiteurs) × 100` | % |

Par magasin et global.

### 20.13 Évolution Mensuelle

Graphique Line Chart (Chart.js) montrant l'évolution mois par mois :
- Axe X : Mois (derniers 12 mois ou période sélectionnée)
- Lignes : Revenue total, Revenue par magasin
- Indicateurs de tendance (hausse/baisse)

### Routes

```
GET /bi/dashboard                → dashboard (avec query params: period, start_date, end_date)
```


---

## 21. MODULE STAFF / RH

**URL Préfixe**: `/settings/staff`
**Contrôleur**: `App\Http\Controllers\StaffController`
**Vues**: `resources/views/staff/`
**Modèle Principal**: `App\Models\StaffMember`
**Services**: `PayrollService`, `CommissionService`

### 21.1 Modèle StaffMember

> **Important** : Le modèle `StaffMember` est **distinct** du modèle `User`. Un `StaffMember` peut être lié à un `User` pour l'accès au BO/POS, mais ils sont indépendants.

**Table**: `staff_members`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom complet |
| `email` | string (nullable) | Email |
| `phone` | string (nullable) | Téléphone |
| `address` | text (nullable) | Adresse |
| `hire_date` | date | Date d'embauche |
| `store_id` | foreignId | Magasin d'affectation |
| `contract_status` | enum | `active`, `terminated` |
| `contract_end_date` | date (nullable) | Date de fin de contrat |
| `termination_reason` | text (nullable) | Raison du départ |
| `user_id` | foreignId (nullable) | Lien vers un compte User |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 21.2 CRUD Staff

**Page Index** : Liste paginée avec colonnes : Nom, Email, Magasin, Statut contrat, Date embauche, Actions

**Formulaire Création** :

| Champ | Type | Validation |
|-------|------|------------|
| Nom | text | required |
| Email | email | nullable |
| Téléphone | text | nullable |
| Adresse | textarea | nullable |
| Date d'embauche | date | required |
| Magasin | select | required |
| Statut contrat | select | required (active/terminated) |

**Option "Créer un compte utilisateur"** :
- Checkbox qui affiche des champs supplémentaires : email (pour le login), mot de passe, rôle
- Crée un `User` et lie le `user_id` au `StaffMember`
- Permet au staff d'accéder au BO/POS

**Option "Lier à un utilisateur existant"** :
- Select dropdown des utilisateurs non encore liés

### 21.3 Page d'Édition (7 Onglets)

**URL**: `/settings/staff/{staff}/edit`

#### Onglet 1 : Profil
- Mêmes champs que le formulaire de création
- **Documents** : Zone d'upload pour les documents du salarié (contrat, pièce d'identité, etc.)
- Liste des documents uploadés avec téléchargement/suppression

#### Onglet 2 : Salaire

**Modèle**: `UserSalary`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `user_id` | foreignId | Lien vers staff_member ou user |
| `base_salary` | decimal(10,2) | Salaire de base mensuel |
| `daily_rate` | decimal(10,2) | Taux journalier |
| `overtime_rate` | decimal(10,2) | Taux heures sup |
| `effective_from` | date | Date de prise d'effet |
| `notes` | text (nullable) | |

- **Historique des salaires** : Tableau des `UserSalary` triés par `effective_from` DESC
- Le salaire actif est celui dont `effective_from` est la date la plus récente dans le passé
- **Ajout d'un nouveau salaire** : Formulaire avec base, taux journalier, taux heures sup, date d'effet

**Avances sur salaire** (`SalaryAdvance`) :

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `staff_member_id` | foreignId | |
| `amount` | decimal(10,2) | Montant de l'avance |
| `date` | date | Date de l'avance |
| `status` | enum | `pending`, `approved`, `rejected` |
| `approved_by` | foreignId (nullable) | |
| `notes` | text (nullable) | |

- Formulaire de demande d'avance
- Workflow d'approbation (pending → approved/rejected)

#### Onglet 3 : Congés

**Modèle**: `LeaveRequest`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `staff_member_id` | foreignId | |
| `type` | enum | `vacation`, `dayoff`, `sick` |
| `start_date` | date | Date début |
| `end_date` | date | Date fin |
| `status` | enum | `pending`, `approved`, `rejected` |
| `reason` | text (nullable) | Motif |
| `approved_by` | foreignId (nullable) | |

- **Quotas de congés** : Gestion du solde de jours par type (vacation, dayoff, sick)
- **Demandes** : Liste avec statut, dates, type, actions (approuver/rejeter)
- **Workflow** : pending → approved/rejected

#### Onglet 4 : Planning

**Modèle**: `StaffSchedule`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `staff_member_id` | foreignId | |
| `day_of_week` | integer | 1 (Lundi) à 7 (Dimanche) |
| `start_time` | time | Heure de début |
| `end_time` | time | Heure de fin |
| `is_off` | boolean | Jour de repos |

- Grille hebdomadaire avec les horaires par jour
- Modification inline des heures de début/fin
- Toggle jour de repos

#### Onglet 5 : Paie (Payroll)

**Vue**: `resources/views/staff/partials/tab-payroll.blade.php`

**PayrollService::calculatePayrollForUser()** retourne :

| Composant | Description |
|-----------|-------------|
| `base_salary` | Salaire de base du mois |
| `working_days` | Nombre de jours travaillés |
| `unjustified_absences` | Jours d'absence non justifiée |
| `unjustified_deduction` | `unjustified_absences × daily_rate` |
| `overtime_hours` | Heures supplémentaires |
| `overtime_total` | `overtime_hours × overtime_rate` |
| `bonus_total` | Somme des bonus approuvés du mois |
| `penalty_total` | Somme des pénalités approuvées du mois |
| `commission_total` | Total des commissions calculées |
| `advance_total` | Total des avances à déduire |
| `gross_salary` | `base + overtime + bonus + commission` |
| `total_deductions` | `unjustified + penalties + advances` |
| `net_salary` | `gross - deductions` |

**Interface** :
- Sélecteur de mois/année
- Affichage détaillé de chaque composant du calcul
- **Bouton "Payer"** : Modal de confirmation avec méthode de paiement
  - Crée un `SalaryPayment`
  - Crée une `FinancialTransaction` (DEBIT sur compte 50001 Salaries)
  - Génère un PDF de fiche de paie

**Historique des paiements** : Tableau des `SalaryPayment` passés avec téléchargement PDF

**Paiement en masse** : Possibilité de payer tous les staff d'un magasin en une fois

#### Onglet 6 : Commissions

**Vue**: `resources/views/staff/partials/tab-commissions.blade.php`

**Modèle**: `EmployeeCommission` (règles)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `staff_member_id` | foreignId | |
| `type` | enum | `percent_sales`, `fixed_amount`, `per_unit` |
| `rate` | decimal(10,4) | Taux (% pour percent_sales, $ pour fixed/per_unit) |
| `store_id` | foreignId (nullable) | Magasin ciblé (null = tous) |
| `is_active` | boolean | Règle active |
| `start_date` | date | Date de début |
| `end_date` | date (nullable) | Date de fin |

**Types de commission** :

| Type | Calcul |
|------|--------|
| `percent_sales` | `rate% × revenue_du_mois` |
| `fixed_amount` | Montant fixe par mois |
| `per_unit` | `rate × nombre_articles_vendus` |

**Modèle**: `CommissionCalculation` (calculs mensuels)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `employee_commission_id` | foreignId | Règle de commission |
| `staff_member_id` | foreignId | |
| `month` | date | Mois de calcul |
| `amount` | decimal(10,2) | Montant calculé |
| `status` | enum | `pending`, `approved`, `paid` |
| `approved_by` | foreignId (nullable) | |
| `paid_at` | timestamp (nullable) | |

**CommissionService** :
- `calculateMonthlyCommissions(StaffMember, month)` : Calcule les commissions pour chaque règle active
- `calculateStoreCommission(StaffMember, Store, month)` : Calcul par magasin
- `calculateResellerCommission(StaffMember, month)` : Calcul sur ventes revendeurs
- `approveCommissions(array $ids)` : Approuve les commissions sélectionnées
- `markCommissionsAsPaid(array $ids)` : Marque comme payées

**Interface** :
- Gestion des règles de commission : CRUD
- Tableau des calculs mensuels avec statut
- Boutons : Recalculer, Approuver, Marquer payé
- `getCommissionSummary()` : Résumé avec détail des calculs

#### Onglet 7 : Ajustements (Bonus/Pénalités)

**Modèle**: `PayrollAdjustment`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `staff_member_id` | foreignId | |
| `type` | enum | `bonus`, `penalty` |
| `amount` | decimal(10,2) | Montant |
| `reason` | text | Raison |
| `month` | date | Mois d'application |
| `status` | enum | `pending`, `approved`, `rejected` |
| `approved_by` | foreignId (nullable) | |

- Ajout de bonus ou pénalités
- Workflow d'approbation
- Les ajustements approuvés sont inclus dans le calcul de la paie du mois concerné

### 21.4 Planning Global

**URL**: `/settings/planning`

- Calendrier mensuel montrant tous les staff
- Vue par magasin
- Affichage des congés approuvés
- Suivi des absences

### 21.5 API POS Staff

Le POS expose des endpoints pour que le staff puisse :
- Consulter son planning
- Faire une demande de congé
- Voir ses heures travaillées

### Routes Staff

```
GET    /settings/staff                         → index
GET    /settings/staff/create                  → create
POST   /settings/staff                         → store
GET    /settings/staff/{staff}/edit            → edit
PUT    /settings/staff/{staff}                 → update
DELETE /settings/staff/{staff}                 → destroy

POST   /settings/staff/{staff}/salary          → updateSalary
POST   /settings/staff/{staff}/advance         → requestAdvance
POST   /settings/staff/{staff}/advance/{adv}/approve → approveAdvance

POST   /settings/staff/{staff}/leave           → requestLeave
POST   /settings/staff/{staff}/leave/{leave}/approve → approveLeave
POST   /settings/staff/{staff}/leave/{leave}/reject  → rejectLeave

PUT    /settings/staff/{staff}/schedule        → updateSchedule

GET    /settings/staff/{staff}/payroll         → payroll
POST   /settings/staff/{staff}/payroll/pay     → processPayroll
GET    /settings/staff/{staff}/payroll/{payment}/pdf → payrollPdf

POST   /settings/staff/{staff}/commission      → storeCommissionRule
PUT    /settings/staff/{staff}/commission/{rule} → updateCommissionRule
POST   /settings/staff/{staff}/commission/calculate → calculateCommissions
POST   /settings/staff/{staff}/commission/approve   → approveCommissions

POST   /settings/staff/{staff}/adjustment      → storeAdjustment
POST   /settings/staff/{staff}/adjustment/{adj}/approve → approveAdjustment

GET    /settings/planning                      → planning
GET    /settings/leave-requests                → leaveRequests
```


---

## 22. MODULE PARAMÈTRES

**URL Préfixe**: `/settings`

### 22.1 Gestion des Utilisateurs

**URL**: `/settings/users`
**Contrôleur**: `App\Http\Controllers\UserController`

**Table**: `users`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom complet |
| `email` | string (unique) | Email de connexion |
| `password` | string | Mot de passe hashé (bcrypt) |
| `pin_code` | string (nullable) | PIN à 6 chiffres pour le POS |
| `store_id` | foreignId (nullable) | Magasin d'affectation |
| `locale` | string (default 'en') | Langue préférée |
| `is_active` | boolean (default true) | Compte actif |
| `email_verified_at` | timestamp (nullable) | |
| `remember_token` | string (nullable) | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Formulaire Création/Édition** :

| Champ | Type | Validation |
|-------|------|------------|
| Nom | text | required |
| Email | email | required, unique |
| Mot de passe | password | required (création), nullable (édition) |
| PIN POS | text (6 digits) | nullable, unique |
| Magasin | select | nullable |
| Rôle | select | required (via Spatie) |
| Actif | checkbox | boolean |

### 22.2 Gestion des Rôles

**URL**: `/settings/roles`
**Contrôleur**: `App\Http\Controllers\RoleController`

Utilise **Spatie Laravel Permission**.

**CRUD de rôles** :
- Nom du rôle
- Attribution de permissions (multi-select ou checkboxes)

**Rôles prédéfinis** :

| Rôle | Description |
|------|-------------|
| `Admin` | Accès complet à tout le système |
| `Manager` | Gestion des opérations, staff, stocks |
| `Staff` | Accès limité aux opérations quotidiennes |
| `ACCOUNTING` | Accès au module comptabilité uniquement |

Les rôles contrôlent :
- L'accès aux routes (middleware `role:...`)
- L'affichage des menus (config/menu.php `allowed_roles`)
- Les actions possibles dans chaque module

### 22.3 Gestion des Magasins

**URL**: `/settings/stores`
**Contrôleur**: `App\Http\Controllers\StoreController`

**Table**: `stores`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom du magasin |
| `address` | text (nullable) | Adresse |
| `phone` | string (nullable) | Téléphone |
| `manager` | string (nullable) | Nom du manager |
| `type` | enum | `normal` (boutique) ou `warehouse` (entrepôt) |
| `is_active` | boolean (default true) | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Magasins existants** :

| ID | Nom | Type |
|----|-----|------|
| 1 | Phnom Penh | normal |
| 2 | Siem Reap | normal |
| 3 | Warehouse | warehouse |

### 22.4 Types de Variation

**URL**: `/settings/variation-types`
**Contrôleur**: `App\Http\Controllers\VariationTypeController`

Types d'attributs pour les variations de produits :

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `name` | string | Nom du type (ex: "Size", "Color") |

CRUD simple. Exemples : Size, Color, Material

### 22.5 Valeurs de Variation

**URL**: `/settings/variation-values`
**Contrôleur**: `App\Http\Controllers\VariationValueController`

Valeurs possibles pour chaque type de variation :

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint (PK) | |
| `variation_type_id` | foreignId | Type parent |
| `value` | string | Valeur (ex: "S", "M", "L", "Red", "Blue") |

CRUD simple, filtré par type de variation.

### 22.6 Sauvegardes

**URL**: `/settings/backups`
**Contrôleur**: `App\Http\Controllers\BackupController`

- **Liste des sauvegardes** existantes (fichiers .sql.gz dans storage)
- **Créer une sauvegarde** : Lance un dump de la base de données
- **Télécharger** une sauvegarde existante
- **Supprimer** une sauvegarde

### 22.7 Planning (Admin)

**URL**: `/settings/planning`

Interface d'administration du planning des employés :
- Vue calendrier mensuelle
- Gestion des horaires par magasin
- Vue consolidée de tous les magasins

### 22.8 Demandes de Congés (Admin)

**URL**: `/settings/leave-requests`

Interface centralisée d'approbation des congés :
- Liste de toutes les demandes en attente (status = pending)
- Filtres : magasin, type de congé, date
- Actions rapides : Approuver / Rejeter avec motif optionnel

### Routes Paramètres

```
GET    /settings/users                           → users.index
GET    /settings/users/create                    → users.create
POST   /settings/users                           → users.store
GET    /settings/users/{user}/edit               → users.edit
PUT    /settings/users/{user}                    → users.update
DELETE /settings/users/{user}                    → users.destroy

GET    /settings/roles                           → roles.index
GET    /settings/roles/create                    → roles.create
POST   /settings/roles                           → roles.store
GET    /settings/roles/{role}/edit               → roles.edit
PUT    /settings/roles/{role}                    → roles.update
DELETE /settings/roles/{role}                    → roles.destroy

GET    /settings/stores                          → stores.index
POST   /settings/stores                          → stores.store
PUT    /settings/stores/{store}                  → stores.update

GET    /settings/variation-types                 → variationTypes.index
POST   /settings/variation-types                 → variationTypes.store
PUT    /settings/variation-types/{type}          → variationTypes.update
DELETE /settings/variation-types/{type}          → variationTypes.destroy

GET    /settings/variation-values                → variationValues.index
POST   /settings/variation-values                → variationValues.store
PUT    /settings/variation-values/{value}        → variationValues.update
DELETE /settings/variation-values/{value}        → variationValues.destroy

GET    /settings/backups                         → backups.index
POST   /settings/backups                         → backups.create
GET    /settings/backups/{backup}/download       → backups.download
DELETE /settings/backups/{backup}                → backups.destroy
```


---

## 23. SERVICES MÉTIER

### 23.1 ExchangeService

**Fichier**: `app/Services/ExchangeService.php` (~22.5 KB)

Service central gérant toute la logique des échanges et retours clients.

#### Méthodes Principales

**`lookupSale(string $receiptNumber): ?Sale`**
- Recherche une vente par numéro de reçu
- **Vérification fenêtre de 30 jours** : Seules les ventes de moins de 30 jours sont éligibles
- Retourne la vente avec ses items, ou null si non trouvée/non éligible

**`processExchange(array $data): Exchange`**

Traitement complet de l'échange en une transaction DB :

1. **Validation** : Vérifie que la vente existe, est éligible, que les items retournés sont valides (quantités ≤ quantités d'origine)
2. **Calcul du solde** :
   - `total_returned` = somme (price × qty) des articles retournés
   - `total_new` = somme (price × qty) des nouveaux articles
   - `balance` = `total_returned - total_new`
3. **Traitement des retours** :
   - Pour chaque article retourné, restauration du stock (création d'un StockBatch)
   - Création d'un StockTransaction (type=in, reason=exchange_return)
4. **Traitement des nouveaux articles** :
   - Pour chaque nouvel article, déduction FIFO du stock
   - Création d'un StockTransaction (type=out, reason=exchange)
5. **Gestion du solde** :
   - **Positif** : Génération d'un voucher via VoucherService (validité 90 jours)
   - **Négatif** : Enregistrement du paiement additionnel
   - **Zéro** : Échange direct
6. **Recalcul du total de la vente** originale
7. **Création de l'échange** (`Exchange`) avec ses items (`ExchangeItem`)

**`decrementStockFIFO(Product $product, Store $store, int $quantity): void`**
- Récupère les `stock_batches` du produit/magasin, triés par `created_at ASC`
- **Verrouillage pessimiste** (`lockForUpdate`) pour éviter les race conditions
- Décrémente les lots un par un jusqu'à satisfaire la quantité demandée
- Supprime les lots vidés (quantity = 0)
- Lance une exception si stock insuffisant

**`createFinancialTransaction(Store $store, float $amount, string $direction, string $label): FinancialTransaction`**
- Calcule le running balance : récupère le dernier `balance_after` du compte pour ce magasin
- Crée la transaction avec `balance_before` et `balance_after` corrects

### 23.2 VoucherService

**Fichier**: `app/Services/VoucherService.php` (~6 KB)

| Méthode | Description |
|---------|-------------|
| `generateCode()` | Génère `KBA` + 9 chiffres aléatoires. Boucle jusqu'à obtenir un code unique en base. |
| `createFromExchange(Exchange, amount, storeId)` | Crée un voucher source_type=exchange, validité 90 jours |
| `createManual(amount, storeId, validityPeriod, userId)` | Crée un voucher source_type=manual, validité configurable |
| `validate(code)` | Retourne `{valid: bool, error_code: ?string, voucher: ?Voucher}`. Codes d'erreur : `not_found`, `already_used`, `expired`, `cancelled` |
| `applyToSale(voucher, sale, storeId)` | Marque voucher comme `used`, enregistre sale_id et store_id |
| `cancel(voucher, userId, reason)` | Met le statut à `cancelled`, enregistre raison et user |
| `expireVouchers()` | Expire les vouchers actifs dont `expires_at < now()`. Retourne le count. |
| `getStatistics()` | Retourne counts et montants agrégés par statut |

### 23.3 PayrollService

**Fichier**: `app/Services/PayrollService.php`

**`calculatePayrollForUser(StaffMember $staff, string $month): array`**

Retourne le breakdown complet de la paie :

```php
[
    'base_salary' => decimal,           // Salaire de base (UserSalary actif)
    'daily_rate' => decimal,            // Taux journalier
    'working_days' => int,              // Jours ouvrés du mois
    'unjustified_days' => int,          // calculateUnjustifiedDays()
    'unjustified_deduction' => decimal, // unjustified_days × daily_rate
    'overtime_total' => decimal,        // getOvertimeTotal()
    'bonus_total' => decimal,           // getBonusTotal() (ajustements approuvés type=bonus)
    'penalty_total' => decimal,         // getPenaltyTotal() (ajustements approuvés type=penalty)
    'commission_total' => decimal,      // getCommissionTotal() (commissions approved du mois)
    'advance_total' => decimal,         // Avances approuvées non encore déduites
    'gross_salary' => decimal,          // base + overtime + bonus + commission
    'total_deductions' => decimal,      // unjustified + penalties + advances
    'net_salary' => decimal,            // gross - deductions
]
```

Méthodes auxiliaires :
- `calculateUnjustifiedDays(staff, month)` : Compare le planning vs les présences réelles
- `getAdjustmentTotals(staff, month)` : Agrège les ajustements approuvés du mois
- `getOvertimeTotal(staff, month)` : Calcule les heures sup depuis les shifts POS
- `getBonusTotal(staff, month)` : Somme des bonus approuvés
- `getPenaltyTotal(staff, month)` : Somme des pénalités approuvées
- `getCommissionTotal(staff, month)` : Somme des commissions approuvées

### 23.4 CommissionService

**Fichier**: `app/Services/CommissionService.php`

| Méthode | Description |
|---------|-------------|
| `calculateMonthlyCommissions(StaffMember, month)` | Pour chaque `EmployeeCommission` active du staff, calcule le montant selon le type (percent_sales/fixed_amount/per_unit) et crée un `CommissionCalculation` |
| `calculateStoreCommission(StaffMember, Store, month)` | Calcule les ventes du staff dans un magasin donné pour le mois |
| `calculateResellerCommission(StaffMember, month)` | Calcule la commission sur les ventes revendeurs |
| `approveCommissions(array $ids)` | Passe les CommissionCalculation sélectionnées à status `approved` |
| `markCommissionsAsPaid(array $ids)` | Passe à status `paid` avec `paid_at = now()` |
| `getCommissionSummary(StaffMember, month)` | Retourne un résumé détaillé avec le détail de chaque calcul |

### 23.5 SpecialOrderService

**Fichier**: `app/Services/SpecialOrderService.php`

| Méthode | Description |
|---------|-------------|
| `handleDirectPayment(WebsiteOrder $order)` | Pour les paiements cash/bank_transfer : crée une `FinancialTransaction` CREDIT sur compte 701, met payment_status à `paid` |
| `handleOrderPaid(string $token)` | Callback PayWay : trouve la commande par token, met à jour le statut de paiement, crée la FinancialTransaction, envoie email de confirmation au client, envoie notification Telegram |

### 23.6 TelegramService

**Fichier**: `app/Services/TelegramService.php` (~1.4 KB)

| Méthode | Description |
|---------|-------------|
| `sendMessage(string $message)` | Envoie un message HTML formaté via l'API Telegram Bot (`sendMessage` endpoint). Utilise `TELEGRAM_BOT_TOKEN` et `TELEGRAM_CHAT_ID` depuis .env. |

Utilisé pour les notifications :
- Ouverture/fermeture de shift POS
- Paiement reçu sur commande spéciale
- Alertes critiques


---

## 24. ARCHITECTURE TECHNIQUE

### 24.1 Routes

**Fichier principal** : `routes/web.php` (600+ routes)
**Fichier API** : `routes/api.php`
**Fichier console** : `routes/console.php`

Les routes sont organisées par module, avec des préfixes de groupe et des middlewares appropriés :

```php
// Structure générale des groupes de routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Products
    Route::resource('products', ProductController::class);

    // Financial (par magasin)
    Route::prefix('financial/{store}')->group(function () {
        // ...
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::resource('staff', StaffController::class);
        Route::resource('users', UserController::class);
        // ...
    });

    // Reception (middleware custom)
    Route::prefix('reception')->middleware('reception.auth')->group(function () {
        // ...
    });
});

// API POS (middleware sanctum)
Route::prefix('api/pos')->middleware('auth:sanctum')->group(function () {
    // ...
});
```

### 24.2 Commandes Artisan

**Fichier** : `routes/console.php` + `app/Console/Commands/`

| Commande | Description | Planification |
|----------|-------------|---------------|
| `vouchers:expire` | Expire les vouchers actifs dont la date est passée | Quotidien (cron) |
| `fix:sale-totals` | Corrige les totaux de ventes incohérents | Manuel |
| `fix:variation-attributes` | Corrige les attributs de variation | Manuel |
| `import:cambodia-post-rates` | Importe les tarifs Cambodia Post | Manuel |
| `sync:cambodia-post-rates` | Synchronise les tarifs Cambodia Post | Manuel |
| `merge:variation-groups` | Fusionne des groupes de variation | Manuel |

### 24.3 Middlewares

| Middleware | Fichier | Description |
|------------|---------|-------------|
| `SetUserLocale` | `app/Http/Middleware/SetUserLocale.php` | Définit la locale de l'application selon la préférence de l'utilisateur (`users.locale`) |
| `ReceptionAuth` | `app/Http/Middleware/ReceptionAuth.php` | Vérifie l'authentification par PIN pour la PWA Réception. Stocke le PIN en session. |
| `auth` | Laravel standard | Authentification BO standard |
| `auth:sanctum` | Laravel Sanctum | Authentification API (POS) |
| `role:...` | Spatie Permission | Restriction par rôle |

### 24.4 Exports (Maatwebsite Excel)

| Export | Fichier | Description |
|--------|---------|-------------|
| `SupplierSalesExport` | `app/Exports/SupplierSalesExport.php` | Export des ventes par fournisseur |
| `InventoryExport` | Via InventoryController | Export du template d'inventaire (feuille protégée) |
| `TransactionExport` | Via FinancialTransactionController | Export des transactions financières |
| `InvoiceExport` | Via GeneralInvoiceController | Export des factures |
| `VoucherExport` | Via VoucherController | Export CSV des vouchers |

### 24.5 Structure des Traductions

**Fichiers** : `resources/lang/{en,fr}/messages.php`

Structure du fichier de traductions (tableau PHP associatif) :

```php
return [
    // Sections du menu
    'menu' => [
        'dashboard' => '...',
        'products' => '...',
        'website_orders' => '...',
        // ...
    ],

    // Éléments communs
    'common' => [
        'save' => '...',
        'cancel' => '...',
        'delete' => '...',
        'edit' => '...',
        'create' => '...',
        'search' => '...',
        'filter' => '...',
        'export' => '...',
        'actions' => '...',
        'status' => '...',
        'yes' => '...',
        'no' => '...',
        // ...
    ],

    // Sections par module
    'product' => [ /* ... */ ],
    'supplier' => [ /* ... */ ],
    'stock_loss' => [ /* ... */ ],
    'reseller' => [ /* ... */ ],
    'financial' => [ /* ... */ ],
    'pos' => [ /* ... */ ],
    'reception' => [ /* ... */ ],
    'factory' => [ /* ... */ ],
    'bi' => [ /* ... */ ],
    'staff' => [ /* ... */ ],       // ~ligne 2985 (fr) / 3001 (en)
    'popup_event' => [ /* ... */ ],
    'voucher' => [ /* ... */ ],
    'website_order' => [ /* ... */ ],
    'special_order' => [ /* ... */ ],
    'shipping' => [ /* ... */ ],
    'main_dashboard' => [ /* ... */ ],
    // ...
];
```

Chaque module a sa section dédiée avec toutes les clés nécessaires pour l'interface : labels, messages, statuts, boutons, etc.

### 24.6 Configuration du Menu

**Fichier** : `config/menu.php`

Structure d'un élément de menu :

```php
[
    'label' => 'messages.menu.products',   // Clé de traduction
    'icon' => 'bi-box',                     // Icône Bootstrap Icons
    'route' => 'products.index',            // Nom de route Laravel
    'active_pattern' => 'products*',        // Pattern pour le surlignage actif
    'allowed_roles' => ['Admin', 'Manager'], // Rôles autorisés (optionnel)
    'submenu' => [                          // Sous-menus (optionnel)
        [
            'label' => '...',
            'route' => '...',
        ],
    ],
    'dynamic_submenu' => function() {       // Sous-menu dynamique (optionnel)
        return Store::all()->map(fn($s) => [...]);
    },
]
```

### 24.7 Gestion des Fichiers et Stockage

| Type | Disque | Chemin | Description |
|------|--------|--------|-------------|
| Photos produits | public | `storage/app/public/products/` | Images produits |
| Photos gift boxes | public | `storage/app/public/gift-boxes/` | Images coffrets |
| Factures fournisseur | local | `storage/app/invoices/` | Fichiers uploadés |
| Justificatifs paiement | local | `storage/app/payments/` | Preuves de paiement |
| Documents staff | local | `storage/app/staff-documents/` | Documents RH |
| Sauvegardes | local | `storage/app/backups/` | Dumps DB |
| PDFs générés | local | `storage/app/pdfs/` | Factures, bons de livraison |

### 24.8 File d'Attente (Queue)

Driver : `database` (table `jobs`)

Utilisée pour :
- Envoi d'emails (notifications, factures, liens de paiement)
- Notifications Telegram
- Exports Excel volumineux
- Indexation Meilisearch

### 24.9 Cache

Driver : `database` (table `cache`)

Utilisé pour :
- Cache des catégories (invalidé sur modification)
- Cache des résultats BI (TTL par période)
- Cache Meilisearch
- Cache des taux de change (si applicable)

### 24.10 Meilisearch

**Configuration** :
- Host : `http://127.0.0.1:7700`
- Driver Scout : `meilisearch`

**Modèles indexés** :
- `Product` : Recherche dans le BO, le POS et l'API fournisseur
- Searchable attributes : `name`, `ean`, `description`, `brand_name`

**Commande de réindexation** :
```bash
php artisan scout:import "App\Models\Product"
```

### 24.11 Diagramme des Relations Principales

```
User ──── StaffMember ──── UserSalary
  │            │              SalaryAdvance
  │            │              LeaveRequest
  │            │              StaffSchedule
  │            │              EmployeeCommission ── CommissionCalculation
  │            │              PayrollAdjustment
  │            │              SalaryPayment
  │
  ├── Sale ── SaleItem ── Product
  │    │                    │
  │    │                    ├── Brand
  │    │                    ├── Category (m2m)
  │    │                    ├── ProductImage
  │    │                    ├── ProductBarcode
  │    │                    ├── StockBatch ── Store
  │    │                    ├── Supplier (m2m + purchase_price)
  │    │                    ├── VariationGroup
  │    │                    └── Recipe ── RecipeItem ── RawMaterial
  │    │
  │    └── Shift ── ShiftUser
  │           └── PopupEvent ── PopupEventItem
  │
  ├── Exchange ── ExchangeItem
  │    └── Voucher
  │
  ├── WebsiteOrder ── WebsiteOrderItem
  │                    WebsitePaymentTransaction
  │
  ├── Supplier ── SupplierOrder ── SupplierOrderItem
  │    │           SupplierReturn
  │    │           Refill
  │    └── SupplierContact
  │
  ├── Reseller ── ResellerStockDelivery
  │    │           ResellerSalesReport
  │    │           ResellerInvoice
  │    └── ResellerContact
  │
  ├── FinancialAccount ── FinancialTransaction
  │                        FinancialJournal
  │                        GeneralInvoice
  │
  ├── GiftBox ── GiftBoxImage
  │               GiftBoxItem
  │
  ├── GiftCard ── GiftCardCode
  │
  ├── StockMovement ── StockMovementItem
  │
  ├── StockLoss ── StockLossItem
  │
  └── Production ── ProductionConsumption
```

---

*Fin de la documentation exhaustive du Back Office Kabas.*
*Ce document contient toutes les informations nécessaires pour reproduire le système dans son intégralité.*

