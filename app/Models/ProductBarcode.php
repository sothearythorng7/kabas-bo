<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'barcode',
        'type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // Types de codes-barres
    const TYPE_EAN13 = 'ean13';
    const TYPE_EAN8 = 'ean8';
    const TYPE_UPC = 'upc';
    const TYPE_INTERNAL = 'internal';

    public static function types(): array
    {
        return [
            self::TYPE_EAN13 => 'EAN-13',
            self::TYPE_EAN8 => 'EAN-8',
            self::TYPE_UPC => 'UPC',
            self::TYPE_INTERNAL => 'Internal',
        ];
    }

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Trouver un produit par son barcode
     */
    public static function findProductByBarcode(string $barcode): ?Product
    {
        $productBarcode = static::where('barcode', $barcode)->first();
        return $productBarcode?->product;
    }

    /**
     * Définir ce barcode comme principal (et retirer le flag des autres)
     */
    public function setAsPrimary(): void
    {
        // Retirer is_primary des autres barcodes du même produit
        static::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Définir celui-ci comme principal
        $this->is_primary = true;
        $this->save();

        // Synchroniser avec la colonne ean du produit
        $this->product->update(['ean' => $this->barcode]);
    }
}
