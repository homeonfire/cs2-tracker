<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function syncInventory(User $user, string $steamId, array $steamItems)
    {
        $inventory = Inventory::firstOrCreate(
            ['user_id' => $user->id, 'steam_id' => $steamId],
            ['name' => 'Main Inventory', 'total_value' => 0]
        );

        // Собираем все "чистые имена" для поиска в базе (AK-47 | Asiimov)
        $cleanNames = array_unique(array_column($steamItems, 'clean_name'));
        
        // Загружаем существующие предметы из базы
        $globalItems = Item::whereIn('market_hash_name', $cleanNames)->get()->keyBy('market_hash_name');

        $currentAssetIds = [];
        $totalValue = 0;

        DB::transaction(function () use ($inventory, $steamItems, &$globalItems, &$currentAssetIds, &$totalValue) {
            foreach ($steamItems as $itemData) {
                $cleanName = $itemData['clean_name'];
                
                // Если предмета нет в нашей эталонной базе (SkinsSeeder не загрузил), создаем его
                if (!isset($globalItems[$cleanName])) {
                    // ВАЖНО: Мы создаем "чистый" предмет без привязки к качеству
                    $newItem = Item::create([
                        'market_hash_name' => $cleanName,
                        'name' => $cleanName, // Имя тоже чистое
                        'image_url' => $itemData['image'],
                        'rarity_color' => $itemData['rarity_color'],
                        // Цены пока 0, их потом обновит CSFloatService по чистому имени
                        'price_csfloat' => 0, 
                    ]);
                    
                    $globalItems->put($cleanName, $newItem);
                }

                $globalItem = $globalItems[$cleanName];
                $currentAssetIds[] = $itemData['asset_id'];
                
                // Берем цену из базы (она там обновляется отдельным сервисом)
                // Делим на 100, если там центы, или берем как есть
                // Для MVP возьмем price_csfloat (центы) -> доллары
                $price = ($globalItem->price_csfloat ?? 0) / 100;
                $totalValue += $price;

                // Сохраняем ЭКЗЕМПЛЯР предмета в инвентарь юзера
                InventoryItem::updateOrCreate(
                    [
                        'inventory_id' => $inventory->id,
                        'asset_id' => $itemData['asset_id']
                    ],
                    [
                        'item_id' => $globalItem->id, // Ссылка на чистый скин
                        'is_tradable' => $itemData['is_tradable'],
                        
                        // Сохраняем уникальные свойства этого конкретного экземпляра
                        'wear_name' => $itemData['wear_name'],     // "Field-Tested"
                        'is_stattrak' => $itemData['is_stattrak'], // true/false
                        'is_souvenir' => $itemData['is_souvenir'], // true/false
                        
                        // float_value пока null, его Steam Inventory API не отдает напрямую.
                        // Его надо парсить отдельно через inspect link (следующая задача).
                    ]
                );
            }

            // Удаляем проданное
            InventoryItem::where('inventory_id', $inventory->id)
                ->whereNotIn('asset_id', $currentAssetIds)
                ->delete();

            $inventory->update(['total_value' => $totalValue]);
        });
        
        return $inventory;
    }
}