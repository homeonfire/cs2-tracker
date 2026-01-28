<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    // ВАЖНО: Разрешаем заполнение новых полей
    protected $fillable = [
        'inventory_id',
        'item_id',
        'asset_id',
        'is_tradable',
        'buy_price',
        
        // Новые поля, которые мы добавили
        'wear_name',
        'is_stattrak',
        'is_souvenir',
        'float_value'
    ];

    // Связи
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}