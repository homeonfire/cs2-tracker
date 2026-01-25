<?php

namespace App\Http\Controllers;

use App\Services\InventoryService; // <--- Вот этой строки не хватало!
use App\Services\SteamInventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, SteamInventoryService $steamService, InventoryService $inventoryService)
    {
        $user = $request->user();
        
        // ВРЕМЕННО: Тестовый SteamID (Anomaly)
        $steamId = '76561199415296764'; 

        // 1. Качаем и сохраняем
        $steamItems = $steamService->fetchInventory($steamId);

        if (!$steamItems) {
            return Inertia::render('Dashboard', [
                'error' => 'Steam API не отвечает или инвентарь скрыт.'
            ]);
        }

        $inventory = $inventoryService->syncInventory($user, $steamId, $steamItems);

        // 2. Подготавливаем предметы
        $items = $inventory->items()->with('item')->get()->map(function ($invItem) {
            return [
                'id' => $invItem->id,
                'item_id' => $invItem->item_id, // Нужно для графика
                'asset_id' => $invItem->asset_id,
                'name' => $invItem->item->name,
                'market_hash_name' => $invItem->item->market_hash_name,
                'image' => $invItem->item->image_url ?? '',
                'price' => $invItem->item->price_skinport,
                'price_formatted' => '$ ' . number_format($invItem->item->price_skinport, 2),
                'buy_price' => $invItem->buy_price,
                'rarity_color' => $invItem->item->rarity_color,
                'is_tradable' => $invItem->is_tradable,
            ];
        });

        // 3. --- НОВАЯ ЛОГИКА: Считаем общую статистику ---
        
        $totalValue = $inventory->total_value; // Текущая стоимость всего
        $totalInvested = $inventory->items()->sum('buy_price'); // Сколько мы потратили (сумма введенных buy_price)
        
        $totalProfit = $totalValue - $totalInvested; // Чистая прибыль
        
        // ROI (Процент доходности). Защита от деления на ноль.
        $totalRoi = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

        return Inertia::render('Dashboard', [
            'inventory' => $items,
            'itemCount' => $items->count(),
            // Передаем красивую статистику
            'stats' => [
                'value' => number_format($totalValue, 2),
                'invested' => number_format($totalInvested, 2),
                'profit' => number_format($totalProfit, 2),
                'roi' => number_format($totalRoi, 2),
                'is_positive' => $totalProfit >= 0
            ]
        ]);
    }

    // Метод для сохранения цены покупки
    public function update(Request $request, $id)
    {
        // Валидация: цена должна быть числом и не меньше 0
        $validated = $request->validate([
            'buy_price' => 'nullable|numeric|min:0',
        ]);

        // Ищем предмет в инвентаре пользователя
        // where('user_id', ...) - защита, чтобы нельзя было менять чужие предметы
        $inventoryItem = \App\Models\InventoryItem::where('id', $id)
            ->whereHas('inventory', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->firstOrFail();

        $inventoryItem->update([
            'buy_price' => $validated['buy_price']
        ]);

        // Возвращаем пользователя назад, Inertia обновит данные без перезагрузки
        return back();
    }

    public function history($id)
    {
        // Получаем все данные за 30 дней
        $history = \App\Models\ItemPriceHistory::where('item_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'asc')
            ->get();

        // Формируем серии данных для ApexCharts
        $series = [
            [
                'name' => 'Skinport',
                'data' => $history->where('source', 'skinport')->map(fn($r) => [
                    $r->created_at->timestamp * 1000, 
                    (float)$r->price
                ])->values()
            ],
            [
                'name' => 'DMarket',
                'data' => $history->where('source', 'dmarket')->map(fn($r) => [
                    $r->created_at->timestamp * 1000, 
                    (float)$r->price
                ])->values()
            ]
        ];

        return response()->json($series);
    }
}