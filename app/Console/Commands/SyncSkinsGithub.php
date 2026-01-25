<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool; // <--- ВОТ ЗДЕСЬ БЫЛА ОШИБКА (исправили)

class SyncSkinsGithub extends Command
{
    protected $signature = 'skins:sync-github';
    protected $description = 'Скачивает полные базы картинок (включая медали) с GitHub';

    public function handle()
    {
        $this->info('Начинаю загрузку баз с GitHub (ByMykel API)...');

        // 1. Качаем параллельно 3 базы: Скины, Предметы (Медали) и Агенты
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('skins')->get('https://raw.githubusercontent.com/ByMykel/CSGO-API/main/public/api/en/skins.json'),
            $pool->as('collectibles')->get('https://raw.githubusercontent.com/ByMykel/CSGO-API/main/public/api/en/collectibles.json'),
            $pool->as('agents')->get('https://raw.githubusercontent.com/ByMykel/CSGO-API/main/public/api/en/agents.json'),
        ]);

        if ($responses['skins']->failed() || $responses['collectibles']->failed()) {
            $this->error('Ошибка при скачивании файлов с GitHub.');
            return;
        }

        $this->info('Файлы скачаны. Начинаю обработку...');

        $allItems = array_merge(
            $responses['skins']->json(), 
            $responses['collectibles']->json(),
            $responses['agents']->json()
        );

        $total = count($allItems);
        $this->info("Всего найдено предметов: $total");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $chunks = array_chunk($allItems, 1000);

        foreach ($chunks as $chunk) {
            $upsertData = [];

            foreach ($chunk as $item) {
                $marketHashName = $item['name']; 
                $imageUrl = $item['image'] ?? null;
                $rarity = $item['rarity']['color'] ?? null;

                if (!$imageUrl) continue;

                $upsertData[] = [
                    'market_hash_name' => $marketHashName,
                    'name' => $marketHashName,
                    'image_url' => $imageUrl,
                    'rarity_color' => $rarity ? ltrim($rarity, '#') : 'b0c3d9',
                    'updated_at' => now(),
                    'created_at' => now(), 
                ];
            }

            if (!empty($upsertData)) {
                Item::upsert(
                    $upsertData, 
                    ['market_hash_name'], 
                    ['image_url', 'rarity_color', 'updated_at']
                );
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info('Готово! Картинки для медалей, ножей и скинов обновлены.');
    }
}