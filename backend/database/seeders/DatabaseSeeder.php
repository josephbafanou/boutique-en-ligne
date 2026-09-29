<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Client Démo',
            'email' => 'client@example.com',
        ]);

        $catalog = [
            'Mode' => ['T-shirt en coton bio', 'Pagne wax', 'Sneakers blanches', 'Sac en cuir'],
            'Électronique' => ['Écouteurs Bluetooth', 'Chargeur rapide USB-C', 'Enceinte portable', 'Montre connectée'],
            'Maison' => ['Lampe de bureau', 'Set de tasses en céramique', 'Coussin décoratif', 'Plaid en laine'],
            'Beauté' => ['Beurre de karité', 'Savon noir', 'Huile de coco', 'Parfum boisé'],
        ];

        foreach ($catalog as $categoryName => $products) {
            $category = Category::create(['name' => $categoryName, 'slug' => Str::slug($categoryName)]);

            foreach ($products as $name) {
                Product::factory()->for($category)->create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'stock' => fake()->numberBetween(5, 50),
                ]);
            }
        }
    }
}
