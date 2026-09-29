<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->cartResponse($request);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'between:1,99'],
        ]);

        $product = Product::active()->findOrFail($data['product_id']);
        $item = $request->user()->cartItems()->firstOrNew(['product_id' => $product->id]);
        $quantity = ($item->exists ? $item->quantity : 0) + ($data['quantity'] ?? 1);

        $this->ensureStock($product, $quantity);

        $item->quantity = $quantity;
        $item->save();

        return $this->cartResponse($request, 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeItem($request, $cartItem);

        $data = $request->validate(['quantity' => ['required', 'integer', 'between:1,99']]);
        $this->ensureStock($cartItem->product, $data['quantity']);

        $cartItem->update($data);

        return $this->cartResponse($request);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeItem($request, $cartItem);
        $cartItem->delete();

        return $this->cartResponse($request);
    }

    private function cartResponse(Request $request, int $status = 200): JsonResponse
    {
        $items = $request->user()->cartItems()->with('product.category')->oldest()->get();
        $total = $items->sum(fn (CartItem $item) => (float) $item->product->price * $item->quantity);

        return response()->json([
            'items' => CartItemResource::collection($items),
            'items_count' => $items->sum('quantity'),
            'total' => round($total, 2),
        ], $status);
    }

    private function ensureStock(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Il ne reste que {$product->stock} exemplaire(s) de « {$product->name} ».",
            ]);
        }
    }

    private function authorizeItem(Request $request, CartItem $cartItem): void
    {
        // 404 plutôt que 403 : on ne révèle pas l'existence du panier d'un autre utilisateur
        abort_unless($cartItem->user_id === $request->user()->id, 404);
    }
}
