<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    backgroundSkins: Array,
});

// Разбиваем скины на 4 ряда
const rows = computed(() => {
    if (!props.backgroundSkins || props.backgroundSkins.length === 0) return [];
    
    // Дублируем исходный массив, чтобы скинов было много (минимум 20 в ряду), 
    // иначе на больших мониторах будет пустота
    let skins = [...props.backgroundSkins];
    while (skins.length < 80) {
        skins = [...skins, ...props.backgroundSkins];
    }

    const chunkSize = Math.ceil(skins.length / 4);
    return [
        skins.slice(0, chunkSize),
        skins.slice(chunkSize, chunkSize * 2),
        skins.slice(chunkSize * 2, chunkSize * 3),
        skins.slice(chunkSize * 3, skins.length),
    ];
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-[#0f1115] text-white overflow-hidden relative font-sans selection:bg-indigo-500 selection:text-white">
        
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none opacity-20 grayscale-[50%]">
            <div class="absolute -top-[30%] -left-[20%] w-[150%] h-[150%] flex flex-col gap-8 -rotate-6 scale-110">
                
                <div v-for="(row, i) in rows" :key="i" class="marquee-container flex gap-6">
                    
                    <div class="flex gap-6 shrink-0 items-center" 
                         :class="i % 2 === 0 ? 'animate-marquee-left' : 'animate-marquee-right'"
                         :style="{ animationDuration: (60 + i * 5) + 's' }"> <div v-for="(skin, idx) in row" :key="skin.id || idx" class="skin-card w-40 h-28 bg-[#1e2128] border border-gray-700/50 rounded-xl flex flex-col items-center justify-center p-2 shadow-lg relative overflow-hidden">
                            <div class="absolute inset-0 opacity-10" :style="{ background: `#${skin.rarity_color || '555'}` }"></div>
                            <img :src="skin.image_url" class="max-h-16 w-auto drop-shadow-md z-10 object-contain" loading="lazy">
                            <p class="text-[10px] text-gray-500 truncate w-full text-center mt-2 z-10 font-mono">{{ skin.name }}</p>
                        </div>
                    </div>

                    <div class="flex gap-6 shrink-0 items-center"
                         :class="i % 2 === 0 ? 'animate-marquee-left' : 'animate-marquee-right'"
                         :style="{ animationDuration: (60 + i * 5) + 's' }">
                        
                        <div v-for="(skin, idx) in row" :key="(skin.id || idx) + '_dup'" class="skin-card w-40 h-28 bg-[#1e2128] border border-gray-700/50 rounded-xl flex flex-col items-center justify-center p-2 shadow-lg relative overflow-hidden">
                            <div class="absolute inset-0 opacity-10" :style="{ background: `#${skin.rarity_color || '555'}` }"></div>
                            <img :src="skin.image_url" class="max-h-16 w-auto drop-shadow-md z-10 object-contain" loading="lazy">
                            <p class="text-[10px] text-gray-500 truncate w-full text-center mt-2 z-10 font-mono">{{ skin.name }}</p>
                        </div>
                    </div>

                </div>

            </div>
            
            <div class="absolute inset-0 bg-gradient-to-b from-[#0f1115] via-[#0f1115]/80 to-[#0f1115] z-10"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-[#0f1115] via-transparent to-[#0f1115] z-10"></div>
        </div>
        <nav class="relative z-20 max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="font-black text-xl tracking-tight">CS2<span class="text-indigo-500">TRACKER</span></span>
            </div>

            <div v-if="canLogin" class="flex gap-4">
                <Link v-if="$page.props.auth.user" :href="route('inventories.index')" class="text-sm font-bold text-gray-300 hover:text-white transition">
                    Мои Портфели →
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="text-sm font-bold text-gray-300 hover:text-white transition py-2 px-4">
                        Войти
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="text-sm font-bold bg-white text-black px-5 py-2 rounded-full hover:bg-gray-200 transition shadow-lg shadow-white/10">
                        Регистрация
                    </Link>
                </template>
            </div>
        </nav>

        <div class="relative z-20 max-w-7xl mx-auto px-6 py-16 lg:py-24 flex flex-col items-center text-center">
            
             <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-bold uppercase tracking-wider mb-8 animate-fade-in-up shadow-[0_0_15px_rgba(16,185,129,0.1)] backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Доступно Бесплатно для Всех
            </div>

            <h1 class="text-5xl md:text-7xl font-black tracking-tight mb-6 leading-tight max-w-5xl drop-shadow-2xl">
                Трекай инвентарь CS2 <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-200 to-cyan-400">без подписок и оплат</span>
            </h1>

            <p class="text-lg md:text-xl text-gray-400 max-w-2xl mb-10 leading-relaxed drop-shadow-md">
                Профессиональная аналитика, расчет прибыли (PnL), база скинов и мульти-аккаунтинг.
                Я сделал этот инструмент бесплатным для комьюнити.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto mb-16">
                <Link :href="route('register')" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-lg transition shadow-[0_0_30px_rgba(5,150,105,0.3)] hover:shadow-[0_0_40px_rgba(5,150,105,0.5)] transform hover:-translate-y-1">
                    Создать аккаунт
                </Link>
                <Link :href="route('market.index')" class="px-8 py-4 bg-[#1e2128]/80 backdrop-blur border border-gray-700 hover:border-gray-500 text-white rounded-xl font-bold text-lg transition flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    База Скинов
                </Link>
            </div>

            <div class="flex flex-col items-center border-t border-white/10 pt-10 w-full max-w-2xl backdrop-blur-sm bg-black/20 p-6 rounded-2xl">
                <p class="text-gray-400 text-sm uppercase font-bold tracking-widest mb-6">Следи за разработкой и стримами</p>
                <div class="flex flex-wrap gap-6 justify-center">
                    
                     <a href="https://twitch.tv/ТВОЙ_КАНАЛ" target="_blank" class="group flex items-center gap-3 bg-[#6441a5]/10 border border-[#6441a5]/30 hover:bg-[#6441a5] px-6 py-3 rounded-xl transition-all duration-300">
                        <svg class="w-6 h-6 text-[#6441a5] group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                             <path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/>
                        </svg>
                        <span class="font-bold text-[#a991d4] group-hover:text-white transition-colors">Twitch</span>
                    </a>

                    <a href="https://t.me/ТВОЙ_КАНАЛ" target="_blank" class="group flex items-center gap-3 bg-[#229ED9]/10 border border-[#229ED9]/30 hover:bg-[#229ED9] px-6 py-3 rounded-xl transition-all duration-300">
                        <svg class="w-6 h-6 text-[#229ED9] group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                        <span class="font-bold text-[#64c2eb] group-hover:text-white transition-colors">Telegram</span>
                    </a>

                </div>
            </div>
        </div>

        <div class="relative z-20 max-w-7xl mx-auto px-6 pb-20">
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-[#131519]/80 backdrop-blur p-8 rounded-3xl border border-white/5 hover:border-emerald-500/30 transition group">
                    <div class="w-12 h-12 bg-emerald-900/50 rounded-2xl flex items-center justify-center mb-6 text-emerald-400 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">100% Бесплатно</h3>
                    <p class="text-gray-400 leading-relaxed">Никаких скрытых платежей или Premium подписок. Полный доступ ко всем функциям трекера сразу после регистрации.</p>
                </div>
                <div class="bg-[#131519]/80 backdrop-blur p-8 rounded-3xl border border-white/5 hover:border-indigo-500/30 transition group">
                    <div class="w-12 h-12 bg-indigo-900/50 rounded-2xl flex items-center justify-center mb-6 text-indigo-400 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Аналитика PnL</h3>
                    <p class="text-gray-400 leading-relaxed">Считай прибыль с каждого скина. Узнай свой точный ROI и общую стоимость инвентаря в реальном времени.</p>
                </div>
                <div class="bg-[#131519]/80 backdrop-blur p-8 rounded-3xl border border-white/5 hover:border-pink-500/30 transition group">
                    <div class="w-12 h-12 bg-pink-900/50 rounded-2xl flex items-center justify-center mb-6 text-pink-400 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Вишлист Мечты</h3>
                    <p class="text-gray-400 leading-relaxed">База из 20,000+ скинов. Ищи, добавляй в избранное и следи за ценами, чтобы купить на просадке.</p>
                </div>
            </div>
        </div>

    </div>
</template>

<style>
/* БЕСКОНЕЧНАЯ ПРОКРУТКА
    Логика: сдвигаем ряд влево на 100% его ширины + ширина отступа (gap).
    calc(-100% - 1.5rem), где 1.5rem это gap-6.
*/
@keyframes marquee-left {
    from { transform: translateX(0); }
    to { transform: translateX(calc(-100% - 1.5rem)); }
}

@keyframes marquee-right {
    from { transform: translateX(calc(-100% - 1.5rem)); }
    to { transform: translateX(0); }
}

.animate-marquee-left {
    animation-name: marquee-left;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}

.animate-marquee-right {
    animation-name: marquee-right;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}
</style>