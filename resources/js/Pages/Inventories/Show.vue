<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    inventory: Object,
    items: Array,
    stats: Object,
    last_updated: String
});

const isRefreshing = ref(false);

// --- ЛОГИКА ФИЛЬТРАЦИИ И СОРТИРОВКИ ---
const searchQuery = ref('');
const sortBy = ref('price_desc'); // price_desc, price_asc, name
const showTradableOnly = ref(false);

// Умный список предметов (фильтруется на лету)
const filteredItems = computed(() => {
    let result = [...props.items];

    // 1. Поиск
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(item => 
            item.name.toLowerCase().includes(q) || 
            item.market_hash_name.toLowerCase().includes(q)
        );
    }

    // 2. Фильтр по трейду
    if (showTradableOnly.value) {
        result = result.filter(item => item.is_tradable);
    }

    // 3. Сортировка
    result.sort((a, b) => {
        if (sortBy.value === 'price_desc') return b.price - a.price;
        if (sortBy.value === 'price_asc') return a.price - b.price;
        if (sortBy.value === 'name') return a.name.localeCompare(b.name);
        return 0;
    });

    return result;
});

const refreshInventory = () => {
    isRefreshing.value = true;
    router.post(route('inventories.refresh', props.inventory.id), {}, {
        onFinish: () => isRefreshing.value = false
    });
};
</script>

<template>
    <Head :title="inventory.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">
                <div class="flex items-center gap-4 w-full xl:w-auto">
                    <Link :href="route('inventories.index')" class="group flex items-center justify-center w-10 h-10 rounded-xl bg-[#1e2128] border border-gray-800 text-gray-400 hover:text-white hover:border-indigo-500 transition-all">
                        <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-black text-white italic tracking-tight uppercase flex items-center gap-3">
                            {{ inventory.name }}
                            <span v-if="isRefreshing" class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                            </span>
                        </h1>
                        <div class="flex items-center gap-3 text-xs font-mono text-gray-500 mt-1">
                            <span class="bg-gray-800 px-2 py-0.5 rounded text-gray-400">ID: {{ inventory.steam_id }}</span>
                            <span class="w-1 h-1 rounded-full bg-gray-600"></span>
                            <span>{{ last_updated }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 w-full xl:w-auto bg-[#15171c] p-1.5 rounded-2xl border border-gray-800/50 shadow-xl">
                    <div class="px-5 py-2.5 rounded-xl bg-[#1e2128] border border-gray-800/50 flex flex-col justify-center">
                        <div class="text-[9px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Стоимость</div>
                        <div class="text-lg font-bold text-white font-mono">${{ stats.value }}</div>
                    </div>
                    <div class="px-5 py-2.5 rounded-xl bg-[#1e2128] border border-gray-800/50 flex flex-col justify-center">
                        <div class="text-[9px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Вложено</div>
                        <div class="text-lg font-bold text-gray-300 font-mono">${{ stats.invested }}</div>
                    </div>
                    <div class="px-5 py-2.5 rounded-xl border flex flex-col justify-center relative overflow-hidden" 
                         :class="stats.is_positive ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-rose-500/5 border-rose-500/20'">
                        <div class="relative z-10 text-[9px] uppercase font-bold tracking-wider mb-0.5" 
                             :class="stats.is_positive ? 'text-emerald-500' : 'text-rose-500'">
                            ROI {{ stats.roi }}%
                        </div>
                        <div class="relative z-10 text-lg font-bold font-mono" 
                             :class="stats.is_positive ? 'text-emerald-400' : 'text-rose-400'">
                            {{ stats.is_positive ? '+' : '' }}${{ stats.profit }}
                        </div>
                        <div class="absolute right-0 bottom-0 opacity-20 pointer-events-none">
                            <svg v-if="stats.is_positive" class="w-10 h-10 text-emerald-500 transform translate-x-2 translate-y-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            <svg v-else class="w-10 h-10 text-rose-500 transform translate-x-2 translate-y-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="sticky top-0 z-30 bg-[#0f1115]/95 backdrop-blur-md py-4 border-b border-gray-800 mb-6 -mx-4 px-4 sm:mx-0 sm:px-0 sm:bg-transparent sm:backdrop-blur-none sm:border-none sm:static">
            <div class="flex flex-col sm:flex-row gap-4 justify-between">
                
                <div class="relative w-full sm:max-w-md group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-500 group-focus-within:text-indigo-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-800 rounded-xl leading-5 bg-[#15171c] text-gray-300 placeholder-gray-600 focus:outline-none focus:bg-[#1a1d24] focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all shadow-sm" 
                        placeholder="Поиск по скинам..." 
                    />
                </div>

                <div class="flex items-center gap-3 overflow-x-auto pb-1 sm:pb-0 no-scrollbar">
                    
                    <button 
                        @click="showTradableOnly = !showTradableOnly"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-bold transition whitespace-nowrap select-none"
                        :class="showTradableOnly ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'bg-[#15171c] border-gray-800 text-gray-400 hover:border-gray-600'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="showTradableOnly ? 'bg-white' : 'bg-gray-600'"></span>
                        Tradable
                    </button>

                    <div class="relative">
                        <select 
                            v-model="sortBy" 
                            class="appearance-none bg-[#15171c] border border-gray-800 text-gray-300 py-2.5 pl-4 pr-10 rounded-xl focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-bold cursor-pointer hover:border-gray-600 transition"
                        >
                            <option value="price_desc">Сначала дорогие</option>
                            <option value="price_asc">Сначала дешевые</option>
                            <option value="name">По названию</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>

                    <button 
                        @click="refreshInventory" 
                        :disabled="isRefreshing"
                        class="bg-gray-800 hover:bg-gray-700 text-white p-2.5 rounded-xl transition border border-gray-700 hover:border-gray-600"
                        title="Обновить цены"
                    >
                        <svg class="w-5 h-5" :class="{'animate-spin': isRefreshing}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 pb-20">
            <Link 
                v-for="item in filteredItems" 
                :key="item.id"
                :href="route('inventories.item', item.id)"
                class="group relative bg-[#15171c] rounded-2xl p-3 border transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl overflow-hidden flex flex-col justify-between"
                :class="{'border-gray-800 hover:border-gray-600': !item.rarity_color}"
                :style="item.rarity_color ? { borderColor: `${item.rarity_color}40` } : {}"
            >
                <div 
                    class="absolute inset-0 opacity-0 group-hover:opacity-20 transition duration-500 pointer-events-none"
                    :style="{ background: `radial-gradient(circle at center, ${item.rarity_color || '#ffffff'}, transparent 70%)` }"
                ></div>

                <div class="flex justify-between items-start z-10 relative mb-2">
                    <div v-if="item.name.includes('StatTrak')" class="bg-orange-500/10 border border-orange-500/50 text-orange-500 text-[9px] font-bold px-1.5 py-0.5 rounded backdrop-blur-sm">
                        ST™
                    </div>
                    <div v-else></div> <div class="w-2 h-2 rounded-full shadow-[0_0_5px_currentColor]" :class="item.is_tradable ? 'text-emerald-500 bg-emerald-500' : 'text-rose-500 bg-rose-500'"></div>
                </div>

                <div class="aspect-[1.3] flex items-center justify-center mb-3 relative z-10">
                    <img :src="item.image" :alt="item.name" class="w-full h-full object-contain drop-shadow-lg group-hover:scale-110 group-hover:drop-shadow-[0_10px_15px_rgba(0,0,0,0.5)] transition duration-500 ease-out">
                </div>

                <div class="z-10 relative bg-[#0f1115]/50 -mx-3 -mb-3 p-3 border-t border-white/5 backdrop-blur-sm group-hover:bg-[#0f1115]/80 transition">
                    <div class="mb-2">
                        <div class="h-0.5 w-8 rounded-full mb-1.5" :style="{ backgroundColor: item.rarity_color || '#4b5563' }"></div>
                        
                        <h3 class="text-xs font-bold text-gray-200 truncate group-hover:text-white transition">
                            {{ item.name.split('|')[0] }}
                        </h3>
                        <p class="text-[10px] text-gray-500 truncate font-medium">
                            {{ item.name.split('|')[1] || item.market_hash_name }}
                        </p>
                    </div>

                    <div class="flex justify-between items-end">
                        <div>
                            <div class="text-[9px] font-bold text-gray-600 uppercase tracking-wider">Price</div>
                            <div class="text-sm font-bold font-mono text-indigo-300 group-hover:text-indigo-200 transition">
                                {{ item.price_formatted }}
                            </div>
                        </div>
                        <div class="w-7 h-7 rounded-lg bg-gray-800 flex items-center justify-center text-gray-500 group-hover:bg-indigo-600 group-hover:text-white transition shadow-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
        
        <div v-if="filteredItems.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-600">
            <div class="w-20 h-20 bg-gray-800/50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <p class="font-bold text-lg">Предметы не найдены</p>
            <p class="text-sm opacity-60">Попробуйте изменить фильтры поиска</p>
        </div>

    </AuthenticatedLayout>
</template>