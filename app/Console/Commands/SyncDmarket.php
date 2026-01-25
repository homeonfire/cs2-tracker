<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncDmarket extends Command
{
    protected $signature = 'dmarket:sync';
    protected $description = 'Парсинг цен с DMarket';

    public function handle()
    {
        $this->info('Запрашиваю цены DMarket...');

        $offset = 0;
        $limit = 10000; // Максимум, который отдает DMarket за раз
        $totalProcessed = 0;
        $keepFetching = true;

        // Бесконечный цикл, пока API отдает данные
        do {
            $this->comment("Загрузка страницы: Offset $offset...");

            $response = Http::get('https://api.dmarket.com/price-aggregator/v1/aggregated-prices', [
                'AppID' => 730,
                'Limit' => $limit,
                'Offset' => $offset // <--- ВОТ ЭТОГО НЕ ХВАТАЛО
            ]);

            if ($response->failed()) {
                $this->error('Ошибка API DMarket. Прерываемся.');
                break;
            }

            $data = $response->json();
            $itemsList = $data['AggregatedTitles'] ?? [];
            $count = count($itemsList);

            if ($count === 0) {
                $this->info('Данные закончились.');
                $keepFetching = false;
                break;
            }

            // Обработка пачки
            $updates = [];
            foreach ($itemsList as $item) {
                $name = $item['MarketHashName'];
                
                // Берем лучшую цену предложения
                $price = isset($item['Offers']['BestPrice']) 
                    ? (float) $item['Offers']['BestPrice'] 
                    : 0;

                if ($price <= 0) continue;

                $updates[] = [
                    'market_hash_name' => $name,
                    'price_dmarket' => $price,
                    'name' => $name, 
                ];
            }

            // Заливаем в базу
            if (!empty($updates)) {
                $this->upsertBatch($updates);
            }

            $totalProcessed += $count;
            $this->info("Обработано: $totalProcessed предметов.");

            // Готовимся к следующей странице
            $offset += $limit;

            // Если API вернул меньше лимита, значит это была последняя страница
            if ($count < $limit) {
                $keepFetching = false;
            }

            // Маленькая пауза, чтобы не получить бан по IP (429 Too Many Requests)
            sleep(1); 

        } while ($keepFetching);

        $this->newLine();
        $this->info("Успешно! Всего обновлено цен DMarket: $totalProcessed");
    }

    private function upsertBatch($updates)
    {
        // Разбиваем на чанки по 1000 для базы данных, 
        // так как upsert 10000 записей за раз может вызвать ошибку SQL (placeholders limit)
        foreach (array_chunk($updates, 1000) as $chunk) {
            Item::upsert(
                $chunk,
                ['market_hash_name'],
                ['price_dmarket']
            );
        }
    }
}