<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    inventory: Object,
    items: Array,
    itemCount: Number,
    stats: Object,
    error: String,
    last_updated: String
});

const isRefreshing = ref(false);

const refreshInventory = () => {
    isRefreshing.value = true;
    router.post(route('inventories.refresh', props.inventory.id), {}, {
        onFinish: () => isRefreshing.value = false,
        preserveScroll: true
    });
};

const formatPrice = (value) => {
    let val = parseFloat(value);
    if (isNaN(val)) val = 0;
    return '$' + val.toFixed(2);
};

const getProfit = (current, buy) => {
    const buyVal = parseFloat(buy);
    const currentVal = parseFloat(current);
    if (!buyVal || isNaN(buyVal) || buyVal === 0) return null;
    const diff = currentVal - buyVal;
    const percent = ((diff / buyVal) * 100).toFixed(1);
    return {
        value: diff >= 0 ? `+$${diff.toFixed(2)}` : `-$${Math.abs(diff).toFixed(2)}`,
        percent: `${diff >= 0 ? '+' : ''}${percent}%`,
        isPositive: diff >= 0
    };
};
</script>

<template>
    <Head :title="inventory.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">
                <div class="flex items-center gap-4 w-full xl:w-auto">
                    <Link :href="route('inventories.index')" class="group flex items-center justify-center w-10 h-10 bg-[#1e2128] border border-gray-700 hover:border-indigo-500 rounded-xl transition text-gray-400 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="font-black text-2xl md:text-3xl text-white tracking-tighter uppercase italic truncate max-w-[200px] sm:max-w-md">{{ inventory.name }}</h2>
                            <span class="bg-[#1e2128] text-gray-400 px-2 py-0.5 rounded text-[10px] font-mono border border-white/5 whitespace-nowrap">{{ itemCount }} ПРЕДМЕТОВ</span>
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <p class="text-xs text-gray-500 font-mono hidden sm:block">{{ inventory.steam_id }}</p>
                            <button @click="refreshInventory" :disabled="isRefreshing" class="flex items-center gap-1 px-2 py-0.5 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white text-[10px] uppercase font-bold rounded transition disabled:opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" :class="{'animate-spin': isRefreshing}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                {{ isRefreshing ? 'ОБНОВЛЕНИЕ...' : 'ОБНОВИТЬ' }}
                            </button>
                            <span class="text-[10px] text-gray-600 border-l border-gray-700 pl-3 ml-1">{{ last_updated }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 w-full xl:w-auto">
                    <div class="bg-[#15171c] border border-gray-800 p-3 rounded-xl min-w-[120px]">
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">Стоимость</div>
                        <div class="text-white font-mono text-lg font-bold tracking-tight">${{ stats.value }}</div>
                    </div>
                    <div class="bg-[#15171c] border border-gray-800 p-3 rounded-xl min-w-[120px]">
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">Вложено</div>
                        <div class="text-gray-300 font-mono text-lg font-bold tracking-tight">${{ stats.invested }}</div>
                    </div>
                    <div class="bg-[#15171c] border p-3 rounded-xl min-w-[120px] relative overflow-hidden col-span-2 sm:col-span-1" :class="stats.is_positive ? 'border-green-500/20 bg-green-500/5' : 'border-red-500/20 bg-red-500/5'">
                        <div class="text-[10px] uppercase font-bold tracking-wider mb-0.5" :class="stats.is_positive ? 'text-green-400' : 'text-red-400'">Профит ({{ stats.roi }}%)</div>
                        <div class="font-mono text-lg font-bold tracking-tight flex items-center gap-2" :class="stats.is_positive ? 'text-green-400' : 'text-red-400'">
                             {{ stats.is_positive ? '+' : '' }}${{ stats.profit }}
                             <svg v-if="stats.is_positive" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                             <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="pb-10">
            <div v-if="error" class="bg-red-500/10 border border-red-500/50 text-red-200 p-4 rounded-xl mb-6 flex items-center gap-3">
                <svg class="h-6 w-6 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="text-sm font-medium">{{ error }}</span>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 3xl:grid-cols-8 gap-4">
                <Link v-for="item in items" :key="item.asset_id" :href="route('inventory.item', item.id)" class="group bg-[#15171c] border border-gray-800 hover:border-gray-600 rounded-xl p-3 transition-all duration-200 relative hover:-translate-y-1 hover:shadow-xl hover:shadow-black/50 block">
                    <div class="absolute bottom-0 left-3 right-3 h-[2px] rounded-full opacity-50 group-hover:opacity-100 transition-opacity" :style="{ background: item.rarity_color ? `#${item.rarity_color}` : '#4b5563' }"></div>
                    <div v-if="!item.is_tradable" class="absolute top-2 right-2 z-20"><div class="w-2 h-2 bg-red-500 rounded-full shadow-[0_0_5px_rgba(239,68,68,0.8)]"></div></div>
                    <div class="aspect-[1.3] flex items-center justify-center mb-3 relative overflow-hidden rounded-lg bg-gradient-to-b from-gray-800/30 to-transparent">
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-30 transition-opacity duration-500" :style="{ background: `radial-gradient(circle at center, #${item.rarity_color || '000'} 0%, transparent 70%)` }"></div>
                        <img :src="item.image" :alt="item.name" loading="lazy" class="max-h-[85%] w-auto drop-shadow-2xl z-10 group-hover:scale-110 transition-transform duration-300 ease-out select-none">
                    </div>
                    <div class="space-y-2 pb-2">
                        <div class="font-medium text-gray-300 text-xs truncate" :title="item.name">{{ item.name }}</div>
                        <div class="flex items-end justify-between">
                            <div>
                                <div class="text-[9px] text-gray-600 font-bold uppercase tracking-wider">Цена</div>
                                <div class="text-sm font-mono font-bold text-white group-hover:text-indigo-300 transition-colors">{{ formatPrice(item.min_price) }}</div>
                            </div>
                            <div class="text-right">
                                <div v-if="item.buy_price && parseFloat(item.buy_price) > 0 && getProfit(item.price, item.buy_price)">
                                    <div class="text-[9px] font-bold" :class="getProfit(item.price, item.buy_price)?.isPositive ? 'text-green-500' : 'text-red-500'">{{ getProfit(item.price, item.buy_price)?.percent }}</div>
                                    <div class="text-xs font-mono font-medium" :class="getProfit(item.price, item.buy_price)?.isPositive ? 'text-green-400' : 'text-red-400'">{{ getProfit(item.price, item.buy_price)?.value }}</div>
                                </div>
                                <div v-else>
                                     <div class="w-6 h-6 rounded bg-gray-800 flex items-center justify-center text-gray-500 group-hover:text-white group-hover:bg-indigo-600 transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>