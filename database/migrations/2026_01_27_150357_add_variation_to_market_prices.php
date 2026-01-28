<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('market_prices', function (Blueprint $table) {
            // 1. Сначала сносим внешний ключ, чтобы он не держал индекс
            // (Laravel по умолчанию ищет ключ с именем market_prices_item_id_foreign)
            $table->dropForeign(['item_id']); 

            // 2. Теперь можно спокойно удалить уникальный индекс
            $table->dropUnique(['item_id', 'market_name']);
            
            // 3. Добавляем новую колонку
            $table->string('variation')->nullable()->after('market_name');
            
            // 4. Создаем новый составной ключ уникальности
            $table->unique(['item_id', 'market_name', 'variation']);
            
            // 5. Возвращаем внешний ключ на место
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('market_prices', function (Blueprint $table) {
            // Обратная процедура
            $table->dropForeign(['item_id']);
            $table->dropUnique(['item_id', 'market_name', 'variation']);
            $table->dropColumn('variation');
            
            $table->unique(['item_id', 'market_name']);
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }
};