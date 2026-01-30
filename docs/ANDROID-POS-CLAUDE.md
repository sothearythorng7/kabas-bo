# Kabas POS - Application Android Native

> Documentation complète pour recréer le POS web en application Android native.
> Ce fichier contient toutes les spécifications nécessaires pour Claude Code.

---

## 1. OBJECTIF DU PROJET

Créer une application Android native qui reproduit **exactement** le comportement et l'interface du POS web actuel de Kabas Concept Store. L'application doit être :

- **Offline-first** : Fonctionne sans connexion internet
- **Synchronisation automatique** : Sync des ventes toutes les 30 secondes
- **Multi-utilisateurs** : Plusieurs caissiers peuvent utiliser le même shift
- **Impression thermique** : Support des imprimantes ESC/POS

---

## 2. STACK TECHNIQUE RECOMMANDÉE (Android)

```
Langage: Kotlin
Min SDK: 26 (Android 8.0)
Target SDK: 34 (Android 14)

Architecture: MVVM + Clean Architecture
UI: Jetpack Compose (ou XML avec ViewBinding)
Navigation: Navigation Component
DI: Hilt (Dagger)

Base de données locale: Room (SQLite)
Réseau: Retrofit2 + OkHttp + Moshi/Gson
Async: Coroutines + Flow
Background: WorkManager (pour sync périodique)

Stockage clé-valeur: DataStore (remplace SharedPreferences)
Images: Coil ou Glide
Impression: ESC/POS via USB/Bluetooth
Scanner: CameraX + ML Kit Barcode
```

---

## 3. ARCHITECTURE DE L'APPLICATION

### 3.1 Structure des packages

```
com.kabas.pos/
├── data/
│   ├── local/
│   │   ├── db/
│   │   │   ├── PosDatabase.kt
│   │   │   ├── dao/
│   │   │   │   ├── UserDao.kt
│   │   │   │   ├── ProductDao.kt
│   │   │   │   ├── SaleDao.kt
│   │   │   │   ├── ShiftDao.kt
│   │   │   │   └── PaymentMethodDao.kt
│   │   │   └── entity/
│   │   │       ├── UserEntity.kt
│   │   │       ├── ProductEntity.kt
│   │   │       ├── SaleEntity.kt
│   │   │       ├── SaleItemEntity.kt
│   │   │       ├── ShiftEntity.kt
│   │   │       └── PaymentMethodEntity.kt
│   │   └── datastore/
│   │       └── SessionDataStore.kt
│   ├── remote/
│   │   ├── api/
│   │   │   └── PosApi.kt
│   │   ├── dto/
│   │   │   ├── CatalogResponse.kt
│   │   │   ├── ShiftRequest.kt
│   │   │   ├── SaleSyncRequest.kt
│   │   │   └── ...
│   │   └── interceptor/
│   │       └── AuthInterceptor.kt
│   └── repository/
│       ├── UserRepository.kt
│       ├── CatalogRepository.kt
│       ├── SaleRepository.kt
│       └── ShiftRepository.kt
├── domain/
│   ├── model/
│   │   ├── User.kt
│   │   ├── Product.kt
│   │   ├── Sale.kt
│   │   ├── SaleItem.kt
│   │   ├── Shift.kt
│   │   ├── Discount.kt
│   │   └── Voucher.kt
│   └── usecase/
│       ├── auth/
│       │   └── VerifyPinUseCase.kt
│       ├── shift/
│       │   ├── StartShiftUseCase.kt
│       │   ├── EndShiftUseCase.kt
│       │   └── ChangeUserUseCase.kt
│       ├── sale/
│       │   ├── CreateSaleUseCase.kt
│       │   ├── AddItemToSaleUseCase.kt
│       │   ├── ApplyDiscountUseCase.kt
│       │   └── ValidateSaleUseCase.kt
│       ├── sync/
│       │   └── SyncSalesUseCase.kt
│       └── exchange/
│           └── ProcessExchangeUseCase.kt
├── presentation/
│   ├── login/
│   │   ├── LoginScreen.kt
│   │   └── LoginViewModel.kt
│   ├── shiftstart/
│   │   ├── ShiftStartScreen.kt
│   │   └── ShiftStartViewModel.kt
│   ├── dashboard/
│   │   ├── DashboardScreen.kt
│   │   ├── DashboardViewModel.kt
│   │   ├── components/
│   │   │   ├── SalesTabs.kt
│   │   │   ├── SaleItemsList.kt
│   │   │   ├── ProductGrid.kt
│   │   │   ├── CategorySelector.kt
│   │   │   ├── SearchBar.kt
│   │   │   ├── NumericKeypad.kt
│   │   │   └── PaymentSelector.kt
│   │   └── dialogs/
│   │       ├── QuantityDialog.kt
│   │       ├── DiscountDialog.kt
│   │       └── PaymentDialog.kt
│   ├── shiftend/
│   │   ├── ShiftEndScreen.kt
│   │   └── ShiftEndViewModel.kt
│   ├── journal/
│   │   ├── JournalScreen.kt
│   │   └── JournalViewModel.kt
│   ├── saledetail/
│   │   ├── SaleDetailScreen.kt
│   │   └── SaleDetailViewModel.kt
│   └── common/
│       ├── theme/
│       └── components/
├── service/
│   ├── SyncWorker.kt
│   ├── PrinterService.kt
│   └── BarcodeScanner.kt
├── di/
│   ├── AppModule.kt
│   ├── DatabaseModule.kt
│   └── NetworkModule.kt
└── util/
    ├── GiftCardCodeGenerator.kt
    ├── DateUtils.kt
    └── Extensions.kt
```

---

## 4. BASE DE DONNÉES LOCALE (Room)

### 4.1 Schéma des entités

```kotlin
// UserEntity.kt
@Entity(tableName = "users")
data class UserEntity(
    @PrimaryKey val id: Int,
    val name: String,
    val pinCode: String,      // PIN à 6 chiffres
    val storeId: Int
)

// ProductEntity.kt
@Entity(tableName = "products")
data class ProductEntity(
    @PrimaryKey val id: Int,
    val storeId: Int,
    val type: String,         // "product", "gift_box", "gift_card"
    val ean: String?,
    val barcodes: String?,    // JSON array des codes-barres additionnels
    val name: String,         // JSON {"en": "...", "fr": "..."}
    val description: String?, // JSON
    val price: Double,
    val priceBtob: Double?,
    val brand: String?,       // JSON
    val categories: String?,  // JSON array
    val images: String?,      // JSON array des URLs
    val totalStock: Int
)

// ShiftEntity.kt
@Entity(tableName = "shifts")
data class ShiftEntity(
    @PrimaryKey(autoGenerate = true) val localId: Long = 0,
    val serverId: Int?,       // ID serveur (null si pas encore synchro)
    val userId: Int,
    val storeId: Int,
    val openingCash: Double,
    val closingCash: Double?,
    val visitorsCount: Int?,
    val cashDifference: Double?,
    val cashIn: Double = 0.0,
    val cashOut: Double = 0.0,
    val startedAt: String,    // ISO 8601
    val endedAt: String?,
    val synced: Boolean = false
)

// SaleEntity.kt
@Entity(tableName = "sales")
data class SaleEntity(
    @PrimaryKey val localId: String,  // Généré: timestamp + random
    val serverId: Int?,
    val shiftId: Long,        // Référence locale du shift
    val label: String?,       // "Sale #1", "Sale #2"
    val paymentType: String?, // "cash", "card", etc.
    val splitPayments: String?, // JSON array
    val discounts: String?,   // JSON array des remises globales
    val total: Double,
    val createdAt: String,
    val synced: Boolean = false,
    val validated: Boolean = false
)

// SaleItemEntity.kt
@Entity(
    tableName = "sale_items",
    foreignKeys = [ForeignKey(
        entity = SaleEntity::class,
        parentColumns = ["localId"],
        childColumns = ["saleLocalId"],
        onDelete = ForeignKey.CASCADE
    )]
)
data class SaleItemEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val saleLocalId: String,
    val productId: Int?,
    val originalId: Int?,     // Pour gift_box/gift_card
    val type: String,         // "product", "gift_box", "gift_card"
    val name: String,
    val ean: String?,
    val price: Double,
    val quantity: Int,
    val discounts: String?,   // JSON array
    val isDelivery: Boolean = false,
    val deliveryAddress: String?,
    val isCustomService: Boolean = false,
    val customServiceDescription: String?,
    val generatedCode: String? // Code carte cadeau généré
)

// PaymentMethodEntity.kt
@Entity(tableName = "payment_methods")
data class PaymentMethodEntity(
    @PrimaryKey val id: Int,
    val code: String,         // "cash", "card", "aba", etc.
    val name: String
)
```

### 4.2 DAOs

```kotlin
@Dao
interface UserDao {
    @Query("SELECT * FROM users")
    fun getAll(): Flow<List<UserEntity>>

    @Query("SELECT * FROM users WHERE pinCode = :pin LIMIT 1")
    suspend fun findByPin(pin: String): UserEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(users: List<UserEntity>)

    @Query("DELETE FROM users")
    suspend fun deleteAll()
}

@Dao
interface ProductDao {
    @Query("SELECT * FROM products WHERE storeId = :storeId")
    fun getByStore(storeId: Int): Flow<List<ProductEntity>>

    @Query("SELECT * FROM products WHERE storeId = :storeId AND (ean = :query OR barcodes LIKE '%' || :query || '%')")
    suspend fun findByBarcode(storeId: Int, query: String): ProductEntity?

    @Query("SELECT * FROM products WHERE storeId = :storeId AND name LIKE '%' || :query || '%'")
    suspend fun searchByName(storeId: Int, query: String): List<ProductEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(products: List<ProductEntity>)

    @Query("DELETE FROM products WHERE storeId = :storeId")
    suspend fun deleteByStore(storeId: Int)
}

@Dao
interface SaleDao {
    @Query("SELECT * FROM sales WHERE shiftId = :shiftId AND validated = 0")
    fun getPendingSales(shiftId: Long): Flow<List<SaleEntity>>

    @Query("SELECT * FROM sales WHERE shiftId = :shiftId AND validated = 1 AND synced = 0")
    suspend fun getUnsyncedSales(shiftId: Long): List<SaleEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(sale: SaleEntity)

    @Update
    suspend fun update(sale: SaleEntity)

    @Query("UPDATE sales SET synced = 1 WHERE localId IN (:localIds)")
    suspend fun markAsSynced(localIds: List<String>)

    @Transaction
    @Query("SELECT * FROM sales WHERE localId = :localId")
    suspend fun getSaleWithItems(localId: String): SaleWithItems?
}
```

---

## 5. API BACKEND

### 5.1 Base URL
```
Production: https://bo.kabasconceptstore.com/api/pos
```

### 5.2 Endpoints

```kotlin
interface PosApi {

    // ===== Utilisateurs =====
    @GET("users")
    suspend fun getUsers(): List<UserDto>

    // ===== Catalogue =====
    @GET("catalog/{storeId}")
    suspend fun getCatalog(@Path("storeId") storeId: Int): CatalogResponse

    @GET("search/{storeId}")
    suspend fun searchProducts(
        @Path("storeId") storeId: Int,
        @Query("q") query: String,
        @Query("limit") limit: Int = 20
    ): SearchResponse

    // ===== Shifts =====
    @GET("shifts/current/{userId}")
    suspend fun getCurrentShift(@Path("userId") userId: Int): ShiftDto?

    @POST("shifts/start")
    suspend fun startShift(@Body request: StartShiftRequest): ShiftDto

    @POST("shifts/end")
    suspend fun endShift(@Body request: EndShiftRequest): ShiftDto

    @POST("shifts/change-user")
    suspend fun changeUser(@Body request: ChangeUserRequest): ChangeUserResponse

    @GET("shifts/expected-cash/{userId}")
    suspend fun getExpectedCash(@Path("userId") userId: Int): ExpectedCashResponse

    @POST("shifts/sales-by-date")
    suspend fun getSalesByDate(@Body request: SalesByDateRequest): SalesByDateResponse

    // ===== Ventes =====
    @POST("sales/sync")
    suspend fun syncSales(@Body request: SaleSyncRequest): SaleSyncResponse

    // ===== Échanges =====
    @GET("exchange/lookup-sale")
    suspend fun lookupSale(@Query("sale_id") saleId: Int): LookupSaleResponse

    @POST("exchange/process")
    suspend fun processExchange(@Body request: ExchangeRequest): ExchangeResponse

    // ===== Vouchers =====
    @GET("voucher/validate")
    suspend fun validateVoucher(@Query("code") code: String): VoucherValidationResponse

    @POST("voucher/apply")
    suspend fun applyVoucher(@Body request: ApplyVoucherRequest): ApplyVoucherResponse
}
```

### 5.3 DTOs (Data Transfer Objects)

```kotlin
// Réponse du catalogue
data class CatalogResponse(
    val store: StoreDto,
    val products: List<ProductDto>,
    val categoryTree: List<CategoryDto>,
    val paymentsMethod: List<PaymentMethodDto>
)

data class ProductDto(
    val id: Int,
    val type: String,
    val ean: String?,
    val barcodes: List<String>?,
    val name: JsonElement,    // {"en": "...", "fr": "..."}
    val price: Double,
    val brand: JsonElement?,
    val categories: List<JsonElement>?,
    val photos: List<PhotoDto>?,
    @SerializedName("total_stock") val totalStock: Int
)

// Requête de sync des ventes
data class SaleSyncRequest(
    @SerializedName("shift_id") val shiftId: Int,
    val sales: List<SaleSyncDto>
)

data class SaleSyncDto(
    val id: String,           // ID local
    val label: String?,
    @SerializedName("payment_type") val paymentType: String?,
    @SerializedName("split_payments") val splitPayments: List<SplitPaymentDto>?,
    val items: List<SaleItemDto>,
    val discounts: List<DiscountDto>?,
    val total: Double
)

data class SaleItemDto(
    @SerializedName("product_id") val productId: Int?,
    @SerializedName("original_id") val originalId: Int?,
    val type: String,
    val name: String?,
    val ean: String?,
    val price: Double,
    val quantity: Int,
    val discounts: List<DiscountDto>?,
    @SerializedName("is_delivery") val isDelivery: Boolean = false,
    @SerializedName("delivery_address") val deliveryAddress: String?,
    @SerializedName("is_custom_service") val isCustomService: Boolean = false,
    @SerializedName("custom_service_description") val customServiceDescription: String?,
    @SerializedName("generated_code") val generatedCode: String?
)

data class DiscountDto(
    val type: String,         // "amount" ou "percent"
    val value: Double,
    val scope: String?        // "unit" ou "line" (pour items)
)

data class SplitPaymentDto(
    @SerializedName("payment_type") val paymentType: String,
    val amount: Double,
    @SerializedName("voucher_code") val voucherCode: String?
)

// Réponse sync
data class SaleSyncResponse(
    val status: String,
    @SerializedName("synced_sales") val syncedSales: List<String>
)
```

---

## 6. ÉCRANS ET NAVIGATION

### 6.1 Flow de navigation

```
┌─────────────┐
│   SPLASH    │ (Chargement initial, sync users)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│    LOGIN    │ (Saisie PIN 6 chiffres)
└──────┬──────┘
       │ PIN valide
       ▼
   ┌───┴───┐
   │ Shift │
   │actif? │
   └───┬───┘
       │
   ┌───┴───┐
  Non    Oui
   │       │
   ▼       │
┌─────────────┐   │
│ SHIFT START │   │
│(Caisse init)│   │
└──────┬──────┘   │
       │          │
       └────┬─────┘
            ▼
     ┌─────────────┐
     │  DASHBOARD  │◄──────────────────────┐
     │  (Principal)│                        │
     └──────┬──────┘                        │
            │                               │
    ┌───────┴───────┬───────────┐          │
    ▼               ▼           ▼          │
┌────────┐   ┌─────────┐  ┌──────────┐     │
│JOURNAL │   │SALE     │  │ MENU     │     │
│(Hist.) │   │DETAIL   │  │(Options) │     │
└────────┘   └─────────┘  └────┬─────┘     │
                               │           │
                    ┌──────────┼──────┐    │
                    ▼          ▼      ▼    │
              ┌─────────┐ ┌────────┐ ┌─────┴───┐
              │CHANGE   │ │CASH IN/│ │SHIFT END│
              │USER     │ │OUT     │ └────┬────┘
              └─────────┘ └────────┘      │
                                          ▼
                                   ┌─────────────┐
                                   │    LOGIN    │
                                   └─────────────┘
```

### 6.2 Écran LOGIN

**Composants :**
- Titre "Authentication"
- Sous-titre "Enter your PIN code"
- Affichage PIN masqué (• • • • • •)
- Clavier numérique 3x4 :
  ```
  [1] [2] [3]
  [4] [5] [6]
  [7] [8] [9]
  [C] [0] [OK]
  ```
- C = Effacer tout
- OK = Valider

**Comportement :**
1. Saisie max 6 chiffres
2. Affichage avec * pour chaque chiffre saisi
3. Sur OK : vérifier PIN dans la base locale
4. Si valide : charger le catalogue si pas en cache, vérifier shift actif
5. Si shift actif → Dashboard
6. Sinon → Shift Start

**Style :**
- Boutons ronds avec ombre
- Bouton C : rouge (#f28b82)
- Bouton OK : vert (#34a853)
- Boutons numériques : gris (#e0e0e0)

### 6.3 Écran SHIFT START

**Composants :**
- Titre "Open Shift"
- Sous-titre "Enter opening cash amount"
- Champ montant avec $ prefix
- Clavier numérique avec décimale :
  ```
  [1] [2] [3]
  [4] [5] [6]
  [7] [8] [9]
  [C] [0] [.]
  [  START  ]
  ```

**Comportement :**
1. Saisie montant initial en caisse
2. Sur START : créer shift via API
3. Enregistrer localement
4. Naviguer vers Dashboard

### 6.4 Écran DASHBOARD (Principal)

**Layout :** Split view horizontal (50/50 ajustable)

**Panneau gauche (Ventes) :**
- Barre d'actions :
  - Bouton [+] : Nouvelle vente
  - Bouton [☰] : Menu latéral
  - Champ recherche produit
  - Bouton Brands : Filtre par marque
- Onglets des ventes actives (Sale #1, Sale #2, ...)
- Liste des articles de la vente sélectionnée :
  - Photo miniature
  - Nom produit
  - Prix unitaire
  - Quantité (modifiable)
  - Remises (si présentes)
  - Total ligne
  - Bouton suppression
- Footer de vente :
  - Sous-total
  - Remise globale (si présente)
  - **TOTAL** (grand)
  - Sélecteur de paiement
  - Bouton Valider

**Panneau droit (Produits) :**
- Navigation par catégories (chips horizontaux scrollables)
  - Niveau 1 : Catégories principales
  - Niveau 2 : Sous-catégories
  - Niveau 3+ : Sous-sous-catégories
- Grille de produits (3-4 colonnes selon taille écran)
  - Image produit
  - Badge prix (vert, coin supérieur droit)
  - Badge stock (bleu, sous l'image)
  - Nom produit (3 lignes max, ellipsis)

**Séparateur central :**
- Draggable pour ajuster les proportions

### 6.5 Menu latéral

**Apparaît depuis la gauche (30% largeur max)**

**Contenu :**
- Affichage "Expected Cash" : montant calculé
- Bouton Dashboard
- Bouton Close Shift (jaune)
- Bouton Journal
- Séparateur
- Bouton Logout (rouge)
- Bouton Refresh/Sync
- Bouton Change User
- Bouton Cash In (vert)
- Bouton Cash Out (rouge)

### 6.6 Écran SHIFT END

**Similaire à Shift Start mais :**
- Titre "Close Shift"
- Affichage du montant attendu
- Saisie du montant réel compté
- Calcul automatique de la différence
- Bouton CLOSE SHIFT

### 6.7 Écran JOURNAL

**Liste des ventes du shift courant ou historique :**
- Filtres par date
- Liste scrollable :
  - Numéro de vente
  - Heure
  - Total
  - Mode de paiement
  - Statut (synced/pending)
- Clic → Sale Detail

### 6.8 Écran SALE DETAIL

**Affiche le détail complet d'une vente :**
- En-tête avec numéro et date
- Liste des articles avec détails
- Remises appliquées
- Mode(s) de paiement
- Historique des échanges (si présent)
- Boutons :
  - Réimprimer ticket
  - Échange (si applicable)

---

## 7. LOGIQUE MÉTIER

### 7.1 Gestion des ventes

```kotlin
// Structure d'une vente en mémoire
data class Sale(
    val localId: String = generateLocalId(),
    var label: String = "Sale #1",
    val items: MutableList<SaleItem> = mutableListOf(),
    var discounts: MutableList<Discount> = mutableListOf(),
    var paymentType: String? = null,
    var splitPayments: List<SplitPayment>? = null,
    var total: Double = 0.0,
    var validated: Boolean = false,
    var synced: Boolean = false
)

data class SaleItem(
    val productId: Int?,
    val originalId: Int?,
    val type: String,         // "product", "gift_box", "gift_card"
    val name: String,
    val ean: String?,
    val price: Double,
    var quantity: Int,
    var discounts: MutableList<Discount> = mutableListOf(),
    val imageUrl: String?,
    var isDelivery: Boolean = false,
    var deliveryAddress: String? = null,
    var isCustomService: Boolean = false,
    var customServiceDescription: String? = null,
    var generatedCode: String? = null  // Pour gift_card
)

data class Discount(
    val type: String,         // "amount" ou "percent"
    val value: Double,
    val scope: String? = null // "unit" ou "line" pour les items
)

data class SplitPayment(
    val paymentType: String,
    val amount: Double,
    val voucherCode: String? = null
)
```

### 7.2 Calcul des totaux

```kotlin
fun calculateSaleTotal(sale: Sale): Double {
    // 1. Calculer le total de chaque ligne
    var subtotal = 0.0

    for (item in sale.items) {
        var lineTotal = item.price * item.quantity

        // Appliquer les remises sur l'article
        for (discount in item.discounts) {
            when (discount.scope) {
                "unit" -> {
                    // Remise par unité
                    when (discount.type) {
                        "amount" -> lineTotal -= discount.value * item.quantity
                        "percent" -> lineTotal -= (item.price * discount.value / 100) * item.quantity
                    }
                }
                "line" -> {
                    // Remise sur la ligne entière
                    when (discount.type) {
                        "amount" -> lineTotal -= discount.value
                        "percent" -> lineTotal -= lineTotal * discount.value / 100
                    }
                }
            }
        }

        subtotal += maxOf(0.0, lineTotal)
    }

    // 2. Appliquer les remises globales
    var globalDiscount = 0.0
    for (discount in sale.discounts) {
        when (discount.type) {
            "amount" -> globalDiscount += discount.value
            "percent" -> globalDiscount += subtotal * discount.value / 100
        }
    }

    return maxOf(0.0, subtotal - globalDiscount)
}
```

### 7.3 Génération de codes carte cadeau

```kotlin
fun generateGiftCardCode(): String {
    val chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789" // Sans 0, O, 1, I
    val segments = (1..3).map {
        (1..4).map { chars.random() }.joinToString("")
    }
    return "GIFT-${segments.joinToString("-")}"
}

// Exemple: GIFT-AB3K-XY7P-QR2N
```

### 7.4 Recherche produits (offline)

```kotlin
suspend fun searchProducts(storeId: Int, query: String): List<Product> {
    // 1. Si query est numérique, chercher par EAN/barcode d'abord
    if (query.all { it.isDigit() }) {
        val byBarcode = productDao.findByBarcode(storeId, query)
        if (byBarcode != null) {
            return listOf(byBarcode.toDomain())
        }
    }

    // 2. Sinon, recherche par nom
    return productDao.searchByName(storeId, query)
        .map { it.toDomain() }
}
```

---

## 8. SYNCHRONISATION

### 8.1 Sync Worker (toutes les 30 secondes)

```kotlin
class SyncWorker(
    context: Context,
    params: WorkerParameters,
    private val saleRepository: SaleRepository,
    private val shiftRepository: ShiftRepository
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        return try {
            val activeShift = shiftRepository.getActiveShift() ?: return Result.success()

            // 1. Récupérer les ventes non synchronisées
            val unsyncedSales = saleRepository.getUnsyncedSales(activeShift.localId)

            if (unsyncedSales.isEmpty()) {
                return Result.success()
            }

            // 2. Envoyer au serveur
            val response = saleRepository.syncSales(activeShift.serverId!!, unsyncedSales)

            // 3. Marquer comme synchronisées
            saleRepository.markAsSynced(response.syncedSales)

            Result.success()
        } catch (e: Exception) {
            Log.e("SyncWorker", "Sync failed", e)
            Result.retry()
        }
    }
}

// Configuration du worker périodique
fun setupPeriodicSync(context: Context) {
    val constraints = Constraints.Builder()
        .setRequiredNetworkType(NetworkType.CONNECTED)
        .build()

    val syncRequest = PeriodicWorkRequestBuilder<SyncWorker>(
        30, TimeUnit.SECONDS,
        15, TimeUnit.SECONDS  // Flex interval
    )
        .setConstraints(constraints)
        .build()

    WorkManager.getInstance(context)
        .enqueueUniquePeriodicWork(
            "pos_sync",
            ExistingPeriodicWorkPolicy.KEEP,
            syncRequest
        )
}
```

### 8.2 Gestion offline

```kotlin
class SaleRepository(
    private val saleDao: SaleDao,
    private val posApi: PosApi
) {
    // Sauvegarder localement d'abord
    suspend fun saveSale(sale: Sale) {
        saleDao.insert(sale.toEntity())
    }

    // Valider une vente (prête à être synchronisée)
    suspend fun validateSale(saleLocalId: String) {
        saleDao.update(
            saleDao.getSale(saleLocalId)?.copy(validated = true)
                ?: throw IllegalStateException("Sale not found")
        )
    }

    // Sync vers le serveur
    suspend fun syncSales(shiftServerId: Int, sales: List<Sale>): SaleSyncResponse {
        val request = SaleSyncRequest(
            shiftId = shiftServerId,
            sales = sales.map { it.toSyncDto() }
        )
        return posApi.syncSales(request)
    }
}
```

---

## 9. IMPRESSION THERMIQUE

### 9.1 Format du ticket

```
================================
#65 STREET 178, Phnom Penh 12302
Phone number: 015 656 122
================================

Item 1 x2        $10.00   $20.00
  Discount: -$2.00
Item 2 x1        $15.00   $15.00
--------------------------------
Subtotal:                 $33.00
Discount (10%):           -$3.30
--------------------------------
TOTAL:                    $29.70
================================
Payment: Cash
Ticket: PP-20260130-001
Date: 2026-01-30 14:35
================================
   Thank you for your visit!
================================
```

### 9.2 Service d'impression

```kotlin
interface PrinterService {
    suspend fun printReceipt(sale: Sale, shift: Shift, store: Store): Boolean
    suspend fun printVoucher(voucher: Voucher, exchange: Exchange?): Boolean
    suspend fun openCashDrawer(): Boolean
    fun isConnected(): Boolean
}

// Implémentation ESC/POS
class EscPosPrinterService : PrinterService {
    // ... implémentation selon le type de connexion (USB, Bluetooth, réseau)
}
```

---

## 10. SCANNER DE CODES-BARRES

### 10.1 Intégration CameraX + ML Kit

```kotlin
class BarcodeScannerService(private val context: Context) {

    private val scanner = BarcodeScanning.getClient(
        BarcodeScannerOptions.Builder()
            .setBarcodeFormats(
                Barcode.FORMAT_EAN_13,
                Barcode.FORMAT_EAN_8,
                Barcode.FORMAT_UPC_A,
                Barcode.FORMAT_UPC_E,
                Barcode.FORMAT_CODE_128,
                Barcode.FORMAT_QR_CODE
            )
            .build()
    )

    fun processImage(imageProxy: ImageProxy, onBarcodeDetected: (String) -> Unit) {
        // ... logique ML Kit
    }
}
```

### 10.2 Scanner global (écoute clavier physique)

Pour les scanners qui émulent un clavier :

```kotlin
class KeyboardScannerHandler {
    private var buffer = StringBuilder()
    private var lastKeyTime = 0L
    private val SCAN_THRESHOLD = 50L // ms entre caractères

    fun onKeyEvent(event: KeyEvent): Boolean {
        val now = System.currentTimeMillis()

        // Reset si trop de temps entre les touches
        if (now - lastKeyTime > SCAN_THRESHOLD && buffer.isNotEmpty()) {
            buffer.clear()
        }
        lastKeyTime = now

        // Accumuler les caractères
        if (event.action == KeyEvent.ACTION_DOWN) {
            when {
                event.keyCode == KeyEvent.KEYCODE_ENTER -> {
                    if (buffer.length >= 3) {
                        onBarcodeScanned(buffer.toString())
                        buffer.clear()
                        return true
                    }
                }
                event.unicodeChar > 0 -> {
                    buffer.append(event.unicodeChar.toChar())
                }
            }
        }
        return false
    }
}
```

---

## 11. GESTION DES ÉTATS

### 11.1 Session utilisateur

```kotlin
class SessionManager(
    private val dataStore: DataStore<Preferences>
) {
    companion object {
        val USER_ID = intPreferencesKey("user_id")
        val STORE_ID = intPreferencesKey("store_id")
        val SHIFT_ID = longPreferencesKey("shift_id")
        val LAST_ACTIVE = longPreferencesKey("last_active")
    }

    val currentUser: Flow<User?> = dataStore.data.map { prefs ->
        prefs[USER_ID]?.let { userId ->
            // Charger depuis Room
        }
    }

    // Timeout d'inactivité (5 minutes)
    val isLocked: Flow<Boolean> = dataStore.data.map { prefs ->
        val lastActive = prefs[LAST_ACTIVE] ?: 0L
        System.currentTimeMillis() - lastActive > 5 * 60 * 1000
    }

    suspend fun updateLastActive() {
        dataStore.edit { prefs ->
            prefs[LAST_ACTIVE] = System.currentTimeMillis()
        }
    }
}
```

### 11.2 ViewModel Dashboard

```kotlin
@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val catalogRepository: CatalogRepository,
    private val saleRepository: SaleRepository,
    private val shiftRepository: ShiftRepository,
    private val sessionManager: SessionManager
) : ViewModel() {

    // État des ventes actives
    private val _sales = MutableStateFlow<List<Sale>>(emptyList())
    val sales: StateFlow<List<Sale>> = _sales.asStateFlow()

    // Vente sélectionnée
    private val _activeSale = MutableStateFlow<Sale?>(null)
    val activeSale: StateFlow<Sale?> = _activeSale.asStateFlow()

    // Produits filtrés
    private val _products = MutableStateFlow<List<Product>>(emptyList())
    val products: StateFlow<List<Product>> = _products.asStateFlow()

    // Catégories
    private val _categoryPath = MutableStateFlow<List<Category>>(emptyList())
    val categoryPath: StateFlow<List<Category>> = _categoryPath.asStateFlow()

    // Ajouter un produit à la vente active
    fun addProductToSale(product: Product, quantity: Int) {
        val currentSale = _activeSale.value ?: createNewSale()

        // Vérifier si le produit existe déjà
        val existingItem = currentSale.items.find {
            it.productId == product.id && it.type == product.type
        }

        if (existingItem != null) {
            existingItem.quantity += quantity
        } else {
            currentSale.items.add(SaleItem(
                productId = if (product.type == "product") product.id else null,
                originalId = if (product.type != "product") product.id else null,
                type = product.type,
                name = product.getLocalizedName(),
                ean = product.ean,
                price = product.price,
                quantity = quantity,
                imageUrl = product.images.firstOrNull(),
                generatedCode = if (product.type == "gift_card") generateGiftCardCode() else null
            ))
        }

        // Recalculer le total
        currentSale.total = calculateSaleTotal(currentSale)

        // Sauvegarder
        viewModelScope.launch {
            saleRepository.saveSale(currentSale)
        }

        _activeSale.value = currentSale
    }

    // Valider et préparer pour sync
    fun validateSale(paymentType: String, splitPayments: List<SplitPayment>?) {
        val sale = _activeSale.value ?: return

        sale.paymentType = paymentType
        sale.splitPayments = splitPayments
        sale.validated = true

        viewModelScope.launch {
            saleRepository.validateSale(sale.localId)

            // Imprimer le ticket
            printerService.printReceipt(sale, currentShift, currentStore)

            // Créer une nouvelle vente
            _activeSale.value = createNewSale()
        }
    }
}
```

---

## 12. THÈME ET STYLES

### 12.1 Couleurs

```kotlin
object PosColors {
    // Primaires
    val Primary = Color(0xFF0d6efd)       // Bleu Bootstrap
    val PrimaryDark = Color(0xFF0a58ca)

    // Succès/Erreur
    val Success = Color(0xFF198754)       // Vert
    val Danger = Color(0xFFdc3545)        // Rouge
    val Warning = Color(0xFFffc107)       // Jaune

    // UI
    val Background = Color(0xFFF8F9FA)
    val Surface = Color(0xFFFFFFFF)
    val Border = Color(0xFFDEE2E6)

    // Texte
    val TextPrimary = Color(0xFF212529)
    val TextSecondary = Color(0xFF6C757D)

    // Badges
    val PriceBadge = Color(0xFF198754)    // Vert
    val StockBadge = Color(0xFF0d6efd)    // Bleu

    // Boutons PIN
    val PinButton = Color(0xFFE0E0E0)
    val PinClear = Color(0xFFF28B82)
    val PinEnter = Color(0xFF34A853)
}
```

### 12.2 Dimensions

```kotlin
object PosDimens {
    // Boutons
    val PinButtonSize = 60.dp
    val ActionButtonSize = 40.dp

    // Cards
    val ProductCardWidth = 120.dp
    val ProductCardHeight = 180.dp
    val ProductImageHeight = 120.dp

    // Espacements
    val PaddingSmall = 4.dp
    val PaddingMedium = 8.dp
    val PaddingLarge = 16.dp

    // Textes
    val TextSmall = 12.sp
    val TextMedium = 14.sp
    val TextLarge = 18.sp
    val TextXLarge = 24.sp
}
```

---

## 13. FONCTIONNALITÉS DÉTAILLÉES

### 13.1 Multi-ventes (Onglets)

- Maximum 10 ventes simultanées
- Chaque vente a un label auto-généré (Sale #1, #2, ...)
- Possibilité de passer d'une vente à l'autre
- Une vente validée disparaît des onglets

### 13.2 Remises

**Sur un article :**
- Par montant fixe (ex: -$5)
- Par pourcentage (ex: -10%)
- Scope: par unité ou par ligne

**Sur la vente globale :**
- Par montant fixe
- Par pourcentage

### 13.3 Split Payment

- Permet de payer avec plusieurs méthodes
- Ex: $50 en cash + $30 en carte
- Chaque portion peut utiliser un voucher

### 13.4 Échanges/Retours

1. Rechercher la vente originale par ID
2. Sélectionner les articles à retourner
3. Optionnellement ajouter de nouveaux articles
4. Calculer la balance :
   - Balance positive → Client reçoit un voucher
   - Balance négative → Client paie la différence
5. Créer l'échange via API

### 13.5 Vouchers

- Code format: XXX-XXX-XXX-XXX
- Validité: vérifiée via API
- Application: réduit le montant à payer
- Peut être utilisé dans un split payment

### 13.6 Cash In / Cash Out

- Cash In: Ajout d'argent en caisse (ex: fond de caisse supplémentaire)
- Cash Out: Retrait d'argent (ex: dépôt à la banque)
- Affecte le calcul du "Expected Cash"

### 13.7 Expected Cash (Caisse attendue)

```
Expected Cash = Opening Cash
              + Cash Sales
              + Cash In
              - Cash Out
```

---

## 14. TESTS

### 14.1 Tests unitaires

```kotlin
@Test
fun `calculateSaleTotal with item discounts`() {
    val sale = Sale().apply {
        items.add(SaleItem(
            productId = 1,
            type = "product",
            name = "Test",
            price = 100.0,
            quantity = 2,
            discounts = mutableListOf(
                Discount("percent", 10.0, "unit")
            )
        ))
    }

    val total = calculateSaleTotal(sale)

    // 100 * 2 = 200
    // -10% par unité = -20
    // Total = 180
    assertEquals(180.0, total, 0.01)
}
```

### 14.2 Tests d'intégration

- Sync des ventes en mode offline → online
- Création de shift et fermeture
- Recherche produits locale

---

## 15. CHECKLIST DE DÉVELOPPEMENT

### Phase 1 : Base
- [ ] Setup projet Android (Kotlin, Hilt, Room, Retrofit)
- [ ] Modèles de données et entités Room
- [ ] API client avec Retrofit
- [ ] Repository pattern

### Phase 2 : Auth & Shifts
- [ ] Écran Login avec clavier PIN
- [ ] Vérification PIN offline
- [ ] Écran Shift Start
- [ ] Écran Shift End
- [ ] Gestion session utilisateur

### Phase 3 : Catalogue
- [ ] Sync catalogue depuis API
- [ ] Stockage local Room
- [ ] Navigation par catégories
- [ ] Recherche produits
- [ ] Affichage grille produits

### Phase 4 : Ventes
- [ ] Création vente
- [ ] Ajout/suppression articles
- [ ] Modification quantités
- [ ] Calcul totaux
- [ ] Multi-onglets ventes
- [ ] Remises (item + global)

### Phase 5 : Paiement
- [ ] Sélection mode de paiement
- [ ] Split payment
- [ ] Validation vente
- [ ] Stockage local

### Phase 6 : Sync
- [ ] WorkManager pour sync périodique
- [ ] Gestion offline/online
- [ ] Marquage ventes synchronisées

### Phase 7 : Fonctionnalités avancées
- [ ] Journal des ventes
- [ ] Détail d'une vente
- [ ] Échanges/Retours
- [ ] Vouchers
- [ ] Cash In/Out
- [ ] Change User

### Phase 8 : Impression
- [ ] Service d'impression ESC/POS
- [ ] Format ticket
- [ ] Impression voucher

### Phase 9 : Scanner
- [ ] Intégration CameraX
- [ ] ML Kit Barcode
- [ ] Scanner clavier physique

### Phase 10 : Polish
- [ ] Tests unitaires
- [ ] Tests UI
- [ ] Optimisations performance
- [ ] Gestion erreurs

---

## 16. NOTES IMPORTANTES

1. **Offline-first** : Toujours sauvegarder localement d'abord, puis synchroniser
2. **IDs locaux** : Utiliser `System.currentTimeMillis() + Random` pour générer des IDs uniques
3. **Sync robuste** : Le serveur doit gérer les doublons via `pos_local_id`
4. **UX tablette** : L'interface est optimisée pour tablettes (10")
5. **Pas de login classique** : Authentification uniquement par PIN
6. **Multi-magasins** : Chaque utilisateur est lié à un `store_id`
7. **Traductions** : Les noms de produits sont en JSON `{"en": "...", "fr": "..."}`

---

*Document généré pour Claude Code - Projet Kabas POS Android*
*Dernière mise à jour: 2026-01-30*
