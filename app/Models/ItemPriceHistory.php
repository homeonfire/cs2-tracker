<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPriceHistory extends Model
{
    protected $fillable = ['item_id', 'price', 'created_at'];
}