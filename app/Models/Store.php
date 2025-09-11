<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Store extends Model
    {
        use HasFactory;

        protected $fillable = [
            'name',
            'address',
            'phone',
            'email',
            'opening_time',
            'closing_time',
            'type',
            'is_reseller',
        ];

        public function products()
        {
            return $this->belongsToMany(Product::class)
                ->withPivot('stock_quantity')
                ->withTimestamps();
        }

        public function getTotalStock()
        {
            return $this->stockBatches()->sum('quantity');
        }


        // Nouveau scope pour filtrer les entrepôts
        public function scopeWarehouse($query)
        {
            return $query->where('type', 'warehouse');
        }

        // Idem pour les shops
        public function scopeShops($query)
        {
            return $query->where('type', 'shop');
        }

        public function stockBatches()
        {
            return $this->hasMany(StockBatch::class);
        }

        public function getCurrentStock()
        {
            return $this->stockBatches()
                ->select('product_id', \DB::raw('SUM(quantity) as total'))
                ->groupBy('product_id')
                ->pluck('total', 'product_id');
        }
    }
