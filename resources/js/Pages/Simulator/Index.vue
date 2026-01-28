<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, computed, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    collections: Array,
    rarities: Array
});

// --- CONSTANTS ---
const RARITY_COLORS = {
    1: '#b0c3d9', 2: '#5e98d9', 3: '#4b69ff', 4: '#8847ff', 5: '#d32ce6', 6: '#eb4b4b', 7: '#e4ae39'
};

const WEAR_RANGES = [
    { label: 'FN', min: 0.00, max: 0.07, color: 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10' },
    { label: 'MW', min: 0.07, max: 0.15, color: 'text-lime-400 border-lime-500/30 bg-lime-500/10' },
    { label: 'FT', min: 0.15, max: 0.38, color: 'text-yellow-400 border-yellow-500/30 bg-yellow-500/10' },
    { label: 'WW', min: 0.38, max: 0.45, color: 'text-orange-400 border-orange-500/30 bg-orange-500/10' },
    { label: 'BS', min: 0.45, max: 1.00, color: 'text-rose-400 border-rose-500/30 bg-rose-500/10' }
];

const WEAR_MAP = { 'FN': 'Factory New', 'MW': 'Minimal Wear', 'FT': 'Field-Tested', 'WW': 'Well-Worn', 'BS': 'Battle-Scarred' };

// --- UTILS ---
const formatMoney = (val) => '$' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const getRarityColor = (item) => {
    if (!item) return '#b0c3d9';
    return item.rarity_color?.startsWith('#') ? item.rarity_color : (RARITY_COLORS[item.rarity_id] || '#b0c3d9');
};

const getPriceRange = (item) => {
    if (!item.prices?.length) return '$0.00';
    const prices = item.prices.map(p => parseFloat(p.price));
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    return min === max ? formatMoney(min) : `${formatMoney(min)} - ${formatMoney(max)}`;
};

const getFloatBarStyle = (item) => ({ 
    left: `${(item.min_float || 0) * 100}%`, 
    width: `${((item.max_float || 1) - (item.min_float || 0)) * 100}%` 
});

const safeCollectionName = (name) => {
    if (!name) return 'Unknown Collection';
    return name.replace('The ', '').replace(' Collection', '');
};

const isWearAvailable = (item, range) => {
    return Math.max(item.min_float, range.min) < Math.min(item.max_float, range.max);
};

// --- STATE ---
const selectedItems = ref([]);
const filters = ref({ query: '', rarity_id: '', collection_id: '' });
const searchResults = ref([]);
const isSearching = ref(false);
const simulationResult = ref(null);
const isCalculating = ref(false);
const errorMessage = ref(null);

// --- LOGIC ---
const fetchItems = async () => {
    isSearching.value = true;
    try { 
        const res = await axios.get(route('simulator.search'), { params: filters.value });
        searchResults.value = res.data;
    } catch (e) { console.error(e); } finally { isSearching.value = false; }
};

watch(filters, () => { 
    clearTimeout(window.st); 
    window.st = setTimeout(fetchItems, 300); 
}, { deep: true });

// Init load
fetchItems();

const requiredSlots = computed(() => (selectedItems.value[0]?.rarity_id === 6 ? 5 : 10));
const totalSum = computed(() => selectedItems.value.reduce((sum, i) => sum + parseFloat(i.price || 0), 0));

const addItem = (item, wearRange = null) => {
    if (selectedItems.value.length > 0 && selectedItems.value[0].rarity_id !== item.rarity_id) return alert("Нельзя смешивать разные редкости!");
    if (selectedItems.value.length >= requiredSlots.value) return alert(`Максимум ${requiredSlots.value} предметов.`);

    if (!wearRange) {
        wearRange = WEAR_RANGES.find(r => isWearAvailable(item, r));
        if (!wearRange) return alert("Этот скин недоступен (ошибка флоата)");
    }

    let targetFloat = (wearRange.min + wearRange.max) / 2;
    if (targetFloat < item.min_float) targetFloat = item.min_float + 0.001;
    if (targetFloat > item.max_float) targetFloat = item.max_float - 0.001;

    const fullWearName = WEAR_MAP[wearRange.label];
    const priceObj = item.prices?.find(p => p.market_name === 'dmarket' && (p.variation === fullWearName));
    let finalPrice = priceObj ? parseFloat(priceObj.price) : 0;
    
    if (finalPrice === 0) {
        const basePrice = item.prices?.find(p => p.market_name === 'dmarket' && p.variation === null);
        if (basePrice) finalPrice = parseFloat(basePrice.price);
    }

    selectedItems.value.push({
        ...item,
        unique_id: Date.now() + Math.random(),
        float_value: parseFloat(targetFloat.toFixed(5)),
        price: finalPrice,
        wear_label: wearRange.label
    });

    simulationResult.value = null; 
    filters.value.rarity_id = item.rarity_id;
};

const removeItem = (index) => {
    selectedItems.value.splice(index, 1); 
    simulationResult.value = null;
    if (selectedItems.value.length === 0) { 
        filters.value.rarity_id = ''; 
        fetchItems(); 
    }
};

const clearAll = () => { 
    selectedItems.value = []; 
    simulationResult.value = null; 
    filters.value.rarity_id = ''; 
    fetchItems(); 
};

const calculate = async () => {
    if (selectedItems.value.length !== requiredSlots.value) return;
    isCalculating.value = true; 
    errorMessage.value = null; 
    simulationResult.value = null;

    try {
        const payload = {
            items: selectedItems.value.map(s => ({ 
                item_id: parseInt(s.id), 
                float_value: parseFloat(s.float_value), 
                price: parseFloat(s.price) 
            }))
        };
        const res = await axios.post(route('simulator.calculate'), payload);
        simulationResult.value = res.data;
        nextTick(() => document.getElementById('results-container')?.scrollIntoView({ behavior: 'smooth' }));
    } catch (e) { 
        errorMessage.value = e.response?.data?.error || "Ошибка расчета."; 
    } finally { 
        isCalculating.value = false; 
    }
};
</script>

<template>
    <Head title="Contract Simulator" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-100px)] flex gap-6 overflow-hidden">
            
            <div class="w-5/12 flex flex-col bg-[#15171c] border border-white/5 rounded-2xl overflow-hidden shadow-2xl relative z-10">
                
                <div class="p-4 border-b border-white/5 bg-[#1a1c22]">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <span class="text-indigo-500">🔍</span> Браузер предметов
                        </h2>
                        <span class="text-[10px] font-bold bg-white/5 px-2 py-1 rounded text-gray-500">{{ searchResults.length }} items</span>
                    </div>

                    <div class="space-y-3">
                        <div class="relative group">
                            <input v-model="filters.query" type="text" placeholder="Поиск по названию..." class="w-full bg-[#0b0c10] border border-white/10 rounded-xl pl-9 pr-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner">
                            <div class="absolute left-3 top-2.5 text-gray-500 group-hover:text-white transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <select v-model="filters.rarity_id" :disabled="selectedItems.length > 0" class="w-full bg-[#0b0c10] border border-white/10 rounded-xl px-3 py-2 text-xs text-gray-300 focus:ring-0 disabled:opacity-50 cursor-pointer transition hover:border-white/20">
                                    <option value="">✨ Все Редкости</option>
                                    <option v-for="r in rarities" :key="r.id" :value="r.id">{{ r.name }}</option>
                                </select>
                            </div>
                            <div class="relative flex-1">
                                <select v-model="filters.collection_id" class="w-full bg-[#0b0c10] border border-white/10 rounded-xl px-3 py-2 text-xs text-gray-300 focus:ring-0 cursor-pointer transition hover:border-white/20">
                                    <option value="">📦 Все Коллекции</option>
                                    <option v-for="c in collections" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-[#121316] relative">
                    <div v-if="isSearching" class="absolute inset-0 flex items-center justify-center bg-[#121316]/90 z-20 backdrop-blur-sm">
                        <div class="flex flex-col items-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500 mb-2"></div>
                            <span class="text-indigo-500 font-bold text-xs tracking-widest animate-pulse">ЗАГРУЗКА...</span>
                        </div>
                    </div>
                    
                    <div v-else-if="searchResults.length === 0" class="h-full flex flex-col items-center justify-center text-gray-600 opacity-50">
                        <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        <span class="text-xs font-bold uppercase tracking-widest">Ничего не найдено</span>
                    </div>
                    
                    <div v-else class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="item in searchResults" :key="item.id" 
                            class="group bg-[#1a1c22] border border-white/5 hover:border-indigo-500/50 rounded-xl p-3 cursor-pointer transition-all relative overflow-hidden flex flex-col shadow-md hover:shadow-xl hover:-translate-y-1"
                            @click="addItem(item)"
                        >
                            <div class="w-1.5 h-1.5 rounded-full absolute top-2 left-2 shadow-[0_0_8px_currentColor]" :style="{ color: getRarityColor(item), backgroundColor: getRarityColor(item) }"></div>
                            
                            <div class="flex-1 flex items-center justify-center py-2 relative z-0">
                                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/20 opacity-0 group-hover:opacity-100 transition duration-500"></div>
                                <img :src="item.image" class="w-20 h-20 object-contain group-hover:scale-110 transition duration-300 drop-shadow-lg">
                            </div>

                            <div class="mt-2 relative z-10">
                                <div class="text-[9px] text-gray-500 font-bold uppercase truncate tracking-wide mb-0.5">{{ safeCollectionName(item.collection_name) }}</div>
                                <div class="text-[11px] font-bold text-gray-200 truncate group-hover:text-white transition leading-tight">{{ item.name }}</div>
                                <div class="text-[9px] text-emerald-400 font-mono mt-1 font-bold bg-emerald-500/5 inline-block px-1.5 rounded">{{ getPriceRange(item) }}</div>
                                
                                <div class="h-1 bg-gray-800 mt-2 rounded-full w-full relative overflow-hidden border border-white/5">
                                    <div class="absolute h-full bg-gradient-to-r from-emerald-500 via-yellow-500 to-rose-500 opacity-90" :style="getFloatBarStyle(item)"></div>
                                </div>
                            </div>

                            <div class="absolute inset-0 bg-[#15171c]/95 backdrop-blur-sm flex flex-col items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-20">
                                <div class="text-[9px] font-black text-gray-400 tracking-[0.2em] uppercase border-b border-white/10 pb-1 mb-1">Добавить как</div>
                                <div class="grid grid-cols-3 gap-1 w-full px-2">
                                    <button v-for="range in WEAR_RANGES" :key="range.label" 
                                        @click.stop="addItem(item, range)" 
                                        :disabled="!isWearAvailable(item, range)"
                                        class="text-[9px] font-bold py-1.5 rounded border transition relative group/btn"
                                        :class="isWearAvailable(item, range) ? range.color + ' hover:scale-105 bg-opacity-10 bg-[#0b0c10]' : 'border-transparent text-gray-700 cursor-not-allowed bg-white/5'">
                                        {{ range.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-[#15171c] border border-white/5 rounded-2xl overflow-hidden shadow-2xl relative z-10">
                
                <div class="p-6 border-b border-white/5 bg-[#1a1c22] flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-black text-white leading-none tracking-wide flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 text-xs">CS2</span>
                            SIMULATOR
                        </h1>
                    </div>
                    <button v-if="selectedItems.length > 0" @click="clearAll" class="group flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-rose-400 hover:text-white bg-rose-500/10 hover:bg-rose-500 px-4 py-2 rounded-xl transition-all border border-rose-500/20">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Очистить
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar bg-[#0b0c10] p-6">
                    
                    <div class="bg-[#15171c] border border-white/5 rounded-xl overflow-hidden mb-6 relative">
                        <div class="h-1.5 w-full bg-gray-800">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500 shadow-[0_0_10px_#6366f1]" :style="{ width: (selectedItems.length / requiredSlots) * 100 + '%' }"></div>
                        </div>
                        <div class="px-5 py-3 border-b border-white/5 flex justify-between items-center bg-[#181a20]">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Слоты</span>
                                <span class="text-sm font-mono font-bold text-white px-2 py-0.5 rounded bg-white/5" :class="selectedItems.length === requiredSlots ? 'text-emerald-400 bg-emerald-500/10' : ''">{{ selectedItems.length }} / {{ requiredSlots }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Итого</span>
                                <span class="text-sm font-mono font-bold text-emerald-400">{{ formatMoney(totalSum) }}</span>
                            </div>
                        </div>

                        <div class="divide-y divide-white/5 max-h-[350px] overflow-y-auto custom-scrollbar">
                            <div v-if="selectedItems.length === 0" class="py-16 flex flex-col items-center justify-center text-gray-600 opacity-60">
                                <div class="w-16 h-16 border-2 border-dashed border-gray-700 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                </div>
                                <span class="text-xs font-bold uppercase tracking-widest">Добавьте предметы из списка слева</span>
                            </div>

                            <div v-for="(item, index) in selectedItems" :key="item.unique_id" class="flex items-center gap-4 px-5 py-3 hover:bg-[#1a1c22] transition group animate-fade-in-up" :style="{ animationDelay: `${index * 50}ms` }">
                                <div class="w-1 h-10 rounded-full shadow-[0_0_8px_currentColor] opacity-80" :style="{ color: getRarityColor(item), backgroundColor: getRarityColor(item) }"></div>
                                <img :src="item.image" class="w-12 h-12 object-contain drop-shadow-md">
                                
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-gray-200 truncate group-hover:text-white transition mb-0.5">{{ item.name }}</div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider truncate max-w-[150px]">{{ safeCollectionName(item.collection_name) }}</span>
                                        <span v-if="item.wear_label" class="text-[9px] px-1.5 py-0.5 rounded bg-white/5 text-gray-400 font-mono border border-white/5 shadow-sm">{{ item.wear_label }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-end">
                                        <label class="text-[8px] text-gray-500 uppercase font-bold mb-0.5">Float</label>
                                        <input type="number" step="0.0001" v-model="item.float_value" class="w-24 bg-[#0b0c10] border border-white/10 rounded-lg py-1.5 px-2 text-[10px] text-right text-gray-300 font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner">
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <label class="text-[8px] text-gray-500 uppercase font-bold mb-0.5">Price</label>
                                        <input type="number" step="0.01" v-model="item.price" class="w-20 bg-[#0b0c10] border border-white/10 rounded-lg py-1.5 px-2 text-[10px] text-right text-emerald-400 font-mono font-bold focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition shadow-inner">
                                    </div>
                                    <button @click="removeItem(index)" class="text-gray-600 hover:text-rose-500 transition p-2 rounded-lg hover:bg-rose-500/10 ml-1 opacity-0 group-hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="selectedItems.length > 0" class="p-4 bg-[#181a20] border-t border-white/5 flex justify-end">
                             <button @click="calculate" :disabled="selectedItems.length !== requiredSlots || isCalculating"
                                class="relative group overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 disabled:from-gray-800 disabled:to-gray-800 disabled:opacity-50 text-white text-xs font-black uppercase tracking-widest px-10 py-3 rounded-xl transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 active:scale-95 flex items-center gap-2">
                                <div class="absolute inset-0 w-full h-full bg-white/20 -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
                                <span v-if="isCalculating" class="animate-spin text-lg">⚙️</span>
                                <span class="relative z-10">{{ isCalculating ? 'ВЫЧИСЛЯЕМ...' : 'РАССЧИТАТЬ КОНТРАКТ' }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="errorMessage" class="mb-6 text-center text-xs text-rose-400 font-bold bg-rose-500/10 py-3 rounded-xl border border-rose-500/20 animate-bounce shadow-lg shadow-rose-500/5">
                        ⚠️ {{ errorMessage }}
                    </div>

                    <div v-if="simulationResult" id="results-container" class="animate-fade-in-up pb-10">
                        
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                             <div class="bg-[#15171c] border border-white/5 p-4 rounded-xl shadow-lg relative overflow-hidden group">
                                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1">Инвестиции</div>
                                <div class="text-xl font-mono font-black text-white tracking-tight">{{ formatMoney(simulationResult.inputs_cost) }}</div>
                            </div>
                             <div class="bg-[#15171c] border border-white/5 p-4 rounded-xl shadow-lg relative overflow-hidden group">
                                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1">Avg Float</div>
                                <div class="text-xl font-mono font-black text-gray-300 tracking-tight">{{ simulationResult.avg_float.toFixed(7) }}</div>
                            </div>
                            <div class="bg-[#15171c] border border-white/5 p-4 rounded-xl shadow-lg relative overflow-hidden group">
                                <div class="absolute inset-0 opacity-10 bg-gradient-to-br" :class="simulationResult.expected_profit >= 0 ? 'from-emerald-500 to-transparent' : 'from-rose-500 to-transparent'"></div>
                                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1 relative z-10">Ожидание (EV)</div>
                                <div class="text-xl font-mono font-black tracking-tight relative z-10" :class="simulationResult.expected_profit >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ formatMoney(simulationResult.expected_value) }}
                                </div>
                            </div>
                            <div class="bg-[#15171c] border border-white/5 p-4 rounded-xl shadow-lg relative overflow-hidden group">
                                <div class="absolute inset-0 opacity-10 bg-gradient-to-br" :class="simulationResult.roi >= 0 ? 'from-emerald-500 to-transparent' : 'from-rose-500 to-transparent'"></div>
                                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1 relative z-10">ROI %</div>
                                <div class="text-xl font-mono font-black tracking-tight relative z-10" :class="simulationResult.roi >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ simulationResult.roi.toFixed(2) }}%
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 pl-1">
                            <span class="w-6 h-6 rounded bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20 text-xs">🎲</span>
                            <h3 class="text-xs font-black text-white uppercase tracking-widest">Результаты крафта</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            <div v-for="(outcome, i) in simulationResult.outcomes" :key="i" 
                                class="bg-[#15171c] border rounded-xl p-3 relative overflow-hidden flex items-center gap-3 hover:bg-[#1a1c22] transition-all group duration-300 hover:-translate-y-1 hover:shadow-xl"
                                :class="outcome.profit >= 0 ? 'border-emerald-500/20 hover:border-emerald-500/40' : 'border-rose-500/20 hover:border-rose-500/40'"
                            >
                                <div class="absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity duration-500 bg-gradient-to-r" :class="outcome.profit >= 0 ? 'from-emerald-500 to-transparent' : 'from-rose-500 to-transparent'"></div>
                                <div class="absolute bottom-0 left-0 h-0.5 transition-all z-20" :style="{ width: outcome.probability + '%', backgroundColor: outcome.profit >= 0 ? '#10b981' : '#f43f5e' }"></div>

                                <div class="relative w-14 h-14 flex-shrink-0 bg-[#0b0c10] rounded-lg flex items-center justify-center border border-white/5">
                                    <div class="absolute inset-0 opacity-20" :style="{ backgroundColor: getRarityColor(outcome.item) }"></div>
                                    <img :src="outcome.item.image_url" class="w-12 h-12 object-contain relative z-10 drop-shadow-sm group-hover:scale-110 transition">
                                </div>

                                <div class="flex-1 min-w-0 relative z-10">
                                    <div class="flex justify-between items-start mb-0.5">
                                        <div class="text-[9px] font-bold text-gray-500 truncate max-w-[100px] uppercase tracking-wider">{{ safeCollectionName(outcome.item.collection_name) }}</div>
                                        <div class="text-[10px] font-bold px-1.5 py-0.5 rounded border shadow-sm" :class="outcome.profit >= 0 ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' : 'text-rose-400 bg-rose-500/10 border-rose-500/20'">
                                            {{ outcome.probability.toFixed(1) }}%
                                        </div>
                                    </div>
                                    <div class="text-xs font-bold text-white truncate mb-1" :style="{ color: getRarityColor(outcome.item) }">{{ outcome.item.name }}</div>
                                    <div class="flex justify-between items-center bg-[#0b0c10]/50 rounded p-1 border border-white/5">
                                        <div class="flex gap-1.5 text-[9px] text-gray-400 font-mono">
                                            <span class="text-white">{{ outcome.wear_name }}</span>
                                            <span class="text-gray-600">{{ outcome.float_value.toFixed(5) }}</span>
                                        </div>
                                        <div class="text-[10px] font-bold font-mono" :class="outcome.profit >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ formatMoney(outcome.price) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; border: 1px solid #0b0c10; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #374151; }
.animate-fade-in-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>