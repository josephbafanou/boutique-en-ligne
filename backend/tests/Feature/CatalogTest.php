<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_active_products(): void
    {
        Product::factory()->count(3)->create();
        Product::factory()->inactive()->create();

        $this->getJson('/api/products')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_filters_by_category_search_and_price(): void
    {
        $mode = Category::factory()->create(['slug' => 'mode']);
        Product::factory()->for($mode)->create(['name' => 'Pagne wax', 'price' => 25]);
        Product::factory()->for($mode)->create(['name' => 'Sac en cuir', 'price' => 120]);
        Product::factory()->create(['name' => 'Enceinte', 'price' => 60]);

        $this->getJson('/api/products?category=mode')->assertJsonCount(2, 'data');
        $this->getJson('/api/products?search=wax')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pagne wax');
        $this->getJson('/api/products?min_price=50&max_price=100')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Enceinte');
    }

    public function test_it_sorts_by_price(): void
    {
        Product::factory()->create(['price' => 30]);
        Product::factory()->create(['price' => 10]);
        Product::factory()->create(['price' => 20]);

        $prices = collect($this->getJson('/api/products?sort=price_asc')->json('data'))->pluck('price');

        $this->assertEquals([10, 20, 30], $prices->all());
    }

    public function test_it_shows_a_product_by_slug_and_hides_inactive_ones(): void
    {
        $product = Product::factory()->create(['slug' => 'savon-noir']);
        $hidden = Product::factory()->inactive()->create();

        $this->getJson('/api/products/savon-noir')->assertOk()->assertJsonPath('id', $product->id);
        $this->getJson("/api/products/{$hidden->slug}")->assertNotFound();
    }

    public function test_categories_include_active_product_count(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->for($category)->create();
        Product::factory()->inactive()->for($category)->create();

        $this->getJson('/api/categories')->assertOk()->assertJsonPath('0.products_count', 2);
    }
}
