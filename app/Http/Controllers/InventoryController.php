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
            ->withCount('items') // Нужно для подсчета предметов
            ->orderBy('total_value', 'desc') // Сортируем самые дорогие вверх
            ->get()
    ]);
}

    // 2. СОЗДАНИЕ НОВОГО
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'steam_id' => 'required|numeric|digits:17', // SteamID64 всегда 17 цифр
        ]);

        // Создаем запись в БД
        $request->user()->inventories()->create([
            'name' => $validated['name'],
            'steam_id' => $validated['steam_id'],
            'total_value' => 0
        ]);

        return Redirect::route('inventories.index');
    }

    // 3. ПРОСМОТР КОНКРЕТНОГО (Бывший Dashboard)
    public function show(Request $request, $id, SteamInventoryService $steamService, InventoryService $inventoryService)
    {
        $inventoryModel = $request->user()->inventories()->findOrFail($id);

        // ОБНОВЛЕНИЕ: Если инвентарь совсем пустой, попробуем загрузить его первый раз.
        // В остальных случаях - просто показываем из базы.
        if ($inventoryModel->items()->count() === 0) {
            $steamItems = $steamService->fetchInventory($inventoryModel->steam_id);
            if ($steamItems) {
                $inventoryModel = $inventoryService->syncInventory($request->user(), $inventoryModel->steam_id, $steamItems);
            }
        }

        // Подготовка данных (как и было)
        $items = $inventoryModel->items()->with('item')->get()->map(function ($invItem) {
            return [
                'id' => $invItem->id,
                'item_id' => $invItem->item_id,
                'asset_id' => $invItem->asset_id,
                'name' => $invItem->item->name,
                'market_hash_name' => $invItem->item->market_hash_name, // Важно для поиска картинки
                'image' => $invItem->item->image_url,
                'price' => $invItem->item->price_skinport,
                'price_formatted' => '$ ' . number_format($invItem->item->price_skinport, 2),
                'buy_price' => $invItem->buy_price,
                'rarity_color' => $invItem->item->rarity_color,
                'is_tradable' => $invItem->is_tradable,
            ];
        });

        // Статистика (как и было)
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
            // Передаем дату обновления, чтобы показать юзеру "Обновлено 5 мин назад"
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

    // Принудительное обновление по кнопке
    public function refresh(Request $request, $id, SteamInventoryService $steamService, InventoryService $inventoryService)
    {
        $inventoryModel = $request->user()->inventories()->findOrFail($id);

        // 1. Принудительно качаем
        $steamItems = $steamService->fetchInventory($inventoryModel->steam_id);

        if ($steamItems) {
            // 2. Синхронизируем
            $inventoryService->syncInventory($request->user(), $inventoryModel->steam_id, $steamItems);
            
            // 3. Обновляем метку времени updated_at (чтобы юзер видел свежее время)
            $inventoryModel->touch();

            return back()->with('success', 'Инвентарь успешно обновлен!');
        }

        return back()->with('error', 'Не удалось обновить. Возможно, Steam недоступен.');
    }

    // Страница детальной информации о предмете
    public function itemDetails(Request $request, $id)
    {
        // 1. Ищем предмет в твоем инвентаре
        // Используем findOrFail, чтобы если предмета нет - была 404
        $inventoryItem = \App\Models\InventoryItem::with('item')->where('id', $id)->firstOrFail();
        
        // Проверка прав (что это твой предмет)
        $inventory = \App\Models\Inventory::where('id', $inventoryItem->inventory_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // 2. История цен для графика
        $history = \App\Models\ItemPriceHistory::where('item_id', $inventoryItem->item_id)
            ->where('created_at', '>=', now()->subDays(90)) // За 3 месяца
            ->orderBy('created_at', 'asc')
            ->get();

        // Формируем серии для графика
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

        // 3. Данные для карточки
        return Inertia::render('Inventories/ItemDetails', [
            'inventoryItem' => $inventoryItem,
            'item' => $inventoryItem->item,
            'chartSeries' => $chartSeries,
            'inventoryName' => $inventory->name,
            'inventoryId' => $inventory->id
        ]);
    }
}