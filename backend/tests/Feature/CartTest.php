<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Sanctum::actingAs(User::factory()->create());
    }

    public function test_adding_the_same_product_twice_increments_quantity(): void
    {
        $product = Product::factory()->create(['price' => 12.50, 'stock' => 10]);

        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertCreated();
        $this->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('items_count', 3)
            ->assertJsonPath('total', 37.5);
    }

    public function test_it_refuses_more_than_the_available_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_it_updates_and_removes_items(): void
    {
        $product = Product::factory()->create(['price' => 10, 'stock' => 10]);
        $item = CartItem::create(['user_id' => $this->user->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->patchJson("/api/cart/items/{$item->id}", ['quantity' => 4])->assertOk()->assertJsonPath('total', 40.0);
        $this->deleteJson("/api/cart/items/{$item->id}")->assertOk()->assertJsonPath('items_count', 0);
    }

    public function test_a_user_cannot_touch_someone_elses_cart(): void
    {
        $other = User::factory()->create();
        $item = CartItem::create([
            'user_id' => $other->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
        ]);

        $this->deleteJson("/api/cart/items/{$item->id}")->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }
}
