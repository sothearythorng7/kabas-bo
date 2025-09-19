<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierOrderInvoiceLine;

class SupplierOrder extends Model
{
    protected $fillable = ['supplier_id', 'status', 'destination_store_id', 'is_paid'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_order_product')
            ->withPivot('purchase_price','sale_price','quantity_ordered','quantity_received')
            ->withTimestamps();
    }

    public function totalOrdered()
    {
        return $this->products->sum(fn($product) => $product->pivot->quantity_ordered);
    }

    public function totalReceived()
    {
        return $this->products->sum(fn($product) => $product->pivot->quantity_received);
    }

    public function expectedAmount()
    {
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
        return DB::table('supplier_order_product as sop')
            ->join('stock_batches as sb', function ($join) {
                $join->on('sb.product_id', '=', 'sop.product_id')
                    ->whereColumn('sb.source_supplier_order_id', 'sop.supplier_order_id');
            })
            ->where('sop.supplier_order_id', $this->id)
            ->selectRaw('SUM(sop.quantity_ordered * sb.unit_price) as total')
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
