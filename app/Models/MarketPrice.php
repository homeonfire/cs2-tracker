<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
        protected $fillable = ['item_id', 'market_name', 'price', 'market_link'];
}
