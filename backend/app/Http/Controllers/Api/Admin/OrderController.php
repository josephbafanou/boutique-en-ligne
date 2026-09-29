<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', Rule::enum(OrderStatus::class)]]);

        $orders = Order::with(['user', 'items'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function updateStatus(Request $request, Order $order): OrderResource
    {
        $data = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]]);

        $order->update($data);

        return new OrderResource($order->load(['user', 'items']));
    }
}
