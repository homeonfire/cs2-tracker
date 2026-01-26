<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApexCharts from 'vue3-apexcharts';

const props = defineProps({
    inventoryItem: Object,
    item: Object,
    chartSeries: Array,
    inventoryName: String,
    inventoryId: Number
});

// 1. Умное вычисление цены (берем min_price из модели)
const currentPrice = computed(() => {
    return parseFloat(props.item.min_price || 0);
});

// 2. Цена покупки
const buyPrice = computed(() => {
    return parseFloat(props.inventoryItem.buy_price || 0);
});

// 3. Считаем PnL (Прибыль/Убыток)
const profit = computed(() => {
    return currentPrice.value - buyPrice.value;
});

// 4. Считаем ROI (Процент доходности)
const roi = computed(() => {
    if (buyPrice.value === 0) return 0;
    return (profit.value / buyPrice.value) * 100;
});

// 5. Определяем цвета для профита
const isPositive = computed(() => profit.value >= 0);
const profitColorClass = computed(() => isPositive.value ? 'text-green-400' : 'text-red-400');
const profitBgClass = computed(() => isPositive.value ? 'bg-green-500/10' : 'bg-red-500/10');

// Форматирование денег
const formatMoney = (val) => {
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Настройки графика
const chartOptions = {
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        background: 'transparent'
    },
    colors: ['#4f46e5', '#10b981'], // Indigo, Emerald
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    theme: { mode: 'dark' },
    xaxis: {
        type: 'datetime',
        tooltip: { enabled: false },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            formatter: (val) => '$' + val.toFixed(2)
        }
    },
    grid: {
        borderColor: '#374151',
        strokeDashArray: 4,
    },
    tooltip: {
        theme: 'dark',
        x: { format: 'dd MMM yyyy' }
    }
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
                            <div class="bg-gray-800/50 p-3 rounded-xl">
                                <div class="text-[10px] uppercase text-gray-500 font-bold mb-1">Цена покупки</div>
                                <div class="text-2xl font-mono font-bold text-gray-300">{{ formatMoney(buyPrice) }}</div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl flex items-center justify-between" :class="profitBgClass">
                            <div>
                                <div class="text-[10px] uppercase font-bold opacity-70" :class="profitColorClass">PnL (Прибыль)</div>
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
                        <ApexCharts 
                            type="area" 
                            height="350" 
                            :options="chartOptions" 
                            :series="chartSeries" 
                        />
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