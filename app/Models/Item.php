<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    // 1. Разрешаем массовое заполнение этих полей
    protected $fillable = [
        'market_hash_name',
        'name',
        'image_url',
        'rarity_color',
        'is_tradable',
        'price_skinport', // Цена Skinport
        'price_dmarket',  // Цена DMarket
        'price_steam',    // Цена Steam
    ];

    // 2. Автоматически превращаем цены в числа (Float)
    // Теперь во Vue они придут как 12.50, а не "12.50"
    protected $casts = [
        'is_tradable' => 'boolean',
        'price_skinport' => 'float',
        'price_dmarket' => 'float',
        'price_steam' => 'float',
    ];

    // --- ДОБАВЬ ЭТОТ МЕТОД ---
    // Связь: У одного предмета может быть много записей в вишлистах (у разных людей)
    // Связь: Пользователи, добавившие этот предмет в избранное
    public function users()
    {
        return $this->belongsToMany(User::class, 'item_user')->withTimestamps();
    }

    // 1. Добавляем это поле, чтобы оно всегда отправлялось на фронтенд
    protected $appends = ['min_price'];

    // 2. Создаем логику вычисления минимальной цены
    public function getMinPriceAttribute()
    {
        // Собираем все цены в массив
        $prices = [
            $this->price_skinport,
            $this->price_dmarket,
            $this->price_steam, // Steam обычно дороже, но если он единственный - берем его
        ];

        // Фильтруем: убираем null и 0 (чтобы не показывать $0.00 как цену)
        $validPrices = array_filter($prices, function($price) {
            return $price > 0;
        });

        // Если цен нет вообще — возвращаем 0, иначе минимальную
        if (empty($validPrices)) {
            return 0;
        }

        return min($validPrices);
    }
}