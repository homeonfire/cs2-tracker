<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('profitable_contracts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('input_item_id')->constrained('items')->cascadeOnDelete(); // Какой предмет закупаем
        $table->string('wear_name'); // Какое качество ищем (FN, MW...)
        $table->decimal('buy_price', 10, 2); // Цена покупки 1 шт
        $table->double('avg_float'); // С каким флоатом искать
        $table->decimal('contract_cost', 10, 2); // Общая стоимость (x10)
        $table->decimal('expected_value', 10, 2); // Сколько получим в среднем
        $table->decimal('profit', 10, 2); // Чистая прибыль
        $table->decimal('roi', 8, 2); // Процент окупаемости
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profitable_contracts');
    }
};
