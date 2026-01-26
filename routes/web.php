<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ProfileController; // <--- Важно: подключили контроллер профиля
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Item;

// Главная редиректит на список инвентарей
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('inventories.index');
    }

    // Берем 100 случайных скинов с картинками для фона
    // cache() используем, чтобы не долбить базу при каждом F5 (кэш на 10 минут)
    $backgroundSkins = cache()->remember('welcome_skins', 600, function () {
        return Item::whereNotNull('image_url')
            ->where('price_skinport', '>', 5) // Берем скины дороже $5, чтобы были красивые, а не ширпотреб
            ->inRandomOrder()
            ->limit(100)
            ->get(['name', 'image_url', 'rarity_color']);
    });
    
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'backgroundSkins' => $backgroundSkins, // <-- Передаем скины
    ]);
})->name('welcome');

// Группа маршрутов для нашего приложения
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 1. Инвентари (CRUD)
    Route::resource('inventories', InventoryController::class);
    Route::get('/inventory/item/{id}', [InventoryController::class, 'itemDetails'])->name('inventories.item');
    Route::put('/inventory-item/{id}', [InventoryController::class, 'updateItem'])->name('inventory-items.update');
    
    // 2. Обновление цены покупки (из модалки)
    // Мы пока используем метод из DashboardController, но роут должен быть
    Route::put('/inventory-items/{id}', [DashboardController::class, 'update'])->name('inventory.update');

    // 3. Графики
    Route::get('/items/{id}/history', [DashboardController::class, 'history'])->name('items.history');

    // 4. Рынок и Вишлист
    Route::get('/market', [MarketController::class, 'index'])->name('market.index');
    Route::post('/market/{item}/wishlist', [MarketController::class, 'toggleWishlist'])->name('market.wishlist');
    Route::get('/wishlist', [MarketController::class, 'favorites'])->name('wishlist.index');

    Route::post('/inventories/{id}/refresh', [InventoryController::class, 'refresh'])->name('inventories.refresh');

    // Просмотр детализации предмета
    Route::get('/inventory-items/{id}', [InventoryController::class, 'itemDetails'])->name('inventory.item');
});

// --- ВЕРНУЛИ СТАНДАРТНЫЕ МАРШРУТЫ ПРОФИЛЯ ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Подключаем маршруты аутентификации (Логин, Регистрация, Выход)
require __DIR__.'/auth.php';