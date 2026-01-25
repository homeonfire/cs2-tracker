<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\ItemPriceHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotPrices extends Command
{
    protected $signature = 'prices:snapshot';
    protected $description = 'Сохраняет текущие цены всех предметов в историю';

    public function handle()
    {
        $this->info('Начинаю создание снапшота цен...');

        // Берем только предметы с ценой > 0 (нет смысла хранить историю нулей)
        // Используем chunk, чтобы не забить память
        Item::chunkById(1000, function ($items) {
        $insertData = [];
        $now = now();

        foreach ($items as $item) {
                // 1. Запись для Skinport (если есть цена)
                if ($item->price_skinport > 0) {
                    $insertData[] = [
                        'item_id' => $item->id,
                        'price' => $item->price_skinport,
                        'source' => 'skinport', // <--- Тег источника
                        'created_at' => $now,
                    ];
                }

                // 2. Запись для DMarket (если есть цена)
                if ($item->price_dmarket > 0) {
                    $insertData[] = [
                        'item_id' => $item->id,
                        'price' => $item->price_dmarket,
                        'source' => 'dmarket', // <--- Тег источника
                        'created_at' => $now,
                    ];
                }
            }

            if (!empty($insertData)) {
                ItemPriceHistory::insert($insertData);
            }
        });

        $this->info('Снапшот успешно создан!');
    }
}