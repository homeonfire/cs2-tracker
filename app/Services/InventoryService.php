<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Синхронизирует данные из Steam с нашей локальной базой данных.
     */
    public function syncInventory(User $user, string $steamId, array $steamItems)
    {
        // 1. Находим или создаем "папку" инвентаря
        $inventory = Inventory::firstOrCreate(
            ['user_id' => $user->id, 'steam_id' => $steamId],
            ['name' => 'Main Inventory', 'total_value' => 0]
        );

        // Получаем то, что уже есть в базе
        $hashNames = array_column($steamItems, 'market_hash_name');
        $globalItems = Item::whereIn('market_hash_name', $hashNames)->get()->keyBy('market_hash_name');

        $currentAssetIds = [];
        $totalValue = 0;

        DB::transaction(function () use ($inventory, $steamItems, &$globalItems, &$currentAssetIds, &$totalValue) {
            foreach ($steamItems as $itemData) {
                $name = $itemData['market_hash_name'];
                
                // --- ИЗМЕНЕНИЕ: Создаем предмет, если его нет в базе цен ---
                if (!isset($globalItems[$name])) {
                    $newItem = Item::create([
                        'market_hash_name' => $name,
                        'name' => $itemData['name'],
                        'image_url' => $itemData['image'],
                        'rarity_color' => $itemData['rarity_color'],
                        'price_skinport' => 0, // Цены нет, так как это нетрейдабл
                        'price_steam' => 0,
                    ]);
                    
                    // Добавляем в локальный массив, чтобы дальше код работал как обычно
                    $globalItems->put($name, $newItem);
                }
                // -----------------------------------------------------------

                $globalItem = $globalItems[$name];
                $currentAssetIds[] = $itemData['asset_id'];
                
                // Обновляем картинку/цвет, если они пустые в базе
                if (empty($globalItem->image_url) || empty($globalItem->rarity_color)) {
                    $globalItem->update([
                        'image_url' => $itemData['image'],
                        'rarity_color' => $itemData['rarity_color']
                    ]);
                }

                // Считаем сумму
                $price = $globalItem->price_skinport ?? $globalItem->price_steam ?? 0;
                $totalValue += $price;

                // Сохраняем в инвентарь
                InventoryItem::updateOrCreate(
                    [
                        'inventory_id' => $inventory->id,
                        'asset_id' => $itemData['asset_id']
                    ],
                    [
                        'item_id' => $globalItem->id,
                        'is_tradable' => $itemData['is_tradable'],
                    ]
                );
            }

            // Удаляем проданное
            InventoryItem::where('inventory_id', $inventory->id)
                ->whereNotIn('asset_id', $currentAssetIds)
                ->delete();

            // Обновляем общую стоимость
            $inventory->update(['total_value' => $totalValue]);
        });
        
        return $inventory;
    }
}