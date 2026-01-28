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
    Schema::table('inventory_items', function (Blueprint $table) {
        // Храним строку типа "Factory New", "Field-Tested"
        $table->string('wear_name')->nullable()->after('item_id');
        // Флаги, чтобы не создавать отдельные Items под StatTrak
        $table->boolean('is_stattrak')->default(false);
        $table->boolean('is_souvenir')->default(false);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            //
        });
    }
};
