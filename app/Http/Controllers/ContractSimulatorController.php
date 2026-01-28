<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractSimulatorController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function index()
    {
        return Inertia::render('Simulator/Index', [
            // Сортируем коллекции по имени для удобства
            'collections' => \App\Models\Collection::orderBy('name')->get(['id', 'name']),
            'rarities' => [
                ['id' => 1, 'name' => 'Consumer Grade (White)', 'color' => 'b0c3d9'],
                ['id' => 2, 'name' => 'Industrial Grade (Light Blue)', 'color' => '5e98d9'],
                ['id' => 3, 'name' => 'Mil-Spec (Blue)', 'color' => '4b69ff'],
                ['id' => 4, 'name' => 'Restricted (Purple)', 'color' => '8847ff'],
                ['id' => 5, 'name' => 'Classified (Pink)', 'color' => 'd32ce6'],
                ['id' => 6, 'name' => 'Covert (Red)', 'color' => 'eb4b4b'], // <--- ДОБАВИЛИ КРАСНОЕ
            ]
        ]);
    }

    public function search(Request $request)
    {
        // Разрешаем редкость < 7 (то есть до 6 включительно - Covert)
        // 7 - это уже Ножи/Перчатки, их в инпут класть нельзя
        $query = Item::query()
            ->whereNotNull('collection_id')
            ->where('rarity_id', '<', 7); 

        if ($request->filled('query')) {
            $query->where('market_hash_name', 'like', "%{$request->query('query')}%");
        }

        if ($request->filled('rarity_id')) {
            $query->where('rarity_id', $request->query('rarity_id'));
        }

        if ($request->filled('collection_id')) {
            $query->where('collection_id', $request->query('collection_id'));
        }

        $items = $query->limit(40)
            ->with(['collection', 'prices'])
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'image' => $item->image_url,
                    'rarity_id' => $item->rarity_id,
                    'rarity_color' => $item->rarity_color,
                    'collection_name' => $item->collection->name ?? 'Unknown',
                    'min_float' => $item->min_float,
                    'max_float' => $item->max_float,
                    'prices' => $item->prices->map(fn($p) => [
                        'market_name' => $p->market_name,
                        'variation' => $p->variation,
                        'price' => $p->price
                    ]),
                ];
            });

        return response()->json($items);
    }

    public function calculate(Request $request)
    {
        // Валидация и вызов сервиса (оставляем как было)
        $validated = $request->validate([
            'items' => 'required|array', // Убрали size:10, так как может быть 5
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.float_value' => 'required|numeric|min:0|max:1',
            'items.*.price' => 'nullable|numeric',
        ]);

        try {
            $result = $this->contractService->simulate($validated['items']);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function profitable()
    {
        $contracts = \App\Models\ProfitableContract::with(['item.collection'])
            ->orderBy('roi', 'desc') // Самые выгодные сверху
            ->limit(100) // Берем топ 100
            ->get();

        return Inertia::render('Simulator/Profitable', [
            'contracts' => $contracts
        ]);
    }
    
    public function landing()
    {
        // Берем топ-50 самых выгодных контрактов для витрины
        $contracts = \App\Models\ProfitableContract::with(['item.collection'])
            ->where('roi', '>', 0) // Только плюсовые
            ->orderBy('roi', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Simulator/Landing', [
            'contracts' => $contracts
        ]);
    }
}