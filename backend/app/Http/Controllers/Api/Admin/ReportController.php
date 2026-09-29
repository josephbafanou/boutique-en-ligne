<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Tableau de bord des ventes sur une période :
     * chiffre d'affaires, nombre de commandes, panier moyen, top produits, ventes par jour.
     */
    public function sales(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->subDays(29))->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();
        $statuses = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::revenue());

        $orders = Order::whereIn('status', $statuses)->whereBetween('created_at', [$from, $to]);

        $revenue = (float) (clone $orders)->sum('total');
        $count = (clone $orders)->count();

        $daily = (clone $orders)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $statuses)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as quantity, '
                .'SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'quantity' => (int) $row->quantity,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        return response()->json([
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'revenue' => round($revenue, 2),
            'orders_count' => $count,
            'average_basket' => $count ? round($revenue / $count, 2) : 0,
            'top_products' => $topProducts,
            'daily' => $daily,
        ]);
    }
}
