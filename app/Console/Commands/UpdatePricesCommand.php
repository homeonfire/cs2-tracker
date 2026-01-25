<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SkinportService;
use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Support\Facades\DB;

class UpdatePricesCommand extends Command
{
    // Имя команды, которое мы будем писать в терминале
    protected $signature = 'prices:update';

    // Описание команды
    protected $description = 'Download prices from Skinport and update local database';

    public function handle(SkinportService $skinportService)
    {
        $this->info('Starting Skinport sync...');

        // 1. Качаем данные
        $this->info('Downloading data from Skinport API...');
        $items = $skinportService->fetchPrices();
        
        if (empty($items)) {
            $this->error('Failed to fetch data!');
            return;
        }

        $count = count($items);
        $this->info("Fetched {$count} items. Processing...");

        // 2. Запускаем прогресс-бар
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($items as $apiItem) {
            // transaction нужен, чтобы если что-то сломается на середине записи, база не побилась
            DB::transaction(function () use ($apiItem) {
                
                // Ищем предмет по имени. Если нет - создаем. Если есть - обновляем.
                // updateOrCreate( [условия поиска], [что обновить] )
                $item = Item::updateOrCreate(
                    ['market_hash_name' => $apiItem['market_hash_name']], 
                    [
                        'name' => $apiItem['market_hash_name'], // Skinport дает только hash_name, используем его как имя
                        'price_skinport' => $apiItem['min_price'] ?? null,
                        'price_steam' => $apiItem['suggested_price'] ?? null, // Skinport иногда дает и цену стима
                        // image_url мы обновим позже, когда получим инвентарь стима, 
                        // так как у скинпорта картинки иногда отличаются
                    ]
                );

                // Записываем историю цены для графика (только если цена есть)
                if ($apiItem['min_price']) {
                    ItemPrice::create([
                        'item_id' => $item->id,
                        'price' => $apiItem['min_price'],
                        'source' => 'skinport',
                        'recorded_at' => now(),
                    ]);
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Successfully updated prices!');
    }
}