<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import ApexCharts from 'vue3-apexcharts';

const props = defineProps({
    inventoryItem: Object,
    item: Object, // Здесь внутри лежит массив prices[]
    chartSeries: Array,
    inventoryName: String,
    inventoryId: Number
});
console.log('Данные графика:', props.chartSeries);

// --- ЛОГИКА ВАРИАЦИЙ ---
const currentVariation = computed(() => {
    let prefix = '';
    if (props.inventoryItem.is_stattrak) prefix = 'StatTrak ';
    else if (props.inventoryItem.is_souvenir) prefix = 'Souvenir ';
    
    let wear = props.inventoryItem.wear_name || '';
    let result = (prefix + wear).trim();
    return result === '' ? null : result;
});

// --- ПОИСК ЦЕНЫ ---
// 1. Текущая цена (для PnL) - ищем точное совпадение
const currentPriceObj = computed(() => {
    const exactMatch = props.item.prices.find(p => p.variation === currentVariation.value && p.market_name === 'dmarket');
    if (exactMatch) return exactMatch.price;

    const baseMatch = props.item.prices.find(p => p.variation === null && p.market_name === 'dmarket');
    return baseMatch ? baseMatch.price : 0;
});

// 2. Список цен на другие качества (для таблицы справа)
const otherQualities = computed(() => {
    const groups = {};
    props.item.prices.forEach(p => {
        if (!p.variation) return;
        if (!groups[p.variation] || parseFloat(p.price) < parseFloat(groups[p.variation].price)) {
            groups[p.variation] = p;
        }
    });
    return Object.values(groups).sort((a, b) => b.price - a.price);
});

// --- FORM ---
const form = useForm({
    buy_price: props.inventoryItem.buy_price || 0
});

const updatePrice = () => {
    form.put(route('inventory-items.update', props.inventoryItem.id), {
        preserveScroll: true,
    });
};

// --- PNL CALC ---
const currentPriceVal = computed(() => parseFloat(currentPriceObj.value || 0));
const buyPriceVal = computed(() => parseFloat(form.buy_price || 0));
const profit = computed(() => currentPriceVal.value - buyPriceVal.value);

const roi = computed(() => {
    if (buyPriceVal.value === 0) return 0;
    return (profit.value / buyPriceVal.value) * 100;
});

const isPositive = computed(() => profit.value >= 0);
const formatMoney = (val) => '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- СТИЛИ ---
const getWearColor = (wear) => {
    if (!wear) return 'bg-gray-700 text-gray-300';
    if (wear.includes('Factory')) return 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
    if (wear.includes('Minimal')) return 'bg-lime-500/20 text-lime-400 border-lime-500/30';
    if (wear.includes('Field')) return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
    if (wear.includes('Well')) return 'bg-orange-500/20 text-orange-400 border-orange-500/30';
    if (wear.includes('Battle')) return 'bg-red-500/20 text-red-400 border-red-500/30';
    return 'bg-gray-700 text-gray-300';
};

// --- ССЫЛКИ ---
const encodeName = (name) => encodeURIComponent(name);
const marketplaces = computed(() => [
    { name: 'DMarket', icon: '👽', url: `https://dmarket.com/ingame-items/item-list/csgo-skins?title=${encodeName(props.item.market_hash_name)}`, color: 'hover:bg-[#0b330f] hover:border-green-500/50' },
    { name: 'Skinport', icon: '🛒', url: `https://skinport.com/market?search=${encodeName(props.item.market_hash_name)}&cat=Counter-Strike%3A+Global+Offensive`, color: 'hover:bg-gray-900 hover:border-gray-500' },
    { name: 'Steam', icon: '🚂', url: `https://steamcommunity.com/market/listings/730/${encodeName(props.item.market_hash_name)}`, color: 'hover:bg-[#1b2838] hover:border-blue-500/50' },
    { name: 'CSFloat', icon: '🎈', url: `https://csfloat.com/search?q=${encodeName(props.item.market_hash_name)}`, color: 'hover:bg-indigo-900/40 hover:border-indigo-500/50' },
    { name: 'Buff163', icon: '🐂', url: `https://buff.163.com/market/csgo#tab=selling&page_num=1&search=${encodeName(props.item.market_hash_name)}`, color: 'hover:bg-orange-900/20 hover:border-orange-500/50' },
]);

// Chart Config
const chartOptions = {
    chart: { type: 'area', height: 350, toolbar: { show: false }, background: 'transparent', animations: { enabled: true } },
    colors: ['#10b981', '#6366f1'],
    stroke: { curve: 'monotoneCubic', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 100] } },
    dataLabels: { enabled: false },
    theme: { mode: 'dark' },
    xaxis: { type: 'datetime', tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#6b7280' } } },
    yaxis: { labels: { style: { colors: '#6b7280' }, formatter: (val) => '$' + val.toFixed(2) } },
    grid: { borderColor: '#374151', strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
    tooltip: { theme: 'dark', x: { format: 'dd MMM yyyy' } }
};
</script>

<template>
    <Head :title="item.market_hash_name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('inventories.show', inventoryId)" class="group flex items-center justify-center w-10 h-10 rounded-xl bg-[#1e2128] border border-gray-800 text-gray-400 hover:text-white hover:border-indigo-500 transition-all">
                    <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-white leading-tight tracking-tight">
                        <span v-if="inventoryItem.is_stattrak" class="text-orange-500 mr-2">StatTrak™</span>
                        <span :style="{ color: item.rarity_color ? `#${item.rarity_color}` : 'white' }">{{ item.name }}</span>
                    </h1>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5 font-mono">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        {{ inventoryName }}
                        <span class="text-gray-600">|</span>
                        <span>Asset ID: {{ inventoryItem.asset_id }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-20">
            
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-[#15171c] border border-gray-800 rounded-3xl overflow-hidden relative shadow-2xl group">
                    <div class="absolute inset-0 opacity-20" :style="{ background: `radial-gradient(circle at center, #${item.rarity_color || '555'}, transparent 70%)` }"></div>
                    <div class="absolute top-0 left-0 w-full h-1 z-10" :style="{ backgroundColor: `#${item.rarity_color || '555'}` }"></div>

                    <div class="aspect-[1.2] flex items-center justify-center relative p-8 z-10">
                        <img :src="item.image_url" :alt="item.name" class="w-full h-full object-contain drop-shadow-[0_10px_30px_rgba(0,0,0,0.5)] transform group-hover:scale-105 transition duration-500 ease-out">
                        
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <div v-if="inventoryItem.is_stattrak" class="bg-orange-500/20 text-orange-400 text-[10px] font-bold px-2 py-1 rounded border border-orange-500/30 backdrop-blur-sm">ST™</div>
                            <div v-if="inventoryItem.is_souvenir" class="bg-yellow-500/20 text-yellow-400 text-[10px] font-bold px-2 py-1 rounded border border-yellow-500/30 backdrop-blur-sm">SOUV</div>
                        </div>

                        <div v-if="inventoryItem.wear_name" class="absolute top-4 right-4">
                            <div class="text-[10px] font-bold px-2 py-1 rounded border backdrop-blur-sm" :class="getWearColor(inventoryItem.wear_name)">
                                {{ inventoryItem.wear_name }}
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#1a1d24]/90 backdrop-blur-xl border-t border-gray-800 p-6 space-y-5 relative z-20">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#111316] border border-gray-700/50 p-3 rounded-2xl">
                                <div class="text-[9px] uppercase tracking-wider text-gray-500 font-bold mb-1">Текущая цена</div>
                                <div class="text-2xl font-mono font-bold text-white tracking-tight">{{ formatMoney(currentPriceVal) }}</div>
                                <div class="text-[10px] text-gray-600 truncate mt-1">Источник: DMarket</div>
                            </div>
                            
                            <div class="bg-[#111316] border border-gray-700/50 p-3 rounded-2xl group focus-within:border-indigo-500/50 focus-within:ring-1 focus-within:ring-indigo-500/50 transition-all cursor-text" @click="$refs.buyInput.focus()">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Цена покупки</span>
                                    <svg class="w-3 h-3 text-gray-600 group-hover:text-indigo-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500 font-mono text-xl font-bold">$</span>
                                    <TextInput 
                                        ref="buyInput"
                                        type="number" 
                                        step="0.01" 
                                        v-model="form.buy_price" 
                                        @change="updatePrice"
                                        class="w-full bg-transparent border-none p-0 text-xl font-mono font-bold text-indigo-300 placeholder-gray-600 focus:ring-0 h-auto leading-none" 
                                        placeholder="0.00"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-2xl p-4 flex items-center justify-between relative overflow-hidden transition-colors duration-500" 
                             :class="isPositive ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20'">
                            <div class="relative z-10">
                                <div class="text-[10px] uppercase font-bold opacity-80 mb-0.5" :class="isPositive ? 'text-emerald-400' : 'text-rose-400'">Прибыль / Убыток</div>
                                <div class="text-2xl font-bold font-mono tracking-tight" :class="isPositive ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ isPositive ? '+' : '' }}{{ formatMoney(profit) }}
                                </div>
                            </div>
                            <div class="text-right relative z-10">
                                <div class="text-[10px] uppercase font-bold opacity-80 mb-0.5" :class="isPositive ? 'text-emerald-400' : 'text-rose-400'">ROI</div>
                                <div class="text-2xl font-bold font-mono tracking-tight" :class="isPositive ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ isPositive ? '+' : '' }}{{ roi.toFixed(0) }}<span class="text-sm align-top">%</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <a v-for="market in marketplaces" :key="market.name" 
                       :href="market.url" target="_blank" 
                       class="flex flex-col items-center justify-center p-3 rounded-xl bg-[#15171c] border border-gray-800 transition group"
                       :class="market.color"
                    >
                        <span class="text-xl mb-1 group-hover:scale-110 transition filter grayscale group-hover:grayscale-0">{{ market.icon }}</span>
                        <span class="text-[10px] font-bold text-gray-500 group-hover:text-white uppercase tracking-wider">{{ market.name }}</span>
                    </a>
                </div>

            </div>

            <div class="lg:col-span-8 space-y-6">
                
                <div class="bg-[#15171c] border border-gray-800 rounded-3xl p-6 shadow-xl min-h-[420px] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-bold flex items-center gap-2 text-lg">
                            <span class="bg-indigo-500/10 text-indigo-400 p-1.5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            </span>
                            История цен
                        </h3>
                        <div class="flex gap-4 text-xs font-bold text-gray-500">
                             <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#10b981]"></span>Цена</div>
                        </div>
                    </div>
                    
                    <div class="flex-1 relative">
                        <div v-if="chartSeries[0].data.length > 0" class="absolute inset-0">
                            <ApexCharts type="area" height="100%" width="100%" :options="chartOptions" :series="chartSeries" />
                        </div>
                        <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-[#111316]/50 rounded-2xl border border-dashed border-gray-800">
                            <div class="text-4xl mb-2 opacity-50">📉</div>
                            <p class="font-medium">История цен отсутствует</p>
                        </div>
                    </div>
                </div>

                <div v-if="otherQualities.length > 0" class="bg-[#15171c] border border-gray-800 rounded-3xl p-6 shadow-xl">
                    <h3 class="text-white font-bold flex items-center gap-2 text-lg mb-4">
                        <span class="bg-emerald-500/10 text-emerald-400 p-1.5 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </span>
                        Другие вариации
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-[#111316] text-xs uppercase font-bold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-xl">Вариация</th>
                                    <th class="px-4 py-3">Площадка</th>
                                    <th class="px-4 py-3 text-right rounded-r-xl">Лучшая цена</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                <tr v-for="variant in otherQualities" :key="variant.id" class="hover:bg-[#1a1d24] transition">
                                    <td class="px-4 py-3 font-medium text-white">
                                        {{ variant.variation }}
                                        <span v-if="variant.variation === currentVariation" class="ml-2 text-[10px] bg-indigo-500/20 text-indigo-300 px-1.5 py-0.5 rounded border border-indigo-500/30">ТВОЙ</span>
                                    </td>
                                    <td class="px-4 py-3 flex items-center gap-2">
                                        <span class="capitalize text-emerald-400">{{ variant.market_name }}</span>
                                        <a :href="variant.market_link" target="_blank" class="text-gray-600 hover:text-white">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-white">
                                        {{ formatMoney(variant.price) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>