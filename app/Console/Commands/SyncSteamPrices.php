<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncSteamPrices extends Command
{
    protected $signature = 'skins:sync-steam';
    protected $description = 'Парсит цены Steam Community Market через CSGOBackpack';

    public function handle()
    {
        $this->info('Скачиваю цены Steam...');

        // API CSGOBackpack (v2)
        $response = Http::get('http://csgobackpack.net/api/GetItemPriceList/v2/', [
            'currency' => 'USD',
            'icon' => 0,
            'details' => 0
        ]);

        if ($response->failed()) {
            $this->error('Ошибка API CSGOBackpack');
            return;
        }

        $data = $response->json();
        
        if (!$data['success']) {
            $this->error('API вернул ошибку.');
            return;
        }

        $items = $data['items_list'];
        $total = count($items);
        $this->info("Получено цен: $total");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Разбиваем на чанки для скорости
        $chunkSize = 1000;
        $updates = [];
        
        foreach ($items as $name => $details) {
            // Если цены нет или она странная, пропускаем
            if (!isset($details['price']['24_hours']['average'])) continue;
            
            $price = $details['price']['24_hours']['average'];
            
            // Собираем массив для обновления
            $updates[] = [
                'market_hash_name' => $name,
                'price_steam' => $price
            ];

            // Когда набрали 1000 штук - заливаем
            if (count($updates) >= $chunkSize) {
                $this->processBatch($updates);
                $updates = [];
            }
            
            $bar->advance();
        }

        // Заливаем остатки
        if (!empty($updates)) {
            $this->processBatch($updates);
        }

        $bar->finish();
        $this->newLine();
        $this->info('Цены Steam успешно обновлены!');
    }

    private function processBatch($updates)
    {
        // Используем upsert только для обновления поля price_steam
        // Важно: мы предполагаем, что предметы уже созданы через skins:sync
        // Если предмета нет в нашей базе, мы его пока игнорируем (или можно создавать)
        
        Item::upsert(
            $updates, 
            ['market_hash_name'], 
            ['price_steam']
        );
    }
}