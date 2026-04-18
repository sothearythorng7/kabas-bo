<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'ean',
        'name',
        'description',
        'slugs',
        'seo_title',
        'meta_description',
        'price',
        'price_btob',
        'shipping_weight',
        'brand_id',
        'color',
        'size',
        'is_active',
        'is_active_pos',
        'is_best_seller',
        'is_resalable',
        'allow_overselling',
        'attributes',
        'variation_group_id',
        'gender',
        'age_group',
    ];

    protected $casts = [
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
    ];

    // Relation vers les stocks
    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    // Stock total pour un store donné
    public function getTotalStock(Store $store)
    {
        return $this->stockBatches()
            ->where('store_id', $store->id)
            ->sum('quantity');
    }

    // Stock total pour un reseller donné
    public function getResellerStock(Reseller $reseller)
    {
        return $this->stockBatches()
            ->where('reseller_id', $reseller->id)
            ->sum('quantity');
    }

    // Stock réservé par les popup events actifs pour un store
    public function getReservedQuantity(Store $store)
    {
        return PopupEventItem::whereHas('event', function ($q) use ($store) {
            $q->where('status', 'active')->where('store_id', $store->id);
        })->where('product_id', $this->id)->sum('quantity_allocated');
    }

    // Stock disponible = total - réservé events actifs
    public function getAvailableStock(Store $store)
    {
        return $this->getTotalStock($store) - $this->getReservedQuantity($store);
    }

    // Relations classiques
    public function brand()      { return $this->belongsTo(Brand::class); }
    public function categories() { return $this->belongsToMany(Category::class)->withTimestamps(); }
    public function suppliers()  {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('purchase_price')
            ->withTimestamps();
    }
    public function stores() {
        return $this->belongsToMany(Store::class)
            ->withPivot('alert_stock_quantity')
            ->withTimestamps();
    }
    public function images()     { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function primaryImage(){ return $this->hasOne(ProductImage::class)->where('is_primary', true); }

    // Relation vers les codes-barres
    public function barcodes()    { return $this->hasMany(ProductBarcode::class); }
    public function primaryBarcode() { return $this->hasOne(ProductBarcode::class)->where('is_primary', true); }

    /**
     * Ajouter un code-barre au produit
     */
    public function addBarcode(string $barcode, string $type = 'ean13', bool $isPrimary = false): ProductBarcode
    {
        $productBarcode = $this->barcodes()->create([
            'barcode' => $barcode,
            'type' => $type,
            'is_primary' => $isPrimary,
        ]);

        if ($isPrimary) {
            $productBarcode->setAsPrimary();
        }

        return $productBarcode;
    }

    /**
     * Trouver un produit par n'importe quel barcode
     */
    public static function findByBarcode(string $barcode): ?self
    {
        // Chercher d'abord dans product_barcodes
        $product = ProductBarcode::findProductByBarcode($barcode);

        // Fallback sur la colonne ean pour rétrocompatibilité
        if (!$product) {
            $product = static::where('ean', $barcode)->first();
        }

        return $product;
    }

    // Relation vers les livraisons
    public function resellerDeliveries()
    {
        return $this->belongsToMany(ResellerStockDelivery::class, 'reseller_stock_delivery_product')
                    ->withPivot('quantity', 'unit_price')
                    ->withTimestamps();
    }

    // Retrait de stock (FIFO) pour un store
    public function removeStock(Store $store, int $quantity): bool
    {
        $batches = $this->stockBatches()
            ->where('store_id', $store->id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $toDeduct = min($batch->quantity, $remaining);
            $batch->quantity -= $toDeduct;
            $batch->save();

            $remaining -= $toDeduct;
        }

        // Ne plus toucher au pivot product_store
        return $remaining === 0;
    }


    // Booted pour gérer le price_btob
    protected static function booted()
    {
        static::creating(function ($product) {
            if ($product->is_resalable && is_null($product->price_btob)) {
                $product->price_btob = $product->price;
            }
        });

        static::updating(function ($product) {
            if ($product->is_resalable && is_null($product->price_btob)) {
                $product->price_btob = $product->price;
            }
        });
    }

    public function getTranslatedNameAttribute()
    {
        return $this->name[app()->getLocale()] ?? reset($this->name);
    }


    public function variationGroup()
    {
        return $this->belongsTo(VariationGroup::class);
    }

    public function variationAttributes()
    {
        return $this->hasMany(ProductVariationAttribute::class);
    }

    /**
     * Get all other products in the same variation group.
     */
    public function groupSiblings()
    {
        if (!$this->variation_group_id) return collect();

        return static::where('variation_group_id', $this->variation_group_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Recettes de fabrication pour ce produit
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function setTranslation(string $field, string $locale, string $value)
    {
        $data = $this->{$field} ?? [];
        if (!is_array($data)) {
            $data = []; // assure que c’est un tableau
        }
        $data[$locale] = $value;
        $this->{$field} = $data;
        return $this;
    }

    public function publicSlug(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $name = $this->name[$loc] ?? reset($this->name) ?? (string)$this->ean;
        return $this->slugs[$loc] ?? Str::slug($name);
    }

    public function publicUrl(?string $locale = null): string
    {
        $base  = rtrim(config('app.public_shop_url'), '/');
        $path  = trim(config('app.public_product_path', 'product'), '/'); // ex: "product"
        $slug  = $this->publicSlug($locale);
        $loc = $locale ?: app()->getLocale();
        return "{$base}/{$loc}/{$path}/{$slug}";
    }

    // ============ Laravel Scout / Meilisearch Methods ============

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'ean' => $this->ean,
            'barcodes' => $this->barcodes->pluck('barcode')->toArray(),

            // Produit - multilingue (FR/EN)
            'name_fr' => $this->name['fr'] ?? '',
            'name_en' => $this->name['en'] ?? '',
            'description_fr' => strip_tags($this->description['fr'] ?? ''),
            'description_en' => strip_tags($this->description['en'] ?? ''),

            // Marque - pas traduisible
            'brand_name' => $this->brand?->name,
            'brand_id' => $this->brand_id,

            // Catégories - traduisibles
            'category_names_fr' => $this->categories->map(function($c) {
                return $c->translation('fr')?->name ?? '';
            })->filter()->values()->toArray(),

            'category_names_en' => $this->categories->map(function($c) {
                return $c->translation('en')?->name ?? '';
            })->filter()->values()->toArray(),

            'category_ids' => $this->categories->pluck('id')->toArray(),

            // Métadonnées pour filtres
            'price' => (float) $this->price,
            'is_active' => (bool) $this->is_active,
            'is_active_pos' => (bool) $this->is_active_pos,
            'is_best_seller' => (bool) $this->is_best_seller,

            // Pour affichage
            'image_url' => $this->primaryImage?->path,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs()
    {
        return 'products';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // Indexer uniquement les produits actifs (optionnel)
        return true; // ou: return $this->is_active;
    }
}
