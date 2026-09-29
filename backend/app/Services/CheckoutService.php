<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Transforme le panier en commande : vérifie le stock, fige les prix,
     * décrémente le stock et vide le panier, le tout dans une transaction.
     */
    public function checkout(User $user, string $shippingAddress): Order
    {
        return DB::transaction(function () use ($user, $shippingAddress) {
            $items = $user->cartItems()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Le panier est vide.']);
            }

            // Verrouille les lignes produit pour éviter la survente en cas de commandes simultanées
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalCents = 0;
            foreach ($items as $item) {
                $product = $products[$item->product_id];

                if (! $product->is_active || $product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuffisant pour « {$product->name} ».",
                    ]);
                }

                $totalCents += self::toCents($product->price) * $item->quantity;
            }

            $order = $user->orders()->create([
                'status' => OrderStatus::Pending,
                'total' => $totalCents / 100,
                'shipping_address' => $shippingAddress,
            ]);

            foreach ($items as $item) {
                $product = $products[$item->product_id];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item->quantity,
                ]);

                $product->decrement('stock', $item->quantity);
            }

            $user->cartItems()->delete();

            return $order->load('items');
        });
    }

    /** Annule une commande en attente et remet les produits en stock. */
    public function cancel(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Seule une commande en attente peut être annulée.',
            ]);
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => OrderStatus::Cancelled]);

            return $order->load('items');
        });
    }

    private static function toCents(string|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
