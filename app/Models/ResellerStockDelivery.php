<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class ResellerStockDelivery extends Model
    {
        protected $fillable = [
            'reseller_id',
            'status',
            'shipping_cost',
            'delivered_at',
        ];

        public const STATUS_OPTIONS = [
            'draft' => 'In preparation',
            'ready_to_ship' => 'Ready to ship',
            'shipped' => 'Shipped',
            'cancelled' => 'Cancelled',
        ];

        public function getRouteKeyName()
        {
            return 'id'; // c’est déjà l’ID par défaut, donc pas strictement nécessaire
        }

        public function reseller()
        {
            return $this->belongsTo(Reseller::class);
        }

        public function products()
        {
            return $this->belongsToMany(Product::class, 'reseller_stock_delivery_product')
                        ->withPivot('quantity', 'unit_price')
                        ->withTimestamps();
        }

        public function invoice()
        {
            return $this->hasOne(ResellerInvoice::class, 'reseller_stock_delivery_id');
        }
    }
