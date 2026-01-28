<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\InventoryService;
use App\Services\SteamInventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use App\Models\InventoryItem; // <--- ДОБАВЬТЕ ВОТ ЭТУ СТРОКУ
use App\Models\Item;          // <--- И ЭТУ (на всякий случай)

class InventoryController extends Controller
{
    // 1. СПИСОК ИНВЕНТАРЕЙ
    // 1. СПИСОК ИНВЕНТАРЕЙ (С ПРАВИЛЬНЫМ ПОДСЧЕТОМ)
    public function index(Request $request)
    {
        $inventories = $request->user()->inventories()
            ->withCount('items')
            // Грузим все связи: Инвентарь -> Вещи -> Скин -> Цены
            ->with(['items.item.prices']) 
            ->get()
            ->map(function ($inventory) {
                
                // Проходимся по всем предметам и суммируем их РЕАЛЬНУЮ цену
                $realTotal = $inventory->items->sum(function ($invItem) {
                    $globalItem = $invItem->item;

                    // --- ЛОГИКА СБОРКИ ВАРИАЦИИ (как в методе Show) ---
                    $prefix = '';
                    if ($invItem->is_stattrak) {
                        $prefix = 'StatTrak ';
                    } elseif ($invItem->is_souvenir) {
                        $prefix = 'Souvenir ';
                    }

                    // Собираем ключ: "StatTrak Field-Tested"
                    $targetVariation = trim($prefix . ($invItem->wear_name ?? ''));
                    
                    if ($targetVariation === '') {
                        $targetVariation = null;
                    }

                    // --- ПОИСК ЦЕНЫ ---
                    // 1. Ищем точное совпадение
                    $priceObj = $globalItem->prices
                        ->where('market_name', 'dmarket')
                        ->where('variation', $targetVariation)
                        ->first();

                    // 2. Фолбэк (если точной нет, ищем базовую)
                    if (!$priceObj) {
                        $priceObj = $globalItem->prices
                            ->where('market_name', 'dmarket')
                            ->whereNull('variation')
                            ->first();
                    }

                    return $priceObj ? $priceObj->price : 0;
                });

                // Подменяем значение total_value для вывода
                $inventory->total_value = $realTotal;

                return $inventory;
            })
            // Сортируем от богатых к бедным
            ->sortByDesc('total_value') 
            ->values();

        return Inertia::render('Inventories/Index', [
            'inventories' => $inventories
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
    // 3. ПРОСМОТР КОНКРЕТНОГО
    // 3. ПРОСМОТР КОНКРЕТНОГО (ОБНОВЛЕН ПОД АГРЕГАТОР)
    public function show(Request $request, $id, SteamInventoryService $steamService, InventoryService $inventoryService)
    {
        $inventoryModel = $request->user()->inventories()->findOrFail($id);

        // 1. Авто-загрузка со Steam, если инвентарь пуст
        if ($inventoryModel->items()->count() === 0) {
            $steamItems = $steamService->fetchInventory($inventoryModel->steam_id);
            if ($steamItems) {
                $inventoryModel = $inventoryService->syncInventory($request->user(), $inventoryModel->steam_id, $steamItems);
            }
        }

        // Хелпер цветов редкости
        $rarityColors = [
            1 => 'b0c3d9', 2 => '5e98d9', 3 => '4b69ff', 4 => '8847ff', 
            5 => 'd32ce6', 6 => 'eb4b4b', 7 => 'e4ae39',
        ];

        // 2. Сборка коллекции предметов для фронтенда
        $items = $inventoryModel->items()
            ->with(['item.prices']) // Жадная загрузка цен агрегатора
            ->get()
            ->map(function ($invItem) use ($rarityColors) {
                $globalItem = $invItem->item;

                // --- А. ОПРЕДЕЛЕНИЕ ЦВЕТА ---
                $color = $globalItem->rarity_color;
                if (empty($color) && $globalItem->rarity_id) {
                    $color = $rarityColors[$globalItem->rarity_id] ?? 'b0c3d9';
                }

                // --- Б. ОПРЕДЕЛЕНИЕ ЦЕНЫ (УМНЫЙ ПОИСК) ---
                $bestPrice = 0;

                // 1. Формируем ключ вариации так же, как в парсере DMarket
                // Логика: "Префикс " + "Качество"
                $prefix = '';
                if ($invItem->is_stattrak) {
                    $prefix = 'StatTrak ';
                } elseif ($invItem->is_souvenir) {
                    $prefix = 'Souvenir ';
                }

                // Результат: "StatTrak Field-Tested" или просто "Factory New"
                $targetVariation = trim($prefix . ($invItem->wear_name ?? ''));
                
                // Если строка пустая (ванильный предмет без качества), делаем null
                if ($targetVariation === '') {
                    $targetVariation = null;
                }

                // 2. Ищем цену DMarket для ЭТОЙ вариации
                $priceObj = $globalItem->prices
                    ->where('market_name', 'dmarket')
                    ->where('variation', $targetVariation)
                    ->first();

                // 3. Fallback: Если точной вариации нет, ищем цену без вариации (null)
                // Это актуально для наклеек, кейсов, музыки
                if (!$priceObj) {
                    $priceObj = $globalItem->prices
                        ->where('market_name', 'dmarket')
                        ->whereNull('variation')
                        ->first();
                }

                if ($priceObj) {
                    $bestPrice = $priceObj->price;
                }
                // ----------------------------------------

                return [
                    'id' => $invItem->id,
                    'item_id' => $invItem->item_id,
                    'asset_id' => $invItem->asset_id,
                    'name' => $globalItem->name,
                    'market_hash_name' => $globalItem->market_hash_name,
                    'image' => $globalItem->image_url,
                    'rarity_color' => $color,
                    'wear_name' => $invItem->wear_name,
                    'is_stattrak' => (bool)$invItem->is_stattrak,
                    'is_souvenir' => (bool)$invItem->is_souvenir,
                    
                    // Цены
                    'price' => $bestPrice,
                    'price_formatted' => '$' . number_format($bestPrice, 2),
                    
                    'buy_price' => $invItem->buy_price,
                    'is_tradable' => $invItem->is_tradable,
                ];
            });

        // 3. Пересчет статистики инвентаря
        $calculatedTotal = $items->sum('price');
        
        // Обновляем кэш в базе, если изменилась сумма
        if (abs($inventoryModel->total_value - $calculatedTotal) > 0.01) {
            $inventoryModel->total_value = $calculatedTotal;
            $inventoryModel->save();
        }

        $totalInvested = $inventoryModel->items()->sum('buy_price');
        $totalProfit = $inventoryModel->total_value - $totalInvested;
        $totalRoi = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

        return Inertia::render('Inventories/Show', [
            'inventory' => $inventoryModel,
            'items' => $items,
            'itemCount' => $items->count(),
            'stats' => [
                'value' => number_format($inventoryModel->total_value, 2),
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
public function itemDetails($id)
    {
        // 1. Загружаем предмет
        $inventoryItem = \App\Models\InventoryItem::with(['item.prices'])->findOrFail($id);

        if ($inventoryItem->inventory->user_id !== auth()->id()) {
            abort(403);
        }

        $item = $inventoryItem->item;

        // 2. Формируем вариацию (StatTrak + Износ)
        $variation = $inventoryItem->wear_name; // Например "Minimal Wear"

        if ($inventoryItem->is_stattrak) {
            $variation = 'StatTrak ' . $variation;
        } elseif ($inventoryItem->is_souvenir) {
            $variation = 'Souvenir ' . $variation;
        }
        
        // 3. Формируем строку source точно как в базе
        // На скриншоте формат: "dmarket_Minimal Wear" (через подчеркивание)
        $targetSource = 'dmarket_' . $variation;

        // 4. Делаем запрос по колонке source
        $history = \App\Models\ItemPriceHistory::where('item_id', $item->id)
            ->where('source', $targetSource) // <--- ИЩЕМ ПО SOURCE
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    $row->created_at->valueOf(), 
                    (float) $row->price
                ];
            });

        return Inertia::render('Inventories/ItemDetails', [
            'inventoryItem' => $inventoryItem,
            'item' => $item,
            'inventoryName' => $inventoryItem->inventory->name,
            'inventoryId' => $inventoryItem->inventory->id,
            'chartSeries' => [
                [
                    'name' => $variation, // Название для легенды графика
                    'data' => $history
                ]
            ]
        ]);
    }

    // Метод для обновления цены покупки (и других полей предмета)
    public function updateItem(Request $request, $id)
    {
        $validated = $request->validate([
            'buy_price' => 'required|numeric|min:0',
        ]);

        // Находим предмет (проверка прав уже внутри middleware или через связь, 
        // но для надежности проверим через user)
        $inventoryItem = \App\Models\InventoryItem::where('id', $id)->firstOrFail();
        
        // Проверяем, принадлежит ли инвентарь этому пользователю
        $inventory = \App\Models\Inventory::where('id', $inventoryItem->inventory_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $inventoryItem->update([
            'buy_price' => $validated['buy_price']
        ]);

        return back()->with('success', 'Цена покупки обновлена');
    }
}