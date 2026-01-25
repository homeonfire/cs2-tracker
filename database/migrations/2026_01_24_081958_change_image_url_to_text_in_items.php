<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('items', function (Blueprint $table) {
        // Меняем тип с string (255) на text (65 000 символов)
        // nullable() оставляем, чтобы можно было хранить пустые значения
        $table->text('image_url')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('items', function (Blueprint $table) {
        // Если захотим отменить - вернем обратно (но это опасно, данные обрежутся)
        $table->string('image_url', 255)->nullable()->change();
    });
}
};
