<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('item_price_histories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('item_id')->constrained()->cascadeOnDelete(); // Связь с предметом
        $table->decimal('price', 10, 2); // Цена в этот момент
        $table->timestamp('created_at')->useCurrent(); // Когда был сделан снимок

        // updated_at нам не нужен, история неизменна
        $table->timestamp('updated_at')->nullable(); 

        // Индекс для ускорения графиков
        $table->index(['item_id', 'created_at']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_price_histories');
    }
};
