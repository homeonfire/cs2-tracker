<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\ItemPriceHistory;
use App\Models\MarketPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncDmarket extends Command
{
    /**
     * Имя команды для запуска в терминале.
     */
    protected $signature = 'dmarket:sync';

    /**
     * Описание команды.
     */
    protected $description = 'Парсинг цен DMarket с учетом StatTrak и износа';

    /**
     * Основная логика.
     */
    public function handle()
    {
        $this->info('🚀 Запуск агрегатора DMarket (v2 - StatTrak fix)...');

        // 1. Загружаем справочник предметов в память для скорости
        // Ключ: Market Hash Name (чистый), Значение: ID
        $this->comment('Загрузка базы предметов...');
        $itemMap = Item::pluck('id', 'market_hash_name')->toArray();
        $this->info('Загружено ' . count($itemMap) . ' предметов.');

        $offset = 0;
        $limit = 10000; // Лимит DMarket API
        $totalProcessed = 0;
        $keepFetching = true;

        // Бесконечный цикл по страницам API
        do {
            $this->comment("Запрос страницы: Offset $offset...");

            try {
                // Запрос к API агрегатора цен DMarket
                $response = Http::timeout(60)->get('https://api.dmarket.com/price-aggregator/v1/aggregated-prices', [
                     'AppID' => 730, 
                     'Limit' => $limit, 
                     'Offset' => $offset
                ]);
                
                if ($response->failed()) {
                    $this->error('Ошибка API: ' . $response->status());
                    break;
                }
                
                $data = $response->json();
                $itemsList = $data['AggregatedTitles'] ?? [];
                $count = count($itemsList);

                if ($count === 0) {
                    $keepFetching = false;
                    break;
                }

                $pricesUpsert = [];   // Массив для обновления текущих цен
                $historyInserts = []; // Массив для истории (графиков)
                $now = now();

                foreach ($itemsList as $marketItem) {
                    $fullName = $marketItem['MarketHashName'];
                    $price = (float) ($marketItem['Offers']['BestPrice'] ?? 0);
                    
                    if ($price <= 0) continue;

                    // 2. УМНЫЙ ПАРСИНГ ИМЕНИ
                    // Нам нужно: 
                    // a) Чистое имя для поиска ID (AK-47 | Redline)
                    // b) Вариацию для цены (StatTrak Field-Tested)
                    $parsed = $this->parseName($fullName);
                    $cleanName = $parsed['clean'];
                    $variation = $parsed['variation']; 

                    // Если чистого предмета нет в нашей базе items — пропускаем
                    if (!isset($itemMap[$cleanName])) {
                        continue;
                    }

                    $itemId = $itemMap[$cleanName];

                    // 3. Подготовка данных для market_prices (Текущая цена)
                    $pricesUpsert[] = [
                        'item_id' => $itemId,
                        'market_name' => 'dmarket',
                        'variation' => $variation, // "StatTrak Field-Tested" или null
                        'price' => $price,
                        'market_link' => "https://dmarket.com/ingame-items/item-list/csgo-skins?title=" . urlencode($fullName),
                        'updated_at' => $now,
                        'created_at' => $now, // Нужно для upsert, если запись создается впервые
                    ];
                    
                    // 4. Подготовка данных для item_price_histories (Графики)
                    // Пишем историю для каждой вариации отдельно
                    $historyInserts[] = [
                        'item_id' => $itemId,
                        'price' => $price,
                        'source' => 'dmarket' . ($variation ? '_' . $variation : ''),
                        'created_at' => $now,
                    ];
                }

                // 5. Массовое сохранение (Upsert)
                if (!empty($pricesUpsert)) {
                    MarketPrice::upsert(
                        $pricesUpsert,
                        ['item_id', 'market_name', 'variation'], // Уникальный ключ (Тройной!)
                        ['price', 'updated_at', 'market_link']   // Поля для обновления
                    );
                }

                // 6. Сохранение истории (Insert Chunked)
                if (!empty($historyInserts)) {
                    foreach (array_chunk($historyInserts, 2000) as $chunk) {
                        ItemPriceHistory::insert($chunk);
                    }
                }

                $totalProcessed += $count;
                $this->info("Обработано: $count (Всего: $totalProcessed)");

                $offset += $limit;
                // Если API вернул меньше лимита, значит это конец
                if ($count < $limit) {
                    $keepFetching = false;
                }

                sleep(1); // Пауза во избежание блокировки

            } catch (\Exception $e) {
                $this->error('Exception: ' . $e->getMessage());
                break;
            }

        } while ($keepFetching);
        
        $this->newLine();
        $this->info("✅ Синхронизация завершена! Обработано записей: $totalProcessed");
    }

    /**
     * Разбирает полное имя из DMarket на "Чистое имя" и "Вариацию".
     * Пример: "StatTrak™ AK-47 | Redline (Field-Tested)"
     * Clean: "AK-47 | Redline"
     * Variation: "StatTrak Field-Tested"
     */
    private function parseName($name)
    {
        $clean = $name;
        $wear = null;
        $prefix = '';

        // Список качеств
        $wears = [
            ' (Factory New)', ' (Minimal Wear)', ' (Field-Tested)', 
            ' (Well-Worn)', ' (Battle-Scarred)', ' (Not Painted)'
        ];

        // 1. Вытаскиваем качество (Wear)
        foreach ($wears as $w) {
            if (str_ends_with($clean, $w)) {
                $wear = trim($w, ' ()'); // "Field-Tested"
                $clean = substr($clean, 0, -strlen($w)); // Отрезаем хвост
                break;
            }
        }

        // 2. Вытаскиваем префикс (StatTrak / Souvenir)
        if (str_contains($clean, 'StatTrak™ ')) {
            $prefix = 'StatTrak ';
            $clean = str_replace('StatTrak™ ', '', $clean);
        } elseif (str_contains($clean, 'Souvenir ')) {
            $prefix = 'Souvenir ';
            $clean = str_replace('Souvenir ', '', $clean);
        }

        // Удаляем звездочку (она часто у ножей или ST)
        $clean = str_replace('★ ', '', $clean);

        // 3. Собираем полную вариацию
        // "StatTrak " + "Field-Tested" = "StatTrak Field-Tested"
        // "" + "Factory New" = "Factory New"
        $fullVariation = trim($prefix . ($wear ?? ''));
        
        if ($fullVariation === '') $fullVariation = null;

        return [
            'clean' => $clean,      // ID ищем по этому
            'variation' => $fullVariation // В базу цен пишем это
        ];
    }
}