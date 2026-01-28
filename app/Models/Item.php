<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'market_hash_name', 'name', 'image_url', 'rarity_color', 'is_tradable',
        'price_skinport', 'price_dmarket', 'price_steam','rarity_id', 'collection_id', 'min_float', 'max_float',
    ];

    protected $casts = [
        'is_tradable' => 'boolean',
        'price_skinport' => 'float',
        'price_dmarket' => 'float',
        'price_steam' => 'float',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'item_user')->withTimestamps();
    }

    protected $appends = ['min_price'];

    public function getMinPriceAttribute()
    {
        $prices = [
            $this->price_skinport,
            $this->price_dmarket,
            $this->price_steam, 
        ];

        $validPrices = array_filter($prices, function($price) {
            return $price > 0;
        });

        if (empty($validPrices)) {
            return 0;
        }

        // floatval гарантирует число
        return floatval(min($validPrices));
    }

    // Связь с текущими ценами
    public function prices()
    {
        return $this->hasMany(MarketPrice::class);
    }

    // Хелпер, чтобы быстро получить лучшую цену
    public function getBestPriceAttribute()
    {
        // Можно оптимизировать, но логика такая:
        return $this->prices->min('price');
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}