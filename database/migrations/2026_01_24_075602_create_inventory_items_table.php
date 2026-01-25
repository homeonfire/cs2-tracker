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
    Schema::create('inventory_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
        // Связь с глобальным каталогом (чтобы знать картинку и текущую цену)
        $table->foreignId('item_id')->constrained('items');
        
        $table->string('asset_id'); // Уникальный ID конкретного предмета в Steam (чтобы не дублировать)
        $table->boolean('is_tradable')->default(true); // Можно ли трейдить
        
        // Цена покупки (вводит юзер или берется автоматически, если мы парсим историю)
        $table->decimal('buy_price', 10, 2)->nullable(); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
