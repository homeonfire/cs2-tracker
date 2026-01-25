<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketController extends Controller
{
    // Главная страница Базы Скинов
    public function index(Request $request)
    {
        $query = Item::query();

        // Поиск
        if ($request->has('search')) {
            $query->where('market_hash_name', 'like', '%' . $request->search . '%');
        }

        // ВАЖНО: Добавляем поле is_favorite
        // Проверяем, авторизован ли пользователь, и есть ли связь в таблице item_user
        if ($request->user()) {
            $query->withExists(['users as is_favorite' => function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            }]);
        }

        $items = $query->orderBy('price_skinport', 'desc') // Или любая другая сортировка
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Market/Index', [
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    // Страница "Избранное"
    public function favorites(Request $request)
    {
        // Берем предметы ТОЛЬКО из вишлиста пользователя
        $items = $request->user()->wishlist()
            ->withExists(['users as is_favorite' => function ($q) use ($request) {
                $q->where('user_id', $request->user()->id); // Они все true, но нужно для фронта
            }])
            ->orderBy('item_user.created_at', 'desc') // Сортируем по дате добавления
            ->paginate(30);

        return Inertia::render('Market/Index', [
            'items' => $items,
            'filters' => [], // Поиск в избранном можно добавить позже
        ]);
    }

    // Действие: Добавить/Удалить
    public function toggleWishlist(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        
        // Метод toggle сам разберется: если есть - удалит, если нет - добавит
        $request->user()->wishlist()->toggle($item->id);

        return back();
    }
}