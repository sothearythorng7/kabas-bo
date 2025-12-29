<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Supplier;

return new class extends Migration {
    public function up(): void
    {
        // Créer un fournisseur spécial "Warehouse (Factory)" pour les produits fabriqués
        Supplier::firstOrCreate(
            ['name' => 'Warehouse (Factory)'],
            [
                'address' => 'Internal - Factory Production',
                'type' => 'consignment', // consignment car c'est du stock interne
            ]
        );
    }

    public function down(): void
    {
        Supplier::where('name', 'Warehouse (Factory)')->delete();
    }
};
