<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\Journal;
    use App\Models\Payment;
    use App\Models\Expense;
    use App\Models\ExpenseCategory;

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

        public function journals()
        {
            return $this->hasMany(Journal::class);
        }

        public function payments()
        {
            return $this->hasMany(Payment::class);
        }

        public function expenses()
        {
            return $this->hasMany(Expense::class);
        }

        public function expenseCategories()
        {
            return $this->hasMany(ExpenseCategory::class);
        }

            // ⚠ Ajouter cette relation pour les paiements fournisseurs
        public function supplierPayments()
        {
            return $this->hasMany(SupplierPayment::class);
        }

        // ⚠ Ajouter relation pour les dépenses générales
        public function generalExpenses()
        {
            return $this->hasMany(GeneralExpense::class);
        }
    }
