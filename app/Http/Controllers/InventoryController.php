<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\InventoryService;
use App\Services\SteamInventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class InventoryController extends Controller
{
    // 1. СПИСОК ИНВЕНТАРЕЙ
    public function index(Request $request)
    {
        return Inertia::render('Inventories/Index', [
            'inventories' => $request->user()->inventories()
                ->withCount('items')
                ->orderBy('total_value', 'desc')
                ->get()
        ]);
    }

    // 2. СОЗДАНИЕ НОВОГО
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'steam_id' => 'required|numeric|digits:17',
        ]);

        $request->user()->inventories()->create([
            'name' => $validated['name'],
            'steam_id' => $validated['steam_id'],
            'total_value' => 0
        ]);

        return Redirect::route('inventories.index');
    }

    // 3. ПРОСМОТР КОНКРЕТНОГО (ИСПРАВЛЕНО)
    public function show(Request $request, $id, SteamInventoryService $steamService, InventoryService $inventoryService)
    {
        $inventoryModel = $request->user()->inventories()->findOrFail($id);

        // Если пусто - пробуем загрузить
        if ($inventoryModel->items()->count() === 0) {
            $steamItems = $steamService->fetchInventory($inventoryModel->steam_id);
            if ($steamItems) {
                $inventoryModel = $inventoryService->syncInventory($request->user(), $inventoryModel->steam_id, $steamItems);
            }
        }

        // === ИСПРАВЛЕНИЕ ЗДЕСЬ ===
        $items = $inventoryModel->items()->with('item')->get()->map(function ($invItem) {
            
            // Используем min_price из модели, а не жесткий skinport
            $bestPrice = $invItem->item->min_price; 

            return [
                'id' => $invItem->id,
                'item_id' => $invItem->item_id,
                'asset_id' => $invItem->asset_id,
                'name' => $invItem->item->name,
                'market_hash_name' => $invItem->item->market_hash_name,
                'image' => $invItem->item->image_url,
                
                // ВОТ ТУТ МЕНЯЕМ: берем лучшую цену
                'price' => $bestPrice,
                'price_formatted' => '$ ' . number_format($bestPrice, 2),
                
                'buy_price' => $invItem->buy_price,
                'rarity_color' => $invItem->item->rarity_color,
                'is_tradable' => $invItem->is_tradable,
            ];
        });

        // === ПЕРЕСЧЕТ СТОИМОСТИ ===
        // Пересчитываем общую стоимость на основе min_price прямо сейчас
        $calculatedTotal = $items->sum('price');
        
        // Если стоимость в базе отличается от реальной (например, была 0), обновляем базу
        if (abs($inventoryModel->total_value - $calculatedTotal) > 0.01) {
            $inventoryModel->total_value = $calculatedTotal;
            $inventoryModel->save();
        }

        $totalValue = $inventoryModel->total_value;
        $totalInvested = $inventoryModel->items()->sum('buy_price');
        $totalProfit = $totalValue - $totalInvested;
        $totalRoi = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

        return Inertia::render('Inventories/Show', [
            'inventory' => $inventoryModel,
            'items' => $items,
            'itemCount' => $items->count(),
            'stats' => [
                'value' => number_format($totalValue, 2),
                'invested' => number_format($totalInvested, 2),
                'profit' => number_format($totalProfit, 2),
                'roi' => number_format($totalRoi, 2),
                'is_positive' => $totalProfit >= 0
            ],
            'last_updated' => $inventoryModel->updated_at->diffForHumans()
        ]);
    }

    // 4. УДАЛЕНИЕ
    public function destroy(Request $request, $id)
    {
        $inventory = $request->user()->inventories()->findOrFail($id);
        $inventory->delete();

        return Redirect::route('inventories.index');
    }

    // REFRESH
    public function refresh(Request $request, $id, SteamInventoryService $steamService, InventoryService $inventoryService)
    {
        $inventoryModel = $request->user()->inventories()->findOrFail($id);
        $steamItems = $steamService->fetchInventory($inventoryModel->steam_id);

        if ($steamItems) {
            $inventoryService->syncInventory($request->user(), $inventoryModel->steam_id, $steamItems);
            
            // После синхронизации нужно пересчитать total_value
            // Можно сделать редирект на show, там оно само пересчитается кодом выше
            return back()->with('success', 'Инвентарь обновлен!');
        }

        return back()->with('error', 'Ошибка Steam');
    }

    // Item Details
    public function itemDetails(Request $request, $id)
    {
        $inventoryItem = \App\Models\InventoryItem::with('item')->where('id', $id)->firstOrFail();
        
        $inventory = \App\Models\Inventory::where('id', $inventoryItem->inventory_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $history = \App\Models\ItemPriceHistory::where('item_id', $inventoryItem->item_id)
            ->where('created_at', '>=', now()->subDays(90))
            ->orderBy('created_at', 'asc')
            ->get();

        $chartSeries = [
            [
                'name' => 'Skinport',
                'data' => $history->where('source', 'skinport')->map(fn($r) => [
                    $r->created_at->timestamp * 1000, (float)$r->price
                ])->values()
            ],
            [
                'name' => 'DMarket',
                'data' => $history->where('source', 'dmarket')->map(fn($r) => [
                    $r->created_at->timestamp * 1000, (float)$r->price
                ])->values()
            ]
        ];

        return Inertia::render('Inventories/ItemDetails', [
            'inventoryItem' => $inventoryItem,
            'item' => $inventoryItem->item,
            'chartSeries' => $chartSeries,
            'inventoryName' => $inventory->name,
            'inventoryId' => $inventory->id
        ]);
    }
}