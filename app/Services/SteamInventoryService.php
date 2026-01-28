<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteamInventoryService
{
    private const BASE_URL = 'https://steamcommunity.com/inventory/%s/730/2?l=english&count=2000';

    public function fetchInventory(string $steamId)
    {
        $url = sprintf(self::BASE_URL, $steamId);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
            ])->get($url);

            if ($response->failed()) {
                Log::error('Steam API Error: ' . $response->status());
                return null;
            }

            $data = $response->json();

            if (!isset($data['assets']) || !isset($data['descriptions'])) {
                return null;
            }

            return $this->formatItems($data);

        } catch (\Exception $e) {
            Log::error('Steam Fetch Exception: ' . $e->getMessage());
            return null;
        }
    }

    private function formatItems(array $data): array
    {
        $descriptions = [];
        foreach ($data['descriptions'] as $desc) {
            $key = $desc['classid'] . '_' . ($desc['instanceid'] ?? '0');
            $descriptions[$key] = $desc;
        }

        $inventory = [];

        foreach ($data['assets'] as $asset) {
            $key = $asset['classid'] . '_' . ($asset['instanceid'] ?? '0');
            
            if (isset($descriptions[$key])) {
                $desc = $descriptions[$key];
                
                // --- ГЛАВНОЕ ИЗМЕНЕНИЕ ---
                // Парсим имя, чтобы отделить качество от названия
                $parsed = $this->parseItemName($desc['market_hash_name']);
                // -------------------------

                $inventory[] = [
                    'asset_id' => $asset['assetid'],
                    
                    // Сохраняем и оригинал (для поиска), и чистое имя (для базы)
                    'market_hash_name' => $desc['market_hash_name'], // "AK-47 | Asiimov (Field-Tested)"
                    'clean_name' => $parsed['clean_name'],           // "AK-47 | Asiimov"
                    
                    // Метаданные качества для таблицы inventory_items
                    'wear_name' => $parsed['wear_name'],             // "Field-Tested"
                    'is_stattrak' => $parsed['is_stattrak'],
                    'is_souvenir' => $parsed['is_souvenir'],

                    'image' => 'https://community.cloudflare.steamstatic.com/economy/image/' . $desc['icon_url'],
                    'is_tradable' => $desc['tradable'],
                    'rarity_color' => $this->extractColor($desc['tags'] ?? [])
                ];
            }
        }

        return $inventory;
    }

    private function extractColor(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if ($tag['category'] === 'Rarity') {
                return $tag['color'] ?? null;
            }
        }
        return null;
    }

    /**
     * Превращает "StatTrak™ AK-47 | Asiimov (Field-Tested)" -> "AK-47 | Asiimov"
     */
    private function parseItemName($marketHashName)
    {
        $cleanName = $marketHashName;
        $wear = null;
        $isStattrak = false;
        $isSouvenir = false;

        $wears = [
            ' (Factory New)', 
            ' (Minimal Wear)', 
            ' (Field-Tested)', 
            ' (Well-Worn)', 
            ' (Battle-Scarred)',
            ' (Not Painted)'
        ];

        foreach ($wears as $w) {
            if (str_ends_with($cleanName, $w)) {
                $wear = trim($w, ' ()');
                $cleanName = substr($cleanName, 0, -strlen($w));
                break;
            }
        }

        if (str_contains($cleanName, 'StatTrak™ ')) {
            $isStattrak = true;
            $cleanName = str_replace('StatTrak™ ', '', $cleanName);
        }

        if (str_contains($cleanName, 'Souvenir ')) {
            $isSouvenir = true;
            $cleanName = str_replace('Souvenir ', '', $cleanName);
        }

        // Удаляем Star для ножей (★ StatTrak™ Karambit...)
        $cleanName = str_replace('★ ', '', $cleanName);

        return [
            'clean_name' => $cleanName,
            'wear_name' => $wear,
            'is_stattrak' => $isStattrak,
            'is_souvenir' => $isSouvenir
        ];
    }
}