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
        'price',
        'price_btob',
        'brand_id',
        'color',
        'size',
        'is_active',
        'is_best_seller',
        'is_resalable',
        'allow_overselling',
        'attributes',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'slugs' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
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


    // Relation vers ses valeurs de déclinaison
    public function variationValues()
    {
        return $this->belongsToMany(VariationValue::class, 'product_variations')
                    ->withTimestamps();
    }

    // Produits liés en tant que déclinaisons
    public function relatedProducts()
    {
        return $this->belongsToMany(Product::class, 'product_variation_links',
            'product_id', 'related_product_id')
            ->withTimestamps();
    }

    // Symétrie (inverse)
    public function linkedTo()
    {
        return $this->belongsToMany(Product::class, 'product_variation_links',
            'related_product_id', 'product_id')
            ->withTimestamps();
    }

    // Récupérer toutes les déclinaisons liées (symétriques)
    public function allVariations()
    {
        return $this->relatedProducts->merge($this->linkedTo);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class, 'product_id');
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
