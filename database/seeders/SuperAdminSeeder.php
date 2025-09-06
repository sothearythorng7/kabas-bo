<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'adsofts@gmail.com',
            'password' => Hash::make('LingGuifen0108_'), // Remplacez par un mot de passe sécurisé
            'email_verified_at' => now(), // Marque l'email comme vérifié
        ]);

        $user->assignRole('admin'); // Assigne le rôle d'admin
    }
}