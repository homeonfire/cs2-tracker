<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Collection;

class ContractService
{
    /**
     * Основной метод расчета контракта.
     * * @param array $inputs Массив из 10 элементов: ['item_id' => 1, 'float_value' => 0.15, 'price' => 10.5]
     */
    public function simulate(array $inputs)
    {
        // Валидация количества
        $count = count($inputs);
        
        // Загружаем предметы
        $itemIds = array_column($inputs, 'item_id');
        $dbItems = Item::whereIn('id', $itemIds)->with('collection')->get()->keyBy('id');

        // Проверяем редкость первого предмета
        if (empty($inputs)) throw new \Exception("Добавьте предметы.");
        $firstRarity = $dbItems[$inputs[0]['item_id']]->rarity_id;

        // --- ЛОГИКА КОЛИЧЕСТВА ---
        // Если это Красное (6) -> Нужно 5 штук (по твоему правилу)
        // Иначе -> Нужно 10 штук (стандарт CS2)
        if ($firstRarity == 6) {
            if ($count !== 5) throw new \Exception("Для крафта из Тайного (Red) нужно ровно 5 предметов.");
        } else {
            if ($count !== 10) throw new \Exception("Для стандартного контракта нужно 10 предметов.");
        }

        // --- СЧИТАЕМ СРЕДНИЕ ---
        $sumFloat = 0;
        $totalBuyPrice = 0;
        $collectionsCount = []; 

        foreach ($inputs as $input) {
            $item = $dbItems[$input['item_id']];
            
            if ($item->rarity_id !== $firstRarity) {
                throw new \Exception("Все предметы должны быть одной редкости.");
            }

            $sumFloat += $input['float_value'];
            $totalBuyPrice += $input['price'] ?? 0;

            if ($item->collection_id) {
                if (!isset($collectionsCount[$item->collection_id])) {
                    $collectionsCount[$item->collection_id] = 0;
                }
                $collectionsCount[$item->collection_id]++;
            }
        }

        $avgFloat = $sumFloat / $count; // Делим на 5 или на 10

        // --- ИЩЕМ РЕЗУЛЬТАТЫ ---
        $targetRarity = $firstRarity + 1; // 6 -> 7 (Gold), 3 -> 4 (Restricted) и т.д.
        $collectionIds = array_keys($collectionsCount);

        // Ищем возможный дроп
        $potentialOutputs = Item::whereIn('collection_id', $collectionIds)
            ->where('rarity_id', $targetRarity)
            ->with(['prices']) 
            ->get();

        if ($potentialOutputs->isEmpty()) {
            throw new \Exception("Не найдено предметов следующей редкости в этих коллекциях.");
        }

        $outcomes = [];
        $totalExpectedValue = 0;

        foreach ($potentialOutputs as $outputItem) {
            $colId = $outputItem->collection_id;
            
            // Шанс: (Инпутов этой коллекции / Всего инпутов) / (Аутпутов в этой коллекции)
            $inputsFromCol = $collectionsCount[$colId] ?? 0;
            $outputsInCol = $potentialOutputs->where('collection_id', $colId)->count();
            
            if ($outputsInCol === 0) continue;

            $probability = ($inputsFromCol / $count) / $outputsInCol;

            // Флоат
            $outcomeFloat = ($avgFloat * ($outputItem->max_float - $outputItem->min_float)) + $outputItem->min_float;
            $wearName = $this->getWearName($outcomeFloat);

            // Цена
            $price = 0;
            // Ищем точную цену (например Factory New)
            $priceObj = $outputItem->prices->where('market_name', 'dmarket')->where('variation', $wearName)->first();
            // Фолбэк на базовую
            if (!$priceObj) $priceObj = $outputItem->prices->where('market_name', 'dmarket')->whereNull('variation')->first();
            
            $price = $priceObj ? $priceObj->price : 0;

            // Матожидание
            $expectedValue = $price * $probability;
            $totalExpectedValue += $expectedValue;

            $outcomes[] = [
                'item' => $outputItem,
                'probability' => $probability * 100,
                'float_value' => $outcomeFloat,
                'wear_name' => $wearName,
                'price' => $price,
                'profit' => $price - $totalBuyPrice,
            ];
        }

        // Сортировка по шансу
        usort($outcomes, function ($a, $b) {
            return $b['probability'] <=> $a['probability'];
        });

        return [
            'inputs_cost' => $totalBuyPrice,
            'avg_float' => $avgFloat,
            'expected_value' => $totalExpectedValue,
            'expected_profit' => $totalExpectedValue - $totalBuyPrice,
            'roi' => $totalBuyPrice > 0 ? (($totalExpectedValue - $totalBuyPrice) / $totalBuyPrice) * 100 : 0,
            'outcomes' => $outcomes
        ];
    }

    private function getWearName($float)
    {
        if ($float < 0.07) return 'Factory New';
        if ($float < 0.15) return 'Minimal Wear';
        if ($float < 0.38) return 'Field-Tested';
        if ($float < 0.45) return 'Well-Worn';
        return 'Battle-Scarred';
    }
}