<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

// 1. Обновление цен со всех маркетов (Skinport, мб Steam) - раз в час
Schedule::command('prices:update')
    ->hourly()
    ->runInBackground(); // Чтобы не тормозить другие задачи

// 2. Синхронизация DMarket (отдельно, так как там свои лимиты) - каждые 2 часа
Schedule::command('dmarket:sync')
    ->everyTwoHours()
    ->runInBackground();

// 3. Синхронизация инвентарей пользователей (Steam)
// Ставим каждые 30 минут, но с защитой от наложения (withoutOverlapping),
// чтобы если стим тупит и скрипт висит, не запускался второй такой же.
Schedule::command('skins:sync-steam')
    ->everyThirtyMinutes()
    ->withoutOverlapping(); 

// 4. Снапшот цен (для графиков истории)
// Делаем раз в день (или раз в 4 часа, если хочешь подробный график как на криптобирже)
// daily() = раз в сутки в 00:00
Schedule::command('prices:snapshot')->dailyAt('00:00');

// Опционально: Очистка старых логов или токенов, если нужно
// Schedule::command('auth:clear-resets')->everyFifteenMinutes();