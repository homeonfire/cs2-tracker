<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Models\ProfitableContract;
use App\Services\ContractService;

class FindProfitableContracts extends Command
{
    protected $signature = 'contracts:find';
    protected $description = 'Ищет выгодные контракты (моно-стек 10 предметов) с положительным ROI';

    public function handle(ContractService $contractService)
    {
        $this->info("Запуск поиска выгодных контрактов (ROI > 0%)...");

        // 1. Очищаем таблицу
        ProfitableContract::truncate();

        // 2. Загружаем предметы (Mil-Spec, Restricted, Classified)
        // Исключаем ножи и ширпотреб, берем только те, что в коллекциях
        $items = Item::whereIn('rarity_id', [1, 2, 3, 4, 5]) 
            ->whereNotNull('collection_id')
            ->with('prices')
            ->get();

        $count = $items->count();
        if ($count === 0) {
            $this->error("Предметы не найдены.");
            return;
        }

        $this->info("Анализ {$count} предметов...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Стандартные флоаты для симуляции
        $wears = [
            'Factory New'    => 0.035,
            'Minimal Wear'   => 0.11,
            'Field-Tested'   => 0.25,
            'Well-Worn'      => 0.42,
            'Battle-Scarred' => 0.75,
        ];

        $foundCount = 0;

        foreach ($items as $item) {
            foreach ($wears as $wearName => $avgFloat) {
                // Пропускаем, если у скина нет такого флоата
                if ($avgFloat < $item->min_float || $avgFloat > $item->max_float) continue;

                // Ищем цену на DMarket для этого качества
                $priceObj = $item->prices->where('market_name', 'dmarket')
                    ->where('variation', $wearName)
                    ->first();
                
                // Пропускаем, если цены нет или она нулевая
                if (!$priceObj || $priceObj->price <= 0) continue;

                $singlePrice = $priceObj->price;

                // Собираем контракт из 10 одинаковых предметов
                $inputs = [];
                for ($i = 0; $i < 10; $i++) {
                    $inputs[] = [
                        'item_id' => $item->id,
                        'float_value' => $avgFloat,
                        'price' => $singlePrice
                    ];
                }

                try {
                    // Симулируем
                    $result = $contractService->simulate($inputs);

                    // СОХРАНЯЕМ ТОЛЬКО ЕСЛИ ROI > 0 (В ПЛЮС)
                    if ($result['roi'] > 0) {
                        ProfitableContract::create([
                            'input_item_id' => $item->id,
                            'wear_name' => $wearName,
                            'buy_price' => $singlePrice,
                            'avg_float' => $avgFloat,
                            'contract_cost' => $result['inputs_cost'],
                            'expected_value' => $result['expected_value'],
                            'profit' => $result['expected_profit'],
                            'roi' => $result['roi'],
                        ]);
                        $foundCount++;
                    }

                } catch (\Exception $e) {
                    // Игнорируем ошибки (например, неполные коллекции)
                    continue;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Готово! Найдено выгодных контрактов: {$foundCount}");
    }
}