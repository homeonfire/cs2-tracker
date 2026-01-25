<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('items', function (Blueprint $table) {
        $table->id();
        // market_hash_name - это уникальное имя предмета в Steam (напр. "AK-47 | Redline (Field-Tested)")
        // Делаем его уникальным индексом для быстрого поиска
        $table->string('market_hash_name')->unique(); 
        
        $table->string('name'); // Просто название "AK-47 | Redline"
        $table->string('image_url')->nullable(); // Ссылка на картинку
        $table->string('rarity_color')->nullable(); // Цвет редкости (для красивых рамок в UI)
        
        // Кэшированные текущие цены (чтобы не искать в истории каждый раз)
        $table->decimal('price_steam', 10, 2)->nullable();
        $table->decimal('price_skinport', 10, 2)->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
