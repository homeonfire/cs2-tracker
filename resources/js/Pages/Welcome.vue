<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    backgroundSkins: Array,
});

// Logic for the background marquee (Preserved because it's cool)
const rows = computed(() => {
    if (!props.backgroundSkins || props.backgroundSkins.length === 0) return [];
    
    let skins = [...props.backgroundSkins];
    while (skins.length < 60) { // Optimization: Reduced count slightly for performance
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
    <Head>
        <title>CS2 Tracker — Трекер Инвентаря, Аналитика PnL и Цены Скинов</title>
        <meta name="description" content="Бесплатный трекер инвентаря CS2. Следите за стоимостью скинов, считайте прибыль (PnL/ROI) и анализируйте рынок Steam, Skinport и DMarket в реальном времени.">
        <meta name="keywords" content="CS2 инвентарь, калькулятор скинов, оценка инвентаря, PnL трекер, инвестиции CS2, цены на скины, Steam аналитика">
    </Head>

    <div class="min-h-screen bg-[#090a0c] text-white overflow-x-hidden relative font-sans selection:bg-emerald-500/30 selection:text-emerald-200">
        
        <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 bg-[#090a0c] z-10 opacity-90"></div> <div class="absolute -top-[20%] -left-[20%] w-[140%] h-[140%] flex flex-col gap-6 -rotate-6 opacity-30 grayscale-[70%]">
                <div v-for="(row, i) in rows" :key="i" class="flex gap-6 shrink-0 items-center" 
                     :class="i % 2 === 0 ? 'animate-marquee-left' : 'animate-marquee-right'"
                     :style="{ animationDuration: (80 + i * 10) + 's' }">
                    
                    <div v-for="(skin, idx) in row" :key="idx" class="w-32 h-24 bg-[#15171c] border border-white/5 rounded-lg flex items-center justify-center p-2 relative overflow-hidden">
                        <div class="absolute inset-0 opacity-20" :style="{ background: `#${skin.rarity_color || '333'}` }"></div>
                        <img :src="skin.image_url" class="max-h-14 w-auto drop-shadow-lg z-10" loading="lazy" alt="CS2 Skin">
                    </div>
                    <div v-for="(skin, idx) in row" :key="idx + '_d'" class="w-32 h-24 bg-[#15171c] border border-white/5 rounded-lg flex items-center justify-center p-2 relative overflow-hidden">
                        <div class="absolute inset-0 opacity-20" :style="{ background: `#${skin.rarity_color || '333'}` }"></div>
                        <img :src="skin.image_url" class="max-h-14 w-auto drop-shadow-lg z-10" loading="lazy" alt="CS2 Skin">
                    </div>
                </div>
            </div>
            
            <div class="absolute inset-0 bg-gradient-to-b from-[#090a0c] via-transparent to-[#090a0c] z-10"></div>
            <div class="absolute top-0 left-0 w-full h-[500px] bg-indigo-900/10 blur-[120px] rounded-full pointer-events-none z-10"></div>
            <div class="absolute bottom-0 right-0 w-full h-[500px] bg-emerald-900/10 blur-[120px] rounded-full pointer-events-none z-10"></div>
        </div>

        <nav class="relative z-50 w-full border-b border-white/5 bg-[#090a0c]/80 backdrop-blur-md sticky top-0">
            <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
                <div class="flex items-center gap-3 group cursor-pointer">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <span class="font-black text-xl tracking-tighter text-white">CS2<span class="text-indigo-400">TRACKER</span></span>
                </div>

                <div v-if="canLogin" class="flex items-center gap-6">
                    <Link v-if="$page.props.auth.user" :href="route('inventories.index')" class="flex items-center gap-2 text-sm font-bold text-white bg-white/10 px-5 py-2.5 rounded-full hover:bg-white/20 transition border border-white/5">
                        <img :src="$page.props.auth.user.avatar || 'https://avatars.steamstatic.com/fef49e7fa7e1997310d705b2a6158ff8dc1cdfeb_full.jpg'" class="w-5 h-5 rounded-full">
                        Мой Профиль
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="hidden sm:block text-sm font-bold text-gray-400 hover:text-white transition">
                            Войти
                        </Link>
                        <Link :href="route('register')" class="group relative px-6 py-2.5 rounded-xl bg-white text-black font-bold text-sm transition hover:shadow-[0_0_20px_rgba(255,255,255,0.3)] overflow-hidden">
                            <span class="relative z-10">Регистрация</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-gray-100 to-gray-300 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></div>
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main class="relative z-20">
            
            <section class="pt-20 pb-32 px-6 flex flex-col items-center text-center max-w-5xl mx-auto">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-emerald-400 text-xs font-bold uppercase tracking-widest mb-8 backdrop-blur-md animate-fade-in">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Бесплатный бета доступ
                </div>

                <h1 class="text-5xl md:text-7xl lg:text-8xl font-black tracking-tight mb-8 leading-[0.9]">
                    Твой Инвентарь. <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-emerald-400">Твоя Прибыль.</span>
                </h1>

                <p class="text-lg md:text-xl text-gray-400 max-w-2xl mb-12 leading-relaxed">
                    Профессиональный инструмент для трейдеров и инвесторов CS2. 
                    Автоматический подсчет <span class="text-gray-200 font-bold">ROI</span>, история цен со <span class="text-gray-200 font-bold">Skinport & Steam</span>, и полная аналитика твоего портфеля.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                    <Link :href="route('register')" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-bold text-lg transition shadow-[0_0_40px_rgba(79,70,229,0.4)] hover:shadow-[0_0_60px_rgba(79,70,229,0.6)] hover:-translate-y-1 flex items-center justify-center gap-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Начать бесплатно
                    </Link>
                    <Link :href="route('market.index')" class="px-8 py-4 bg-[#1e2128] border border-gray-700 hover:border-gray-500 text-gray-200 rounded-2xl font-bold text-lg transition hover:-translate-y-1 flex items-center justify-center gap-3">
                        База Скинов
                        <span>→</span>
                    </Link>
                </div>
            </section>

            <section class="max-w-7xl mx-auto px-6 pb-32">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="md:col-span-2 bg-[#131519]/60 backdrop-blur-xl border border-white/5 rounded-3xl p-8 hover:border-indigo-500/30 transition duration-500 group overflow-hidden relative">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600/10 blur-[80px] rounded-full pointer-events-none group-hover:bg-indigo-600/20 transition"></div>
                        
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-indigo-500/10 rounded-xl flex items-center justify-center mb-6 text-indigo-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            </div>
                            <h2 class="text-2xl font-bold text-white mb-3">Глубокая PnL Аналитика</h2>
                            <p class="text-gray-400 mb-6 max-w-lg">
                                Забудь про Excel таблицы. Наш трекер автоматически считает цену покупки и текущую рыночную стоимость. Узнай свой точный профит в долларах и процентах (ROI) для каждого предмета.
                            </p>
                            <div class="mt-4 p-4 bg-[#090a0c] rounded-xl border border-white/5 flex items-center gap-4 max-w-sm opacity-80 group-hover:opacity-100 transition">
                                <img src="https://community.cloudflare.steamstatic.com/economy/image/-9a81dlWLwJ2UUGcVs_nsVtzdOEdtWwKGZZLQHTxDZ7I56KU0Zwwo4NUX4oFJZEHLbXH5ApeO4YmlhxYQknCRvN0_Frrfv182Qtb1al0d0B11fTMezZ97dC3nI2PwaX2a-qGwD9Xv8F0j-qQrI3xiVLnqBc5Zj_yJ4CSJlM_Z1rS-1G_kOy-gJO77Z_Jz3Qx6yMk4S7VmQv3308-5v2dFg/360fx360f" class="w-10 h-10 object-contain">
                                <div>
                                    <div class="text-xs text-gray-500 font-bold uppercase">AK-47 | Asiimov</div>
                                    <div class="text-sm font-mono text-emerald-400 font-bold">+$24.50 (32%) ↗</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#131519]/60 backdrop-blur-xl border border-white/5 rounded-3xl p-8 hover:border-emerald-500/30 transition duration-500 group relative overflow-hidden">
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-emerald-600/10 blur-[80px] rounded-full pointer-events-none group-hover:bg-emerald-600/20 transition"></div>
                        
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 text-emerald-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">Цены со всех Маркетов</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">
                                Мы агрегируем данные с Steam Market, Skinport и DMarket. Всегда актуальные цены и возможность найти арбитраж.
                            </p>
                        </div>
                    </div>

                    <div class="bg-[#131519]/60 backdrop-blur-xl border border-white/5 rounded-3xl p-8 hover:border-pink-500/30 transition duration-500 group">
                        <div class="w-12 h-12 bg-pink-500/10 rounded-xl flex items-center justify-center mb-6 text-pink-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Умный Вишлист</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Создавай списки желаемого из базы в 20,000+ скинов. Следи за просадками и покупай на дне.
                        </p>
                    </div>

                    <div class="md:col-span-2 bg-[#131519]/60 backdrop-blur-xl border border-white/5 rounded-3xl p-8 hover:border-gray-500/30 transition duration-500 group flex flex-col md:flex-row items-center gap-8">
                        <div class="flex-1">
                            <div class="w-12 h-12 bg-gray-700/30 rounded-xl flex items-center justify-center mb-6 text-gray-300">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <h2 class="text-2xl font-bold text-white mb-3">Безопасность прежде всего</h2>
                            <p class="text-gray-400">
                                Нам не нужен доступ к твоему аккаунту Steam. Мы используем только публичный <b>Steam ID 64</b> для загрузки инвентаря. Никаких API ключей, никаких ботов. Твой аккаунт в полной безопасности.
                            </p>
                        </div>
                        <div class="bg-[#090a0c] p-6 rounded-2xl border border-white/5 w-full md:w-auto min-w-[300px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <div class="space-y-3 font-mono text-xs">
                                <div class="text-gray-500"># Загрузка инвентаря</div>
                                <div class="flex gap-2">
                                    <span class="text-purple-400">GET</span>
                                    <span class="text-gray-300">/api/steam/inventory/765611...</span>
                                </div>
                                <div class="text-emerald-400">Status: 200 OK (Public Data)</div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <section class="max-w-4xl mx-auto px-6 pb-20 text-gray-400 text-sm leading-relaxed">
                <h3 class="text-white font-bold text-lg mb-4">Почему CS2 Tracker — лучший выбор для инвестора?</h3>
                <p class="mb-4">
                    Рынок скинов Counter-Strike 2 постоянно меняется. Чтобы успешно инвестировать, недостаточно просто покупать скины. Вам нужен надежный 
                    <strong>трекер инвентаря CS2</strong>, который показывает реальную картину. Наш сервис позволяет отслеживать динамику цен не только в Steam, 
                    но и на реальных торговых площадках, таких как Skinport и DMarket, где цены часто ниже на 20-30%.
                </p>
                <p>
                    Используя наш <strong>калькулятор скинов</strong>, вы всегда будете знать, сколько стоит ваш инвентарь в реальных деньгах (Cashout value). 
                    Мы учитываем комиссию, трейд-баны и редкость паттернов. Это идеальный инструмент как для коллекционеров, так и для активных трейдеров.
                </p>
            </section>

            <section class="max-w-4xl mx-auto px-6 pb-24">
                <div class="relative bg-[#131519]/40 backdrop-blur-md border border-white/5 rounded-3xl p-8 md:p-12 overflow-hidden text-center">
                    
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-64 h-64 bg-indigo-500/10 blur-[80px] rounded-full pointer-events-none"></div>

                    <h2 class="relative z-10 text-2xl md:text-3xl font-bold text-white mb-4">Присоединяйся к комьюнити</h2>
                    <p class="relative z-10 text-gray-400 mb-8 max-w-lg mx-auto">
                        Следи за обновлениями трекера, предлагай идеи и смотри разработку в прямом эфире.
                    </p>

                    <div class="relative z-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        
                        <a href="https://www.twitch.tv/trenertvs" target="_blank" class="group relative w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-[#090a0c] border border-gray-800 rounded-xl hover:border-[#9146FF] transition-all duration-300 hover:shadow-[0_0_25px_rgba(145,70,255,0.2)]">
                            <svg class="w-6 h-6 text-[#9146FF] group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/>
                            </svg>
                            <span class="font-bold text-gray-300 group-hover:text-white">Twitch Stream</span>
                        </a>

                        <a href="https://t.me/olegovich_komorka" target="_blank" class="group relative w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-[#090a0c] border border-gray-800 rounded-xl hover:border-[#229ED9] transition-all duration-300 hover:shadow-[0_0_25px_rgba(34,158,217,0.2)]">
                            <svg class="w-6 h-6 text-[#229ED9] group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            <span class="font-bold text-gray-300 group-hover:text-white">Telegram</span>
                        </a>

                    </div>
                </div>
            </section>

            <footer class="border-t border-white/5 bg-[#090a0c] pt-12 pb-8">
                <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center">
                            <span class="font-bold text-white text-xs">CS</span>
                        </div>
                        <span class="text-gray-500 text-sm">© 2026 CS2 Tracker. Not affiliated with Valve Corp.</span>
                    </div>
                    
                    <div class="flex gap-6">
                        <a href="#" class="text-gray-500 hover:text-white transition">Политика конфиденциальности</a>
                        <a href="#" class="text-gray-500 hover:text-white transition">Условия использования</a>
                    </div>
                </div>
            </footer>

        </main>
    </div>
</template>

<style>
@keyframes marquee-left {
    from { transform: translateX(0); }
    to { transform: translateX(calc(-100% - 1.5rem)); }
}

@keyframes marquee-right {
    from { transform: translateX(calc(-100% - 1.5rem)); }
    to { transform: translateX(0); }
}

.animate-marquee-left {
    animation: marquee-left linear infinite;
}

.animate-marquee-right {
    animation: marquee-right linear infinite;
}

.animate-fade-in {
    animation: fadeIn 1s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>