<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Item;
use App\Models\Collection; // <--- Не забудь эту модель
use Illuminate\Support\Facades\DB;

class SkinsSeeder extends Seeder
{
    public function run()
    {
        // 1. Проверяем файл
        $path = storage_path('app/skins.json');
        if (!File::exists($path)) {
            $this->command->error("Файл {$path} не найден! Загрузите файл skins.json в папку storage/app/.");
            return;
        }

        // 2. Читаем JSON
        $json = File::get($path);
        $data = json_decode($json, true);

        if (!$data) {
            $this->command->error("Ошибка декодирования JSON.");
            return;
        }

        $this->command->info("Начинаем обработку " . count($data) . " предметов...");

        // 3. Карта редкости для конвертации в ID (1-7)
        $rarityMap = [
            'rarity_common_weapon' => 1,      // Consumer Grade (White)
            'rarity_uncommon_weapon' => 2,    // Industrial Grade (Light Blue)
            'rarity_rare_weapon' => 3,        // Mil-Spec Grade (Blue)
            'rarity_mythical_weapon' => 4,    // Restricted (Purple)
            'rarity_legendary_weapon' => 5,   // Classified (Pink)
            'rarity_ancient_weapon' => 6,     // Covert (Red)
            'rarity_contraband_weapon' => 7,  // Contraband (Gold)
            
            // На всякий случай альтернативные ключи
            'rarity_common' => 1,
            'rarity_uncommon' => 2,
            'rarity_rare' => 3,
            'rarity_mythical' => 4,
            'rarity_legendary' => 5,
            'rarity_ancient' => 6,
            'rarity_contraband' => 7,
        ];

        $count = 0;

        foreach ($data as $skin) {
            // Фильтрация мусора: оставляем только скины, ножи и перчатки
            // Убираем агентов, граффити, наборы музыки и патчи
            if (
                !isset($skin['name']) || 
                str_contains($skin['id'], 'agent') || 
                str_contains($skin['id'], 'patch') ||
                str_contains($skin['id'], 'graffiti') ||
                str_contains($skin['id'], 'music_kit') ||
                str_contains($skin['id'], 'pin')
            ) {
                continue;
            }

            // --- 1. ОБРАБОТКА КОЛЛЕКЦИИ ---
            $collectionId = null;
            
            // Если у скина есть коллекция (обычно массив)
            if (isset($skin['collections']) && is_array($skin['collections']) && count($skin['collections']) > 0) {
                $colData = $skin['collections'][0]; // Берем первую (основную)
                
                // Находим или создаем коллекцию в БД
                // Используем кэширование в памяти Laravel для updateOrCreate было бы быстрее, 
                // но для сидера сойдет и так.
                $collection = Collection::firstOrCreate(
                    ['name' => $colData['name']], // Поиск по имени
                    ['image' => $colData['image'] ?? null] // Если создаем новую - пишем картинку
                );
                
                $collectionId = $collection->id;
            }

            // --- 2. ОБРАБОТКА РЕДКОСТИ ---
            $rarityKey = $skin['rarity']['id'] ?? null;
            $rarityId = $rarityMap[$rarityKey] ?? null;
            
            // Дополнительный цвет редкости (hex), если есть в JSON
            $rarityColor = $skin['rarity']['color'] ?? null; 
            // JSON часто дает цвет без решетки, добавим если надо, но обычно там hex string

            // --- 3. ЗАПИСЬ ПРЕДМЕТА ---
            Item::updateOrCreate(
                ['market_hash_name' => $skin['name']], // Уникальный ключ
                [
                    'image_url' => $skin['image'] ?? null,
                    
                    // Данные для контрактов
                    'collection_id' => $collectionId,
                    'min_float' => $skin['min_float'] ?? 0.00,
                    'max_float' => $skin['max_float'] ?? 1.00,
                    'rarity_id' => $rarityId,
                    
                    // Опционально сохраним цвет, если он пришел из JSON
                    // 'rarity_color' => $rarityColor 
                ]
            );

            $count++;
            
            if ($count % 500 == 0) {
                $this->command->info("Обработано: {$count}...");
            }
        }

        $this->command->info("✅ Успешно! Обработано и обновлено {$count} скинов.");
    }
}