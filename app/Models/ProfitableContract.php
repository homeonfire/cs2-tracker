<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitableContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'input_item_id',
        'wear_name',
        'buy_price',
        'avg_float',
        'contract_cost',
        'expected_value',
        'profit',
        'roi',
    ];

    // Связь с предметом, чтобы выводить картинки и названия
    public function item()
    {
        return $this->belongsTo(Item::class, 'input_item_id');
    }
}