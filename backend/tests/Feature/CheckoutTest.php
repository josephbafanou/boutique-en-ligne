<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Sanctum::actingAs(User::factory()->create());
    }

    private function addToCart(Product $product, int $quantity): void
    {
        CartItem::create(['user_id' => $this->user->id, 'product_id' => $product->id, 'quantity' => $quantity]);
    }

    public function test_checkout_creates_the_order_decrements_stock_and_empties_the_cart(): void
    {
        $shirt = Product::factory()->create(['name' => 'T-shirt', 'price' => 19.99, 'stock' => 5]);
        $bag = Product::factory()->create(['name' => 'Sac', 'price' => 45.00, 'stock' => 2]);
        $this->addToCart($shirt, 3);
        $this->addToCart($bag, 1);

        $response = $this->postJson('/api/orders', ['shipping_address' => '1 rue de Paris, 77100 Meaux']);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('total', 104.97)
            ->assertJsonCount(2, 'items');

        $this->assertSame(2, $shirt->fresh()->stock);
        $this->assertSame(1, $bag->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_price_is_frozen_on_the_order(): void
    {
        $product = Product::factory()->create(['price' => 10, 'stock' => 5]);
        $this->addToCart($product, 1);
        $orderId = $this->postJson('/api/orders', ['shipping_address' => 'Lomé'])->json('id');

        $product->update(['price' => 99]);

        $this->getJson("/api/orders/{$orderId}")->assertJsonPath('items.0.unit_price', 10.0);
    }

    public function test_checkout_fails_when_stock_is_insufficient_and_changes_nothing(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $this->addToCart($product, 3);
        $product->update(['stock' => 1]); // quelqu'un a acheté entre-temps

        $this->postJson('/api/orders', ['shipping_address' => 'Lomé'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_with_an_empty_cart_is_rejected(): void
    {
        $this->postJson('/api/orders', ['shipping_address' => 'Lomé'])->assertUnprocessable();
    }

    public function test_cancelling_a_pending_order_restores_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->addToCart($product, 2);
        $orderId = $this->postJson('/api/orders', ['shipping_address' => 'Lomé'])->json('id');

        $this->postJson("/api/orders/{$orderId}/cancel")->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertSame(5, $product->fresh()->stock);

        // Une commande annulée ne peut pas l'être une seconde fois
        $this->postJson("/api/orders/{$orderId}/cancel")->assertUnprocessable();
    }
}
