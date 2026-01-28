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
const searchQuery = ref('');
const sortBy = ref('price_desc');
const showTradableOnly = ref(false);

// Helper to safely get color. If null/undefined, return default grey/green.
const getRarityColor = (color) => {
    if (!color) return '#10b981'; // Default Emerald
    return color.startsWith('#') ? color : `#${color}`;
};

// Сокращение названий качеств (Professional Style)
const getWearShort = (wear) => {
    if (!wear) return '';
    if (wear.includes('Factory')) return 'FN';
    if (wear.includes('Minimal')) return 'MW';
    if (wear.includes('Field')) return 'FT';
    if (wear.includes('Well')) return 'WW';
    if (wear.includes('Battle')) return 'BS';
    return wear; // Для 'Not Painted' и прочего
};

// Цвет бейджика качества
const getWearStyle = (wear) => {
    if (!wear) return 'bg-gray-800 text-gray-500 border-gray-700'; // Default
    
    if (wear.includes('Factory')) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-[0_0_10px_rgba(16,185,129,0.1)]'; // FN - Top
    if (wear.includes('Minimal')) return 'bg-lime-500/10 text-lime-400 border-lime-500/20'; // MW - Good
    if (wear.includes('Field')) return 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'; // FT - Mid
    if (wear.includes('Well')) return 'bg-orange-500/10 text-orange-400 border-orange-500/20'; // WW - Bad
    if (wear.includes('Battle')) return 'bg-red-500/10 text-red-400 border-red-500/20'; // BS - Worst
    
    return 'bg-gray-800 text-gray-400 border-gray-700';
};

// Filter Logic
const filteredItems = computed(() => {
    let result = [...props.items];

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(item => 
            item.name.toLowerCase().includes(q) || 
            item.market_hash_name.toLowerCase().includes(q)
        );
    }

    if (showTradableOnly.value) {
        result = result.filter(item => item.is_tradable);
    }

    result.sort((a, b) => {
        // Ensure price is treated as a number
        const priceA = parseFloat(a.price) || 0;
        const priceB = parseFloat(b.price) || 0;

        if (sortBy.value === 'price_desc') return priceB - priceA;
        if (sortBy.value === 'price_asc') return priceA - priceB;
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
                    <Link :href="route('inventories.index')" class="group flex items-center justify-center w-10 h-10 rounded-xl bg-[#1e2128] border border-gray-800 text-gray-400 hover:text-white hover:border-emerald-500 transition-all">
                        <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-black text-white italic tracking-tight uppercase flex items-center gap-3">
                            {{ inventory.name }}
                            <span v-if="isRefreshing" class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
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
                    </div>
                </div>
            </div>
        </template>
        <div class="sticky top-0 z-30 bg-[#0f1115]/95 backdrop-blur-md py-4 border-b border-gray-800 mb-6 -mx-4 px-4 sm:mx-0 sm:px-0 sm:bg-transparent sm:backdrop-blur-none sm:border-none sm:static">
            <div class="flex flex-col sm:flex-row gap-4 justify-between">
                <div class="relative w-full sm:max-w-md group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-500 group-focus-within:text-emerald-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-800 rounded-xl leading-5 bg-[#15171c] text-gray-300 placeholder-gray-600 focus:outline-none focus:bg-[#1a1d24] focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:text-sm transition-all shadow-sm" 
                        placeholder="Поиск по скинам..." 
                    />
                </div>

                <div class="flex items-center gap-3 overflow-x-auto pb-1 sm:pb-0 no-scrollbar">
                    <button 
                        @click="showTradableOnly = !showTradableOnly"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-bold transition whitespace-nowrap select-none"
                        :class="showTradableOnly ? 'bg-emerald-600 border-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-[#15171c] border-gray-800 text-gray-400 hover:border-gray-600'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="showTradableOnly ? 'bg-white' : 'bg-gray-600'"></span>
                        Tradable
                    </button>

                    <div class="relative">
                        <select 
                            v-model="sortBy" 
                            class="appearance-none bg-[#15171c] border border-gray-800 text-gray-300 py-2.5 pl-4 pr-10 rounded-xl focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm font-bold cursor-pointer hover:border-gray-600 transition"
                        >
                            <option value="price_desc">Сначала дорогие</option>
                            <option value="price_asc">Сначала дешевые</option>
                            <option value="name">По названию</option>
                        </select>
                    </div>

                    <button 
                        @click="refreshInventory" 
                        :disabled="isRefreshing"
                        class="bg-gray-800 hover:bg-gray-700 text-white p-2.5 rounded-xl transition border border-gray-700 hover:border-gray-600"
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
                class="group bg-[#15171c] border rounded-xl p-3 transition-all duration-300 relative hover:-translate-y-1 hover:shadow-2xl flex flex-col overflow-hidden"
                :class="[
                    item.is_stattrak 
                        ? 'border-orange-500/30 hover:border-orange-500/60 hover:shadow-orange-900/10' 
                        : 'border-gray-800 hover:border-emerald-500/50 hover:shadow-emerald-900/10'
                ]"
            >
                <div 
                    class="absolute bottom-0 left-0 right-0 h-[3px] opacity-0 group-hover:opacity-100 transition-opacity duration-300" 
                    :style="{ background: getRarityColor(item.rarity_color) }"
                ></div>

                <div class="aspect-[1.3] flex items-center justify-center mb-3 relative rounded-lg bg-gradient-to-b from-gray-800/30 to-transparent p-4">
                    
                    <div v-if="item.is_stattrak" class="absolute top-2 left-2 flex flex-col items-center">
                        <div class="bg-[#1c1c1c] border border-orange-500/50 text-orange-500 text-[10px] font-mono font-bold px-1.5 py-0.5 rounded leading-none shadow-lg">
                            ST™
                        </div>
                    </div>

                    <div v-if="item.is_souvenir" class="absolute top-2 left-2">
                        <div class="bg-yellow-500/20 border border-yellow-500/50 text-yellow-400 text-[9px] font-bold px-1.5 py-0.5 rounded">
                            SOUV
                        </div>
                    </div>

                    <div v-if="item.wear_name" class="absolute top-2 right-2">
                        <div 
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded border backdrop-blur-sm transition-colors"
                            :class="getWearStyle(item.wear_name)"
                            :title="item.wear_name"
                        >
                            {{ getWearShort(item.wear_name) }}
                        </div>
                    </div>

                    <div 
                        class="absolute inset-0 opacity-0 group-hover:opacity-30 transition-opacity duration-500 rounded-lg" 
                        :style="{ background: `radial-gradient(circle at center, ${getRarityColor(item.rarity_color)} 0%, transparent 60%)` }"
                    ></div>
                    
                    <img 
                        :src="item.image" 
                        :alt="item.name" 
                        loading="lazy" 
                        class="w-full h-full object-contain drop-shadow-xl z-10 group-hover:scale-110 transition-transform duration-300 ease-out select-none"
                    >
                </div>

                <div class="mt-auto space-y-1.5 z-10">
                    <div class="font-medium text-gray-200 text-xs truncate leading-tight transition-colors">
                        <span v-if="item.is_stattrak" class="text-orange-500 font-bold mr-1">StatTrak™</span>
                        <span class="group-hover:text-white" :style="{ color: item.is_stattrak ? '#ffb98a' : getRarityColor(item.rarity_color) }">
                            {{ item.name.split('|')[0] }}
                        </span>
                    </div>
                    
                    <div class="text-[10px] text-gray-500 truncate font-mono flex items-center gap-2">
                        <span>{{ item.name.split('|')[1] || item.market_hash_name }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between pt-2 border-t border-gray-800/50 group-hover:border-gray-700/50 transition-colors">
                        <div>
                            <div class="text-[9px] text-gray-600 font-bold uppercase tracking-wider">Цена</div>
                            <div class="text-sm font-mono font-bold text-white group-hover:text-emerald-400 transition-colors">
                                {{ item.price_formatted }}
                            </div>
                        </div>
                        
                        <div class="opacity-0 -translate-x-2 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
                            <div class="w-6 h-6 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 group-hover:bg-emerald-500 group-hover:text-white">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
        
        <div v-if="filteredItems.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-600">
            <svg class="w-16 h-16 mb-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="font-bold text-lg">Предметы не найдены</p>
        </div>

    </AuthenticatedLayout>
</template>