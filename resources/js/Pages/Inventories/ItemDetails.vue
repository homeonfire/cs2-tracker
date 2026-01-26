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

// Форма для редактирования цены покупки
const form = useForm({
    buy_price: props.inventoryItem.buy_price || 0
});

const updatePrice = () => {
    form.put(route('inventory-items.update', props.inventoryItem.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Можно добавить уведомление
        }
    });
};

// 1. Умное вычисление цены
const currentPrice = computed(() => parseFloat(props.item.min_price || 0));

// 2. Цена покупки (берем из пропсов для реактивности, но форма обновляет страницу)
const buyPrice = computed(() => parseFloat(props.inventoryItem.buy_price || 0));

// 3. Считаем PnL
const profit = computed(() => currentPrice.value - buyPrice.value);

// 4. Считаем ROI
const roi = computed(() => {
    if (buyPrice.value === 0) return 0;
    return (profit.value / buyPrice.value) * 100;
});

const isPositive = computed(() => profit.value >= 0);
const profitColorClass = computed(() => isPositive.value ? 'text-green-400' : 'text-red-400');
const profitBgClass = computed(() => isPositive.value ? 'bg-green-500/10' : 'bg-red-500/10');

const formatMoney = (val) => '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Генерация ссылок на маркеты
const steamLink = computed(() => `https://steamcommunity.com/market/listings/730/${encodeURIComponent(props.item.market_hash_name)}`);
const skinportLink = computed(() => `https://skinport.com/market?search=${encodeURIComponent(props.item.market_hash_name)}&cat=Counter-Strike%3A+Global+Offensive`);
const dmarketLink = computed(() => `https://dmarket.com/ingame-items/item-list/csgo-skins?title=${encodeURIComponent(props.item.market_hash_name)}`);

// Настройки графика
const chartOptions = {
    chart: { type: 'area', height: 350, toolbar: { show: false }, background: 'transparent' },
    colors: ['#4f46e5', '#10b981'],
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    theme: { mode: 'dark' },
    xaxis: { type: 'datetime', tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { formatter: (val) => '$' + val.toFixed(2) } },
    grid: { borderColor: '#374151', strokeDashArray: 4 },
    tooltip: { theme: 'dark', x: { format: 'dd MMM yyyy' } }
};
</script>

<template>
    <Head :title="item.market_hash_name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('inventories.show', inventoryId)" class="p-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 transition">
                    ← Назад
                </Link>
                <h1 class="text-2xl font-bold text-white leading-tight">
                    {{ item.market_hash_name }}
                    <span class="block text-sm font-normal text-gray-500 mt-1">{{ inventoryName }}</span>
                </h1>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-10">
            
            <div class="space-y-6">
                <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1" :style="{ backgroundColor: item.rarity_color }"></div>
                    
                    <div class="aspect-square flex items-center justify-center mb-6 relative">
                        <img :src="item.image_url" :alt="item.name" class="w-full h-full object-contain drop-shadow-2xl hover:scale-105 transition duration-500">
                        <div v-if="item.name.includes('StatTrak')" class="absolute top-2 right-2 bg-orange-500 text-black text-xs font-bold px-2 py-0.5 rounded">StatTrak™</div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-800/50 p-3 rounded-xl">
                                <div class="text-[10px] uppercase text-gray-500 font-bold mb-1">Текущая цена</div>
                                <div class="text-2xl font-mono font-bold text-white">{{ formatMoney(currentPrice) }}</div>
                            </div>
                            <div class="bg-gray-800/50 p-3 rounded-xl border border-transparent focus-within:border-indigo-500 transition">
                                <InputLabel value="Цена покупки ($)" class="text-[10px] uppercase text-gray-500 font-bold mb-1" />
                                <div class="flex items-center gap-2">
                                    <TextInput 
                                        type="number" 
                                        step="0.01" 
                                        v-model="form.buy_price" 
                                        @change="updatePrice"
                                        class="w-full bg-transparent border-none p-0 text-2xl font-mono font-bold text-indigo-300 focus:ring-0 h-auto" 
                                    />
                                    <span v-if="form.isDirty" class="text-xs text-indigo-400 animate-pulse">Enter ↵</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl flex items-center justify-between" :class="profitBgClass">
                            <div>
                                <div class="text-[10px] uppercase font-bold opacity-70" :class="profitColorClass">PnL</div>
                                <div class="text-xl font-bold font-mono" :class="profitColorClass">
                                    {{ isPositive ? '+' : '' }}{{ formatMoney(profit) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] uppercase font-bold opacity-70" :class="profitColorClass">ROI</div>
                                <div class="text-xl font-bold font-mono" :class="profitColorClass">
                                    {{ isPositive ? '+' : '' }}{{ roi.toFixed(2) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-4">
                    <h3 class="text-xs text-gray-500 font-bold uppercase mb-3">Торговые площадки</h3>
                    <div class="grid grid-cols-1 gap-2">
                        <a :href="steamLink" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-[#1b2838] hover:bg-[#2a475e] transition text-white group">
                            <span class="font-bold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.979 0C5.666 0 .506 4.935.035 11.164l3.66 1.517c.564-2.185 2.548-3.805 4.909-3.805 1.571 0 2.973.708 3.928 1.81l3.581-1.474C14.935 3.998 11.754 0 11.979 0zM7.22 9.774c-1.748 0-3.181 1.34-3.321 3.056l-3.38-1.402C.185 12.59 0 13.79 0 15.02c0 4.97 4.03 9 9 9 4.385 0 8.03-3.176 8.846-7.316l-3.376 1.39c-.585 1.705-2.204 2.926-4.108 2.926-2.394 0-4.336-1.942-4.336-4.336 0-1.896 1.205-3.514 2.903-4.105L7.22 9.774zm1.78 4.606c-1.31 0-2.372 1.062-2.372 2.372 0 1.31 1.062 2.372 2.372 2.372s2.372-1.062 2.372-2.372c0-1.31-1.062-2.372-2.372-2.372zm15 1.637l-4.708 1.938c-.371 1.76-1.536 3.235-3.084 4.104A8.966 8.966 0 0024 15c0-2.822-1.282-5.347-3.318-7.106l-2.074 4.54a5.27 5.27 0 01-1.018 2.986l6.41 2.6z"/></svg>
                                Steam Market
                            </span>
                            <span class="text-xs text-gray-400 group-hover:text-white">Перейти →</span>
                        </a>
                        <a :href="skinportLink" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-[#242424] hover:bg-[#333] transition text-white border border-gray-700 group">
                            <span class="font-bold flex items-center gap-2">🛒 Skinport</span>
                            <span class="text-xs text-gray-400 group-hover:text-white">Перейти →</span>
                        </a>
                        <a :href="dmarketLink" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-[#3e9e16] hover:bg-[#4cb91f] transition text-white group">
                            <span class="font-bold flex items-center gap-2">👽 DMarket</span>
                            <span class="text-xs text-white/80 group-hover:text-white">Перейти →</span>
                        </a>
                    </div>
                </div>

                <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-6">
                    <h3 class="text-white font-bold mb-4">Детали</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex justify-between">
                            <span class="text-gray-500">Tradable</span>
                            <span :class="item.is_tradable ? 'text-green-400' : 'text-red-400'">
                                {{ item.is_tradable ? 'Да' : 'Нет (Бан)' }}
                            </span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-gray-500">Asset ID</span>
                            <span class="text-gray-300 font-mono">{{ inventoryItem.asset_id }}</span>
                        </li>
                         <li class="flex justify-between">
                            <span class="text-gray-500">Добавлен</span>
                            <span class="text-gray-300">{{ new Date(inventoryItem.created_at).toLocaleDateString() }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-6 h-full">
                    <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                        История цены (90 дней)
                    </h3>
                    <div v-if="chartSeries[0].data.length > 0 || chartSeries[1].data.length > 0">
                        <ApexCharts type="area" height="350" :options="chartOptions" :series="chartSeries" />
                    </div>
                    <div v-else class="h-[300px] flex flex-col items-center justify-center text-gray-500">
                        <p>Нет данных для графика</p>
                        <span class="text-xs opacity-50 mt-2">История начнет собираться при следующих обновлениях</span>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>