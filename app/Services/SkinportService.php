<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SkinportService
{
    // API Skinport
    private const URL = 'https://api.skinport.com/v1/items?app_id=730&currency=USD&tradable=0';

    public function fetchPrices(): array
    {
        try {
            // Исправление: Добавляем заголовки, чтобы притвориться браузером и принять сжатые данные
            $response = Http::withHeaders([
                'Accept-Encoding' => 'gzip, deflate, br', // Говорим: "Мы понимаем Brotli"
                'Content-Type' => 'application/json',
            ])->get(self::URL);

            if ($response->failed()) {
                Log::error('Skinport API Error: ' . $response->status());
                Log::error('Response: ' . $response->body()); // Логируем ответ для отладки
                return [];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Skinport Fetch Exception: ' . $e->getMessage());
            return [];
        }
    }
}