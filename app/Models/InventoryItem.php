<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'inventory_id',
        'item_id',
        'asset_id',
        'is_tradable',
        'buy_price',
    ];

    // Связь с глобальным предметом (была)
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // --- ДОБАВЬ ВОТ ЭТОТ МЕТОД ---
    // Связь с родительским инвентарем (нужна для проверки владельца)
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}