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
}