<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Добавляем цену DMarket (если её нет)
        if (!Schema::hasColumn('items', 'price_dmarket')) {
            Schema::table('items', function (Blueprint $table) {
                $table->decimal('price_dmarket', 10, 2)->default(0)->after('price_skinport');
            });
        }

        // 2. Обновляем таблицу истории
        Schema::table('item_price_histories', function (Blueprint $table) {
            
            // Сначала удаляем внешний ключ и индекс.
            // Оборачиваем в try-catch, так как MySQL не умеет делать "DROP IF EXISTS" для внешних ключей красиво,
            // а если миграция падала на полпути, ключа может уже не быть.
            try {
                $table->dropForeign(['item_id']);
            } catch (\Exception $e) {
                // Игнорируем, если ключа уже нет
            }

            try {
                $table->dropIndex(['item_id', 'created_at']);
            } catch (\Exception $e) {
                // Игнорируем, если индекса уже нет
            }

            // Добавляем новую колонку source
            if (!Schema::hasColumn('item_price_histories', 'source')) {
                $table->string('source')->default('skinport')->after('item_id');
            }
            
            // Создаем новый правильный индекс
            // Используем короткое имя, чтобы не превысить лимит длины имени в MySQL
            $table->index(['item_id', 'source', 'created_at'], 'price_hist_composite_index');
            
            // Возвращаем внешний ключ на место
            $table->foreign('item_id')
                  ->references('id')
                  ->on('items')
                  ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'price_dmarket')) {
                $table->dropColumn('price_dmarket');
            }
        });

        Schema::table('item_price_histories', function (Blueprint $table) {
            // Удаляем новые ключи
            try {
                $table->dropForeign(['item_id']);
                $table->dropIndex('price_hist_composite_index');
            } catch (\Exception $e) {}

            if (Schema::hasColumn('item_price_histories', 'source')) {
                $table->dropColumn('source');
            }

            // Восстанавливаем старые (если нужно откатить)
            $table->index(['item_id', 'created_at']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }
};