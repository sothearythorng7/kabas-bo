<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierOrderInvoiceLine;

class SupplierOrder extends Model
{
    protected $fillable = ['supplier_id', 'status', 'destination_store_id', 'is_paid', 'order_type', 'invoice_file'];

    const ORDER_TYPE_PRODUCT = 'product';
    const ORDER_TYPE_RAW_MATERIAL = 'raw_material';

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_order_product')
            ->withPivot('purchase_price', 'sale_price', 'quantity_ordered', 'quantity_received', 'invoice_price')
            ->withTimestamps();
    }

    /**
     * Matières premières de la commande
     */
    public function rawMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'supplier_order_raw_material')
            ->withPivot('purchase_price', 'quantity_ordered', 'quantity_received', 'invoice_price')
            ->withTimestamps();
    }

    /**
     * Vérifie si c'est une commande de matières premières
     */
    public function isRawMaterialOrder(): bool
    {
        return $this->order_type === self::ORDER_TYPE_RAW_MATERIAL;
    }

    /**
     * Vérifie si c'est une commande de produits
     */
    public function isProductOrder(): bool
    {
        return $this->order_type === self::ORDER_TYPE_PRODUCT;
    }

    public function totalOrdered()
    {
        if ($this->isRawMaterialOrder()) {
            return $this->rawMaterials->sum(fn($m) => $m->pivot->quantity_ordered);
        }
        return $this->products->sum(fn($product) => $product->pivot->quantity_ordered);
    }

    public function totalReceived()
    {
        if ($this->isRawMaterialOrder()) {
            return $this->rawMaterials->sum(fn($m) => $m->pivot->quantity_received);
        }
        return $this->products->sum(fn($product) => $product->pivot->quantity_received);
    }

    public function expectedAmount()
    {
        if ($this->isRawMaterialOrder()) {
            return DB::table('supplier_order_raw_material')
                ->where('supplier_order_id', $this->id)
                ->selectRaw('SUM(quantity_ordered * purchase_price) as total')
                ->value('total') ?? 0;
        }

        // Si des différences de prix ont été enregistrées, on calcule à partir de reference_price
        if ($this->priceDifferences()->exists()) {
            return $this->priceDifferences->sum(function ($line) {
                $quantity = $this->products->find($line->product_id)?->pivot->quantity_ordered ?? 0;
                return $line->reference_price * $quantity;
            });
        }

        // Sinon on se base sur le pivot actuel
        return DB::table('supplier_order_product')
            ->where('supplier_order_id', $this->id)
            ->selectRaw('SUM(quantity_ordered * purchase_price) as total')
            ->value('total') ?? 0;
    }

    public function invoicedAmount()
    {
        if ($this->isRawMaterialOrder()) {
            return DB::table('supplier_order_raw_material')
                ->where('supplier_order_id', $this->id)
                ->selectRaw('SUM(quantity_received * COALESCE(invoice_price, purchase_price)) as total')
                ->value('total') ?? 0;
        }

        return DB::table('supplier_order_product as sop')
            ->join('stock_batches as sb', function ($join) {
                $join->on('sb.product_id', '=', 'sop.product_id')
                    ->whereColumn('sb.source_supplier_order_id', 'sop.supplier_order_id');
            })
            ->where('sop.supplier_order_id', $this->id)
            ->selectRaw('SUM(sop.quantity_received * sb.unit_price) as total')
            ->value('total') ?? 0;
    }

    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function priceDifferences()
    {
        return $this->hasMany(SupplierOrderInvoiceLine::class, 'supplier_order_id');
    }
}
