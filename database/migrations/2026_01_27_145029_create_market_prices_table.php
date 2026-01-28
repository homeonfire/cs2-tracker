<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            
            // Название маркета: 'dmarket', 'skinport', 'steam', 'buff'
            // Можно сделать отдельную таблицу marketplaces, но для начала string пойдет
            $table->string('market_name', 50)->index();
            
            // Цена в долларах
            $table->decimal('price', 10, 2);
            
            // Ссылка на товар (прямо на маркет), пригодится для кнопки "Купить"
            $table->string('market_link')->nullable(); 
            
            $table->timestamps();

            // Уникальный ключ: у одного предмета на одном маркете может быть только ОДНА текущая цена
            $table->unique(['item_id', 'market_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('market_prices');
    }
};