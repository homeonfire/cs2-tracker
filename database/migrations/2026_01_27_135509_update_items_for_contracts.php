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
        Schema::table('items', function (Blueprint $table) {
            // Для группировки в контрактах
            $table->string('collection')->nullable()->index(); // Название коллекции
            $table->string('rarity')->nullable()->index();     // Редкость (Mil-Spec, Restricted...)
            $table->integer('rarity_id')->nullable()->index(); // Числовой ID редкости (для удобства сортировки)
            
            // Критически важно для формулы контракта
            $table->decimal('min_float', 8, 7)->default(0)->nullable(); 
            $table->decimal('max_float', 8, 7)->default(1)->nullable();
            
            // Цена с CSFloat (в центах, для точности)
            $table->unsignedBigInteger('price_csfloat')->nullable(); 
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['collection', 'rarity', 'rarity_id', 'min_float', 'max_float', 'price_csfloat']);
        });
    }
};
