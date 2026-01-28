<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['name', 'image'];

    // Все предметы этой коллекции
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}