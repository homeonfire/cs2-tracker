<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    inventoryItem: Object,
    item: Object,
    chartSeries: Array,
    inventoryName: String,
    inventoryId: Number
});

const form = useForm({
    buy_price: props.inventoryItem.buy_price || null
});

const savePrice = () => {
    form.put(route('inventory.update', props.inventoryItem.id), {
        preserveScroll: true,
        onSuccess: () => { }
    });
};

const profit = computed(() => {
    const buy = parseFloat(form.buy_price);
    const current = parseFloat(props.item.price_skinport);
    if (!buy || isNaN(buy) || buy === 0) return null;
    if (!current) return null;
    const diff = current - buy;
    const percent = ((diff / buy) * 100).toFixed(2);
    return { val: diff.toFixed(2), percent: percent, isPositive: diff >= 0 };
});

const formatMoney = (val) => val ? `$${parseFloat(val).toFixed(2)}` : '---';

const chartOptions = ref({
    chart: { type: 'area', height: 350, fontFamily: 'inherit', toolbar: { show: false }, background: 'transparent', animations: { enabled: true } },
    colors: ['#818cf8', '#34d399'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#374151', strokeDashArray: 4, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
    xaxis: { type: 'datetime', tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#9ca3af' } } },
    yaxis: { labels: { style: { colors: '#9ca3af', fontFamily: 'monospace' }, formatter: (v) => `$${v.toFixed(2)}` } },
    theme: { mode: 'dark' },
    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#fff' } },
    tooltip: { theme: 'dark' }
});

const links = computed(() => {
    const nameEnc = encodeURIComponent(props.item.market_hash_name);
    return {
        skinport: `https://skinport.com/market?search=${nameEnc}`,
        dmarket: `https://dmarket.com/ingame-items/item-list/csgo-skins?title=${nameEnc}`,
        steam: `https://steamcommunity.com/market/listings/730/${nameEnc}`
    };
});
</script>

<template>
    <Head :title="item.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('inventories.show', inventoryId)" class="group flex items-center justify-center w-10 h-10 bg-[#1e2128] border border-gray-700 hover:border-indigo-500 rounded-xl transition text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </Link>
                <div>
                    <h2 class="font-black text-2xl text-white tracking-tighter uppercase italic">{{ item.name }}</h2>
                    <div class="flex items-center gap-2 text-xs text-gray-500 font-mono mt-0.5">
                        <span>{{ inventoryName }}</span>
                        <span class="text-gray-700">•</span>
                        <span class="text-indigo-400">{{ item.market_hash_name }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-8 relative overflow-hidden flex flex-col items-center justify-center min-h-[400px]">
                        <div class="absolute inset-0 opacity-20" :style="{ background: `radial-gradient(circle at center, #${item.rarity_color || '555'} 0%, transparent 70%)` }"></div>
                        <img :src="item.image_url" class="relative z-10 w-full max-w-[300px] drop-shadow-[0_10px_40px_rgba(0,0,0,0.5)] hover:scale-110 transition-transform duration-500 ease-out">
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <a :href="links.skinport" target="_blank" class="flex items-center justify-between px-5 py-4 bg-[#1a1c23] hover:bg-[#1f222b] border border-gray-800 hover:border-indigo-500/50 rounded-xl group transition-all duration-200">
                            <div class="flex items-center gap-3"><div class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.6)]"></div><span class="font-bold text-gray-300 group-hover:text-white transition">Skinport</span></div>
                            <svg class="w-5 h-5 text-gray-600 group-hover:text-indigo-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                        <a :href="links.dmarket" target="_blank" class="flex items-center justify-between px-5 py-4 bg-[#1a1c23] hover:bg-[#1f222b] border border-gray-800 hover:border-emerald-500/50 rounded-xl group transition-all duration-200">
                            <div class="flex items-center gap-3"><div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]"></div><span class="font-bold text-gray-300 group-hover:text-white transition">DMarket</span></div>
                            <svg class="w-5 h-5 text-gray-600 group-hover:text-emerald-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                        <a :href="links.steam" target="_blank" class="flex items-center justify-between px-5 py-4 bg-[#1a1c23] hover:bg-[#1f222b] border border-gray-800 hover:border-blue-500/50 rounded-xl group transition-all duration-200">
                            <div class="flex items-center gap-3"><div class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]"></div><span class="font-bold text-gray-300 group-hover:text-white transition">Steam</span></div>
                            <svg class="w-5 h-5 text-gray-600 group-hover:text-blue-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-6 md:p-8">
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-6 flex items-center gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg> Финансы</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                            <div class="space-y-2">
                                <label class="text-xs text-gray-400 font-bold ml-1">Цена покупки</label>
                                <div class="relative group">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-indigo-400 transition">$</span>
                                    <input v-model="form.buy_price" type="number" step="0.01" class="w-full bg-[#0f1115] border border-gray-700 rounded-xl py-4 pl-8 pr-20 text-white placeholder-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition font-mono text-xl font-bold" placeholder="0.00" @keyup.enter="savePrice">
                                    <button @click="savePrice" class="absolute right-2 top-2 bottom-2 px-4 bg-gray-800 hover:bg-indigo-600 text-gray-400 hover:text-white rounded-lg text-xs font-bold uppercase transition">Save</button>
                                </div>
                            </div>
                            <div class="bg-[#0f1115] border border-gray-700/50 rounded-xl p-4 flex items-center justify-between h-full min-h-[86px]">
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Текущий PnL</div>
                                    <div v-if="profit" class="text-3xl font-mono font-black tracking-tight" :class="profit.isPositive ? 'text-green-400' : 'text-red-400'">{{ profit.isPositive ? '+' : '' }}{{ profit.val }}<span class="text-lg font-bold opacity-70">$</span></div>
                                    <div v-else class="text-3xl font-mono font-bold text-gray-700">--</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">ROI</div>
                                    <div v-if="profit" class="text-xl font-mono font-bold" :class="profit.isPositive ? 'text-green-400' : 'text-red-400'">{{ profit.isPositive ? '+' : '' }}{{ profit.percent }}%</div>
                                    <div v-else class="text-xl font-mono font-bold text-gray-700">--</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-[#15171c] border border-gray-800 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-indigo-500/20 rounded-full blur-xl group-hover:bg-indigo-500/30 transition"></div>
                            <div class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider mb-1">Skinport</div>
                            <div class="text-2xl text-white font-mono font-bold">{{ formatMoney(item.price_skinport) }}</div>
                        </div>
                        <div class="bg-[#15171c] border border-gray-800 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-500/20 rounded-full blur-xl group-hover:bg-emerald-500/30 transition"></div>
                            <div class="text-[10px] text-emerald-400 font-bold uppercase tracking-wider mb-1">DMarket</div>
                            <div class="text-2xl text-white font-mono font-bold">{{ formatMoney(item.price_dmarket) }}</div>
                        </div>
                        <div class="bg-[#15171c] border border-gray-800 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-500/20 rounded-full blur-xl group-hover:bg-blue-500/30 transition"></div>
                            <div class="text-[10px] text-blue-400 font-bold uppercase tracking-wider mb-1">Steam</div>
                            <div class="text-2xl text-white font-mono font-bold">{{ formatMoney(item.price_steam) }}</div>
                        </div>
                    </div>

                    <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-6">
                         <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg> История цен (30 дней)</h3>
                            <div class="flex gap-4">
                                <div class="flex items-center gap-2 text-xs text-gray-400"><span class="w-2 h-2 rounded-full bg-indigo-400"></span> Skinport</div>
                                <div class="flex items-center gap-2 text-xs text-gray-400"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> DMarket</div>
                            </div>
                        </div>
                        <div class="w-full h-[350px] relative">
                            <apexchart width="100%" height="100%" type="area" :options="chartOptions" :series="chartSeries"></apexchart>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>