<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'user_id',
        'steam_id',
        'name',
        'total_value',
    ];
    
    // Сразу добавим связь: У одного Инвентаря много Предметов
    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }
}