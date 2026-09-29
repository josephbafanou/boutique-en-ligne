<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_access_the_back_office(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/reports/sales')->assertForbidden();
        $this->postJson('/api/admin/products', [])->assertForbidden();
    }

    public function test_admin_can_create_and_update_a_product_with_unique_slugs(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();
        Product::factory()->create(['slug' => 'lampe-de-bureau']);

        $response = $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Lampe de bureau',
            'price' => 39.90,
            'stock' => 12,
        ]);

        $response->assertCreated()->assertJsonPath('slug', 'lampe-de-bureau-2');

        $this->putJson('/api/admin/products/lampe-de-bureau-2', ['stock' => 0])
            ->assertOk()
            ->assertJsonPath('in_stock', false);
    }

    public function test_admin_can_change_an_order_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertOk()
            ->assertJsonPath('status', 'shipped');

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'perdu'])
            ->assertUnprocessable();
    }

    public function test_sales_report_counts_only_paid_orders_in_the_period(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->makeOrder(OrderStatus::Paid, 'Pagne wax', 20.00, 3, now()->subDays(2));
        $this->makeOrder(OrderStatus::Delivered, 'Savon noir', 5.00, 2, now()->subDay());
        $this->makeOrder(OrderStatus::Cancelled, 'Pagne wax', 20.00, 10, now()->subDay());
        $this->makeOrder(OrderStatus::Paid, 'Pagne wax', 20.00, 1, now()->subDays(60)); // hors période

        $response = $this->getJson('/api/admin/reports/sales?from='.now()->subDays(7)->toDateString())
            ->assertOk();

        $response->assertJsonPath('orders_count', 2)
            ->assertJsonPath('revenue', 70.0)
            ->assertJsonPath('average_basket', 35.0)
            ->assertJsonPath('top_products.0.product_name', 'Pagne wax')
            ->assertJsonPath('top_products.0.quantity', 3)
            ->assertJsonCount(2, 'daily');
    }

    private function makeOrder(OrderStatus $status, string $name, float $price, int $qty, $date): void
    {
        $order = Order::factory()->create(['status' => $status, 'total' => $price * $qty]);
        $order->items()->create([
            'product_name' => $name,
            'unit_price' => $price,
            'quantity' => $qty,
        ]);
        $order->forceFill(['created_at' => $date])->save();
    }
}
