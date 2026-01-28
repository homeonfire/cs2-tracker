<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    contracts: Array,
    // Эти пропсы Laravel Inertia передает автоматически, если настроено HandleInertiaRequests
    auth: Object 
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// --- UTILS ---
const formatMoney = (val) => '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const RARITY_COLORS = { 3: '#4b69ff', 4: '#8847ff', 5: '#d32ce6', 6: '#eb4b4b' };
const getRarityColor = (item) => RARITY_COLORS[item.rarity_id] || '#b0c3d9';

// --- FILTERS STATE ---
const searchQuery = ref('');
const minRoi = ref(0);
const maxPrice = ref(100);

// --- FILTERING LOGIC ---
const filteredContracts = computed(() => {
    return props.contracts.filter(c => {
        const matchesSearch = c.item.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesRoi = c.roi >= minRoi.value;
        const matchesPrice = parseFloat(c.contract_cost) <= maxPrice.value;
        return matchesSearch && matchesRoi && matchesPrice;
    });
});
</script>

<template>
    <Head title="Trade Up Contract Simulator CS2" />

    <div class="min-h-screen bg-[#0b0c10] text-gray-300 font-sans selection:bg-indigo-500 selection:text-white">
        
        <nav class="absolute top-0 w-full z-50 border-b border-white/5 bg-[#0b0c10]/50 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <span class="font-black text-xl tracking-tight text-white block leading-none">CS2</span>
                        <span class="font-bold text-xs tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">TRACKER</span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="user">
                        <Link :href="route('simulator.index')" class="text-sm font-bold text-gray-300 hover:text-white transition">Simulator</Link>
                        <Link :href="route('simulator.index')" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-indigo-500/20">
                            Открыть Симулятор
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-bold text-gray-300 hover:text-white transition">Войти</Link>
                        <Link :href="route('register')" class="px-5 py-2 bg-white text-black hover:bg-gray-200 text-xs font-black uppercase tracking-wider rounded-xl transition">
                            Регистрация
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <div class="relative bg-[#15171c] border-b border-white/5 overflow-hidden pt-32 pb-20">
            <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-indigo-600/10 rounded-full blur-[120px] -mr-20 -mt-20 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-purple-600/10 rounded-full blur-[100px] -ml-20 -mb-20 pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
                <span class="inline-block py-1 px-3 rounded-full bg-white/5 border border-white/10 text-[10px] font-bold uppercase tracking-widest text-indigo-400 mb-6 animate-fade-in-up">
                    Math Verified • Live Prices • CS2
                </span>
                
                <h1 class="text-5xl md:text-7xl font-black text-white uppercase tracking-tighter leading-none mb-6 drop-shadow-2xl">
                    Симулятор <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500">Контрактов</span>
                </h1>
                
                <p class="text-lg text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Калькулятор выгодных крафтов CS2. Рассчитывай шансы, ищи флоаты и находи халяву с помощью математической точность.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('simulator.index')" class="group relative px-8 py-4 bg-white text-black font-black uppercase tracking-wider rounded-xl hover:scale-105 transition-transform duration-200 shadow-[0_0_20px_rgba(255,255,255,0.3)] hover:shadow-[0_0_30px_rgba(255,255,255,0.5)]">
                        <span class="relative z-10 flex items-center gap-2">
                            Добавить скины в контракт
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </span>
                    </Link>
                    
                    <a href="#profitable-table" class="px-8 py-4 bg-white/5 text-white font-bold uppercase tracking-wider rounded-xl border border-white/10 hover:bg-white/10 transition flex items-center gap-2">
                        <span>Смотреть готовые рецепты</span>
                        <span class="bg-emerald-500/20 text-emerald-400 text-[10px] px-1.5 py-0.5 rounded ml-1">+ROI</span>
                    </a>
                </div>
            </div>
        </div>

        <div id="profitable-table" class="max-w-7xl mx-auto px-6 -mt-10 relative z-20 pb-20">
            
            <div class="bg-[#1a1c22] border border-white/10 rounded-t-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl backdrop-blur-sm bg-opacity-90">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 text-xs font-bold">$$</div>
                    <h2 class="text-lg font-bold text-white uppercase tracking-wide">Выгодные сборки</h2>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <div class="relative group flex-1 md:flex-none">
                        <input v-model="searchQuery" type="text" placeholder="Поиск скина..." class="w-full md:w-48 bg-[#0b0c10] border border-white/10 rounded-lg pl-8 pr-3 py-2 text-xs text-white focus:border-indigo-500 focus:ring-0 transition">
                        <svg class="w-3.5 h-3.5 text-gray-500 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>

                    <div class="flex items-center gap-2 bg-[#0b0c10] border border-white/10 rounded-lg px-3 py-1.5">
                        <span class="text-[10px] text-gray-500 font-bold uppercase">Min ROI %</span>
                        <input v-model="minRoi" type="number" class="w-12 bg-transparent border-none p-0 text-xs text-emerald-400 font-bold focus:ring-0 text-right" placeholder="0">
                    </div>

                    <div class="flex items-center gap-2 bg-[#0b0c10] border border-white/10 rounded-lg px-3 py-1.5">
                        <span class="text-[10px] text-gray-500 font-bold uppercase">Max $ (x10)</span>
                        <input v-model="maxPrice" type="number" class="w-16 bg-transparent border-none p-0 text-xs text-white font-bold focus:ring-0 text-right" placeholder="100">
                    </div>
                </div>
            </div>

            <div class="bg-[#15171c] border-x border-b border-white/10 rounded-b-2xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#121316] text-[10px] uppercase font-bold text-gray-500 tracking-wider border-b border-white/5">
                                <th class="px-6 py-4">Предмет (Вход)</th>
                                <th class="px-6 py-4 text-center">Качество</th>
                                <th class="px-6 py-4 text-right">Цена крафта (x10)</th>
                                <th class="px-6 py-4 text-right">Профит</th>
                                <th class="px-6 py-4 text-right">ROI</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm">
                            <tr v-for="contract in filteredContracts" :key="contract.id" class="group hover:bg-[#1e2026] transition-colors duration-200">
                                
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="relative w-10 h-10 bg-[#0b0c10] rounded-lg flex items-center justify-center border border-white/5 group-hover:border-white/20 transition">
                                            <div class="absolute inset-0 opacity-20 rounded-lg" :style="{ backgroundColor: getRarityColor(contract.item) }"></div>
                                            <img :src="contract.item.image_url" class="w-8 h-8 object-contain relative z-10">
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-200 group-hover:text-white transition text-xs">{{ contract.item.name }}</div>
                                            <div class="text-[9px] text-gray-600 uppercase">{{ contract.item.collection?.name }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="text-[10px] font-bold bg-[#0b0c10] border border-white/5 px-2 py-0.5 rounded text-gray-400 mb-0.5">{{ contract.wear_name }}</span>
                                        <span class="text-[9px] text-gray-600 font-mono">avg {{ contract.avg_float }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-3 text-right font-mono text-gray-300">
                                    {{ formatMoney(contract.contract_cost) }}
                                </td>

                                <td class="px-6 py-3 text-right">
                                    <div class="font-mono font-bold text-emerald-400 text-xs">
                                        +{{ formatMoney(contract.profit) }}
                                    </div>
                                </td>

                                <td class="px-6 py-3 text-right">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        {{ contract.roi }}%
                                    </span>
                                </td>

                                <td class="px-6 py-3 text-right">
                                    <Link :href="route('simulator.index')" class="opacity-0 group-hover:opacity-100 transition-opacity bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-bold uppercase px-3 py-1.5 rounded-lg shadow-lg shadow-indigo-500/20">
                                        Craft
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="filteredContracts.length === 0">
                                <td colspan="6" class="p-8 text-center text-gray-500 text-xs uppercase tracking-widest">
                                    Ничего не найдено под ваши фильтры
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-[#121316] px-6 py-3 text-[10px] text-gray-600 flex justify-between items-center border-t border-white/5">
                    <span>Обновлено автоматически 1 час назад</span>
                    <span>Показано топ-100 результатов</span>
                </div>
            </div>

            <div class="mt-16 text-gray-500 text-sm max-w-4xl mx-auto space-y-4 text-center">
                <p class="text-xs uppercase tracking-widest opacity-50">© 2026 CS2 Tracker. Trade Up Simulator.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
</style>