<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPrice extends Model
{
    // Отключаем автоматическое обновление полей created_at/updated_at, 
    // если хотим контролировать время записи сами (но в миграции мы их добавили, так что можно оставить).
    // Главное - fillable.
    protected $fillable = [
        'item_id',
        'price',
        'source',
        'recorded_at',
    ];
}