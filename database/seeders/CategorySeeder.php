<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\CategoryTranslation;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $locales = config('app.website_locales', ['en', 'fr']);

        // Exemple de structure : Électronique, Vêtements, Maison
        $categories = [
            'Electronics' => ['Phones', 'Laptops', 'Accessories'],
            'Clothing'    => ['Men', 'Women', 'Children'],
            'Home'        => ['Furniture', 'Kitchen', 'Garden'],
        ];

        foreach ($categories as $parentNameEn => $children) {
            // Catégorie parente
            $parent = Category::create(['parent_id' => null]);

            foreach ($locales as $locale) {
                CategoryTranslation::create([
                    'category_id' => $parent->id,
                    'locale'      => $locale,
                    'name'        => $locale === 'en' ? $parentNameEn : $this->translate($parentNameEn, $locale),
                ]);
            }

            // Sous-catégories
            foreach ($children as $childNameEn) {
                $child = Category::create(['parent_id' => $parent->id]);

                foreach ($locales as $locale) {
                    CategoryTranslation::create([
                        'category_id' => $child->id,
                        'locale'      => $locale,
                        'name'        => $locale === 'en' ? $childNameEn : $this->translate($childNameEn, $locale),
                    ]);
                }
            }
        }
    }

    private function translate(string $name, string $locale): string
    {
        // Ici tu peux mettre une vraie traduction si tu veux
        return $locale === 'fr' ? 'FR ' . $name : $name;
    }
}
