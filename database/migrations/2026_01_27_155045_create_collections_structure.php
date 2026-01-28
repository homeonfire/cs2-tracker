<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Безопасное создание таблицы коллекций
        if (!Schema::hasTable('collections')) {
            Schema::create('collections', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }

        // 2. Безопасное добавление полей в таблицу items
        Schema::table('items', function (Blueprint $table) {
            
            // Добавляем collection_id только если его нет
            if (!Schema::hasColumn('items', 'collection_id')) {
                $table->foreignId('collection_id')->nullable()->constrained()->onDelete('set null');
            }
            
            // Добавляем min_float только если его нет
            if (!Schema::hasColumn('items', 'min_float')) {
                $table->decimal('min_float', 5, 4)->default(0); 
            }

            // Добавляем max_float только если его нет
            if (!Schema::hasColumn('items', 'max_float')) {
                $table->decimal('max_float', 5, 4)->default(1); 
            }
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'collection_id')) {
                // Сначала удаляем внешний ключ (нужно знать его имя, Laravel генерирует items_collection_id_foreign)
                // Чтобы не гадать, используем массив для dropForeign
                $table->dropForeign(['collection_id']);
                $table->dropColumn('collection_id');
            }
            
            if (Schema::hasColumn('items', 'min_float')) {
                $table->dropColumn('min_float');
            }

            if (Schema::hasColumn('items', 'max_float')) {
                $table->dropColumn('max_float');
            }
        });

        Schema::dropIfExists('collections');
    }
};