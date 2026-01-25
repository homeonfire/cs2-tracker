<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteamInventoryService
{
    // Базовый URL для получения инвентаря CS2 (AppID: 730, ContextID: 2)
    // l=english важно для получения английских названий (Market Hash Name)
    // count=5000 чтобы получить максимум предметов за раз
    private const BASE_URL = 'https://steamcommunity.com/inventory/%s/730/2?l=english&count=2000';

    public function fetchInventory(string $steamId)
    {
        $url = sprintf(self::BASE_URL, $steamId);

        try {
            // Делаем запрос к Steam
            // Важно: Steam часто блокирует запросы без User-Agent
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br', // Сжатие Brotli для экономии трафика
            ])->get($url);

            if ($response->failed()) {
                Log::error('Steam API Error: ' . $response->status());
                return null;
            }

            $data = $response->json();

            // Проверяем, вернул ли Steam данные
            if (!isset($data['assets']) || !isset($data['descriptions'])) {
                return null; // Инвентарь пуст или скрыт
            }

            return $this->formatItems($data);

        } catch (\Exception $e) {
            Log::error('Steam Fetch Exception: ' . $e->getMessage());
            return null;
        }
    }

    // Steam отдает данные странно: отдельно список предметов (assets) и отдельно их описания (descriptions).
    // Нам нужно их склеить ("смерджить").
    private function formatItems(array $data): array
    {
        $descriptions = [];
        
        // Создаем карту описаний для быстрого поиска
        foreach ($data['descriptions'] as $desc) {
            // Ключ - это комбинация classid и instanceid
            $key = $desc['classid'] . '_' . ($desc['instanceid'] ?? '0');
            $descriptions[$key] = $desc;
        }

        $inventory = [];

        foreach ($data['assets'] as $asset) {
            $key = $asset['classid'] . '_' . ($asset['instanceid'] ?? '0');
            
            if (isset($descriptions[$key])) {
                $desc = $descriptions[$key];
                
                // Пропускаем непередаваемые предметы (медали, монеты), если нужно
                // if ($desc['tradable'] == 0) continue;

                $inventory[] = [
                    'asset_id' => $asset['assetid'],
                    'market_hash_name' => $desc['market_hash_name'],
                    'name' => $desc['name'], // Обычное имя
                    'image' => 'https://community.cloudflare.steamstatic.com/economy/image/' . $desc['icon_url'],
                    'is_tradable' => $desc['tradable'],
                    'rarity_color' => $this->extractColor($desc['tags'] ?? [])
                ];
            }
        }

        return $inventory;
    }

    // Вспомогательная функция для поиска цвета редкости в тегах
    private function extractColor(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if ($tag['category'] === 'Rarity') {
                return $tag['color'] ?? null;
            }
        }
        return null; // Серый по умолчанию
    }
}