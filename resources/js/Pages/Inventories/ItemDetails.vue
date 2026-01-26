<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ApexCharts from 'vue3-apexcharts';

const props = defineProps({
    inventoryItem: Object,
    item: Object,
    chartSeries: Array,
    inventoryName: String,
    inventoryId: Number
});

// Форма
const form = useForm({
    buy_price: props.inventoryItem.buy_price || 0
});

const updatePrice = () => {
    form.put(route('inventory-items.update', props.inventoryItem.id), {
        preserveScroll: true,
        onSuccess: () => { /* Можно добавить toast notification */ }
    });
};

// Вычисления
const currentPrice = computed(() => parseFloat(props.item.min_price || 0));
const buyPrice = computed(() => parseFloat(props.inventoryItem.buy_price || 0));
const profit = computed(() => currentPrice.value - buyPrice.value);

const roi = computed(() => {
    if (buyPrice.value === 0) return 0;
    return (profit.value / buyPrice.value) * 100;
});

const isPositive = computed(() => profit.value >= 0);
const profitColorClass = computed(() => isPositive.value ? 'text-emerald-400' : 'text-rose-400');
const profitBgClass = computed(() => isPositive.value ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20');

const formatMoney = (val) => '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Ссылки
const steamLink = computed(() => `https://steamcommunity.com/market/listings/730/${encodeURIComponent(props.item.market_hash_name)}`);
const skinportLink = computed(() => `https://skinport.com/market?search=${encodeURIComponent(props.item.market_hash_name)}&cat=Counter-Strike%3A+Global+Offensive`);
const dmarketLink = computed(() => `https://dmarket.com/ingame-items/item-list/csgo-skins?title=${encodeURIComponent(props.item.market_hash_name)}`);

// График
const chartOptions = {
    chart: { type: 'area', height: 350, toolbar: { show: false }, background: 'transparent', animations: { enabled: true } },
    colors: ['#6366f1', '#10b981'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
    dataLabels: { enabled: false },
    theme: { mode: 'dark' },
    xaxis: { type: 'datetime', tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#6b7280' } } },
    yaxis: { labels: { style: { colors: '#6b7280' }, formatter: (val) => '$' + val.toFixed(2) } },
    grid: { borderColor: '#374151', strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
    tooltip: { theme: 'dark', x: { format: 'dd MMM yyyy' }, style: { fontSize: '12px' } }
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
                    <h1 class="text-2xl font-bold text-white leading-tight tracking-tight">
                        {{ item.market_hash_name }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        {{ inventoryName }}
                    </div>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">
            
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-[#15171c] border border-gray-800 rounded-3xl overflow-hidden relative shadow-2xl">
                    <div class="absolute inset-0 opacity-20" :style="{ background: `radial-gradient(circle at center, ${item.rarity_color}, transparent 70%)` }"></div>
                    <div class="absolute top-0 left-0 w-full h-1 z-10" :style="{ backgroundColor: item.rarity_color }"></div>

                    <div class="aspect-[1.1] flex items-center justify-center relative p-8 z-10">
                        <img :src="item.image_url" :alt="item.name" class="w-full h-full object-contain drop-shadow-[0_10px_30px_rgba(0,0,0,0.5)] transform hover:scale-105 transition duration-500 ease-out">
                        <div v-if="item.name.includes('StatTrak')" class="absolute top-4 right-4 bg-orange-500/90 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-lg border border-orange-400/50 shadow-lg">
                            StatTrak™
                        </div>
                    </div>

                    <div class="bg-[#1a1d24]/80 backdrop-blur-md border-t border-gray-800 p-6 space-y-5 relative z-20">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#111316] border border-gray-700/50 p-3 rounded-2xl">
                                <div class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Market Price</div>
                                <div class="text-2xl font-mono font-bold text-white tracking-tight">{{ formatMoney(currentPrice) }}</div>
                            </div>
                            
                            <div class="bg-[#111316] border border-gray-700/50 p-3 rounded-2xl group focus-within:border-indigo-500/50 focus-within:ring-1 focus-within:ring-indigo-500/50 transition-all">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Buy Price</span>
                                    <svg class="w-3 h-3 text-gray-600 group-hover:text-indigo-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500 font-mono text-xl font-bold">$</span>
                                    <TextInput 
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

                        <div class="border rounded-2xl p-4 flex items-center justify-between relative overflow-hidden" :class="profitBgClass">
                            <div class="relative z-10">
                                <div class="text-[10px] uppercase font-bold opacity-80 mb-0.5" :class="profitColorClass">Profit / Loss</div>
                                <div class="text-2xl font-bold font-mono tracking-tight" :class="profitColorClass">
                                    {{ isPositive ? '+' : '' }}{{ formatMoney(profit) }}
                                </div>
                            </div>
                            <div class="text-right relative z-10">
                                <div class="text-[10px] uppercase font-bold opacity-80 mb-0.5" :class="profitColorClass">ROI</div>
                                <div class="text-2xl font-bold font-mono tracking-tight" :class="profitColorClass">
                                    {{ isPositive ? '+' : '' }}{{ roi.toFixed(0) }}<span class="text-sm align-top">%</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <a :href="steamLink" target="_blank" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-[#1b2838] hover:bg-[#2a475e] border border-white/5 hover:border-blue-400/30 transition group">
                        <svg class="w-6 h-6 text-white mb-1 group-hover:scale-110 transition" fill="currentColor" viewBox="0 0 24 24"><path d="M11.979 0C5.666 0 .506 4.935.035 11.164l3.66 1.517c.564-2.185 2.548-3.805 4.909-3.805 1.571 0 2.973.708 3.928 1.81l3.581-1.474C14.935 3.998 11.754 0 11.979 0zM7.22 9.774c-1.748 0-3.181 1.34-3.321 3.056l-3.38-1.402C.185 12.59 0 13.79 0 15.02c0 4.97 4.03 9 9 9 4.385 0 8.03-3.176 8.846-7.316l-3.376 1.39c-.585 1.705-2.204 2.926-4.108 2.926-2.394 0-4.336-1.942-4.336-4.336 0-1.896 1.205-3.514 2.903-4.105L7.22 9.774zm1.78 4.606c-1.31 0-2.372 1.062-2.372 2.372 0 1.31 1.062 2.372 2.372 2.372s2.372-1.062 2.372-2.372c0-1.31-1.062-2.372-2.372-2.372zm15 1.637l-4.708 1.938c-.371 1.76-1.536 3.235-3.084 4.104A8.966 8.966 0 0024 15c0-2.822-1.282-5.347-3.318-7.106l-2.074 4.54a5.27 5.27 0 01-1.018 2.986l6.41 2.6z"/></svg>
                        <span class="text-[10px] font-bold text-gray-400 group-hover:text-white uppercase tracking-wider">Steam</span>
                    </a>
                    <a :href="skinportLink" target="_blank" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-[#111] hover:bg-black border border-white/10 hover:border-orange-500/50 transition group">
                        <span class="text-xl mb-1 group-hover:scale-110 transition">🛒</span>
                        <span class="text-[10px] font-bold text-gray-400 group-hover:text-white uppercase tracking-wider">Skinport</span>
                    </a>
                    <a :href="dmarketLink" target="_blank" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-[#0b330f] hover:bg-[#0f4214] border border-white/5 hover:border-green-400/30 transition group">
                        <span class="text-xl mb-1 group-hover:scale-110 transition">👽</span>
                        <span class="text-[10px] font-bold text-gray-400 group-hover:text-white uppercase tracking-wider">DMarket</span>
                    </a>
                </div>

                <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-5">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center border-b border-gray-800 pb-2">
                            <span class="text-xs text-gray-500 font-bold uppercase">Status</span>
                            <span class="text-sm font-bold flex items-center gap-1.5" :class="item.is_tradable ? 'text-emerald-400' : 'text-rose-400'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="item.is_tradable ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                {{ item.is_tradable ? 'Tradable' : 'Trade Ban' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-800 pb-2">
                            <span class="text-xs text-gray-500 font-bold uppercase">Asset ID</span>
                            <span class="text-xs text-gray-400 font-mono bg-gray-800 px-2 py-1 rounded">{{ inventoryItem.asset_id }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-bold uppercase">Added</span>
                            <span class="text-xs text-gray-400">{{ new Date(inventoryItem.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-8 h-full flex flex-col">
                <div class="bg-[#15171c] border border-gray-800 rounded-3xl p-6 flex-1 flex flex-col shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-white font-bold flex items-center gap-2 text-lg">
                            <span class="bg-indigo-500/10 text-indigo-400 p-1.5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            </span>
                            Price History
                        </h3>
                        <div class="flex gap-4 text-xs font-bold text-gray-500">
                             <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#6366f1]"></span>Skinport</div>
                             <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#10b981]"></span>DMarket</div>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-h-[400px] relative">
                        <div v-if="chartSeries[0].data.length > 0 || chartSeries[1].data.length > 0" class="absolute inset-0">
                            <ApexCharts type="area" height="100%" width="100%" :options="chartOptions" :series="chartSeries" />
                        </div>
                        <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-[#111316] rounded-2xl border border-dashed border-gray-800">
                            <div class="text-4xl mb-2 opacity-50">📉</div>
                            <p class="font-medium">No price history available yet</p>
                            <span class="text-xs opacity-50 mt-1">Check back tomorrow</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>