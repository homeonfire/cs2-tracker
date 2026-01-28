<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    contracts: Array
});

// --- UTILS ---
const formatMoney = (val) => '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const RARITY_COLORS = {
    3: '#4b69ff', // Mil-Spec
    4: '#8847ff', // Restricted
    5: '#d32ce6', // Classified
    6: '#eb4b4b', // Covert
};

const getRarityColor = (item) => RARITY_COLORS[item.rarity_id] || '#b0c3d9';

// Получаем топ-3 для плашек сверху
const bestRoi = computed(() => props.contracts.length ? props.contracts[0] : null);
const cheapestEntry = computed(() => [...props.contracts].sort((a, b) => a.contract_cost - b.contract_cost)[0]);
const biggestProfit = computed(() => [...props.contracts].sort((a, b) => b.profit - a.profit)[0]);

// Хелпер для цвета ROI
const getRoiColor = (roi) => {
    if (roi >= 50) return 'text-purple-400 bg-purple-500/10 border-purple-500/20';
    if (roi >= 20) return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
    if (roi > 0) return 'text-teal-400 bg-teal-500/10 border-teal-500/20';
    return 'text-gray-400 bg-gray-500/10 border-gray-500/20';
};
</script>

<template>
    <Head title="Smart Contracts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black text-2xl text-white leading-tight uppercase tracking-wide flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 text-sm">
                            $$
                        </span>
                        Smart Contracts
                    </h2>
                    <p class="text-gray-500 text-xs mt-1 font-medium ml-11">
                        Автоматический поиск выгодных моно-крафтов (10 одинаковых предметов)
                    </p>
                </div>
                <Link :href="route('simulator.index')" class="px-5 py-2.5 bg-[#15171c] hover:bg-[#1a1c22] text-white text-xs font-bold rounded-xl border border-white/10 hover:border-white/20 transition flex items-center gap-2">
                    <span>Перейти в Симулятор</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </Link>
            </div>
        </template>

        <div class="pb-12 max-w-7xl mx-auto">
            
            <div v-if="contracts.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-[#15171c] border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-purple-500/20 transition"></div>
                    <div class="text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-1">Максимальный ROI</div>
                    <div class="text-3xl font-black text-white mb-2">{{ bestRoi.roi }}%</div>
                    <div class="text-xs text-gray-500 truncate">{{ bestRoi.item.name }} ({{ bestRoi.wear_name }})</div>
                </div>

                <div class="bg-[#15171c] border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-emerald-500/20 transition"></div>
                    <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1">Макс. Прибыль</div>
                    <div class="text-3xl font-black text-white mb-2">+{{ formatMoney(biggestProfit.profit) }}</div>
                    <div class="text-xs text-gray-500 truncate">Вход: {{ formatMoney(biggestProfit.contract_cost) }}</div>
                </div>

                <div class="bg-[#15171c] border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-blue-500/20 transition"></div>
                    <div class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Дешевый вход</div>
                    <div class="text-3xl font-black text-white mb-2">{{ formatMoney(cheapestEntry.contract_cost) }}</div>
                    <div class="text-xs text-gray-500 truncate">ROI: {{ cheapestEntry.roi }}%</div>
                </div>
            </div>

            <div class="bg-[#15171c] border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#1a1c22]/50 border-b border-white/5 text-[10px] uppercase font-bold text-gray-500 tracking-wider">
                                <th class="px-6 py-4">Предмет (Вход x10)</th>
                                <th class="px-6 py-4">Качество</th>
                                <th class="px-6 py-4 text-right">Стоимость</th>
                                <th class="px-6 py-4 text-right">Ожидание (EV)</th>
                                <th class="px-6 py-4 text-right">Прибыль</th>
                                <th class="px-6 py-4 text-right">ROI</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm">
                            <tr v-for="contract in contracts" :key="contract.id" class="group hover:bg-[#1a1c22] transition-colors duration-200">
                                
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-12 h-12 flex-shrink-0 bg-[#0b0c10] rounded-lg flex items-center justify-center border border-white/5 group-hover:border-white/10 transition">
                                            <div class="absolute inset-0 opacity-20 rounded-lg" :style="{ backgroundColor: getRarityColor(contract.item) }"></div>
                                            <img :src="contract.item.image_url" class="w-10 h-10 object-contain relative z-10 group-hover:scale-110 transition duration-300">
                                        </div>
                                        <div>
                                            <div class="font-bold text-white group-hover:text-indigo-400 transition truncate max-w-[200px]">{{ contract.item.name }}</div>
                                            <div class="text-[10px] text-gray-500 uppercase tracking-wide">{{ contract.item.collection?.name }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-gray-300 font-medium text-xs">{{ contract.wear_name }}</span>
                                        <span class="text-[10px] text-gray-600 font-mono">avg {{ contract.avg_float }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="font-mono text-gray-300">{{ formatMoney(contract.contract_cost) }}</div>
                                    <div class="text-[10px] text-gray-600">x10 шт</div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="font-mono font-bold text-white">{{ formatMoney(contract.expected_value) }}</div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="font-mono font-bold" :class="contract.profit > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ contract.profit > 0 ? '+' : '' }}{{ formatMoney(contract.profit) }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border" :class="getRoiColor(contract.roi)">
                                        {{ contract.roi > 0 ? '+' : '' }}{{ contract.roi }}%
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('simulator.index')" class="w-8 h-8 rounded-lg bg-[#0b0c10] border border-white/10 flex items-center justify-center text-gray-500 hover:text-white hover:border-indigo-500/50 hover:bg-indigo-500/10 transition ml-auto">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="contracts.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 bg-[#0b0c10] rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
                        <svg class="w-8 h-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </div>
                    <h3 class="text-white font-bold text-lg">Контракты не найдены</h3>
                    <p class="text-gray-500 text-sm mt-1">Запустите поиск через консоль: <code>php artisan contracts:find</code></p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>