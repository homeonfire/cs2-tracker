<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    inventory: Object,
    items: Array,
    stats: Object,
    last_updated: String
});

const isRefreshing = ref(false);

const refreshInventory = () => {
    isRefreshing.value = true;
    router.post(route('inventories.refresh', props.inventory.id), {}, {
        onFinish: () => isRefreshing.value = false
    });
};

// Функция форматирования больше не обязательна для цены, т.к. она приходит готовая с бэка,
// но пригодится для статистики сверху
const formatMoney = (val) => {
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head :title="inventory.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('inventories.index')" class="p-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 transition">
                        ←
                    </Link>
                    <div>
                        <h1 class="text-3xl font-black text-white italic tracking-tight uppercase">
                            {{ inventory.name }}
                        </h1>
                        <div class="flex items-center gap-3 text-xs font-mono text-gray-500 mt-1">
                            <span>{{ inventory.steam_id }}</span>
                            <span class="w-1 h-1 rounded-full bg-gray-600"></span>
                            <button @click="refreshInventory" :disabled="isRefreshing" class="flex items-center gap-1 hover:text-indigo-400 transition" :class="{'opacity-50': isRefreshing}">
                                <svg class="w-3 h-3" :class="{'animate-spin': isRefreshing}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                {{ isRefreshing ? 'ОБНОВЛЕНИЕ...' : 'ОБНОВИТЬ' }}
                            </button>
                            <span>{{ last_updated }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 bg-[#15171c] p-1.5 rounded-xl border border-gray-800">
                    <div class="px-4 py-2 rounded-lg bg-gray-800/50">
                        <div class="text-[10px] uppercase font-bold text-gray-500">Стоимость</div>
                        <div class="text-xl font-bold text-white">${{ stats.value }}</div>
                    </div>
                    <div class="px-4 py-2 rounded-lg bg-gray-800/50">
                        <div class="text-[10px] uppercase font-bold text-gray-500">Вложено</div>
                        <div class="text-xl font-bold text-white">${{ stats.invested }}</div>
                    </div>
                    <div class="px-4 py-2 rounded-lg" :class="stats.is_positive ? 'bg-green-500/10' : 'bg-red-500/10'">
                        <div class="text-[10px] uppercase font-bold" :class="stats.is_positive ? 'text-green-500' : 'text-red-500'">
                            Профит ({{ stats.roi }}%)
                        </div>
                        <div class="text-xl font-bold" :class="stats.is_positive ? 'text-green-400' : 'text-red-400'">
                            {{ stats.is_positive ? '+' : '' }}${{ stats.profit }} ↗
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 pb-10">
            <Link 
                v-for="item in items" 
                :key="item.id"
                :href="route('inventories.item', item.id)"
                class="group bg-[#15171c] border border-gray-800 rounded-xl p-3 transition-all duration-200 hover:-translate-y-1 relative overflow-hidden flex flex-col justify-between"
                :class="{'hover:shadow-lg': true}"
                :style="{ background: item.rarity_color ? `#${item.rarity_color}` : '#10b981' , borderBottomWidth: '3px' }"
            >
                <div 
                    class="absolute inset-0 opacity-0 group-hover:opacity-10 transition duration-500 pointer-events-none"
                    :style="{ background: `radial-gradient(circle at center, ${item.rarity_color || '#ffffff'}, transparent 70%)` }"
                ></div>

                <div class="aspect-[4/3] flex items-center justify-center mb-2 relative z-10">
                    <img :src="item.image" :alt="item.name" class="w-full h-full object-contain drop-shadow-lg group-hover:scale-110 transition duration-300">
                    
                    <div v-if="item.name.includes('StatTrak')" class="absolute top-0 right-0 bg-orange-500 text-black text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm">ST</div>
                </div>

                <div class="z-10 relative">
                    <div class="mb-2">
                        <h3 
                            class="text-xs font-bold text-gray-300 truncate transition duration-200"
                            :style="{ color: item.rarity_color }" 
                        >
                            {{ item.name.split('|')[0] }}
                        </h3>
                        <p class="text-[10px] text-gray-500 truncate">{{ item.name.split('|')[1] || item.market_hash_name }}</p>
                    </div>

                    <div class="flex justify-between items-end border-t border-gray-800 pt-2 mt-auto">
                        <div>
                            <div class="text-[9px] font-bold text-gray-600 uppercase">Цена</div>
                            <div class="text-sm font-bold font-mono text-white">
                                {{ item.price_formatted }}
                            </div>
                        </div>
                        <div class="w-6 h-6 rounded bg-gray-800 flex items-center justify-center text-gray-500 group-hover:bg-indigo-500 group-hover:text-white transition">
                            +
                        </div>
                    </div>
                </div>
            </Link>
        </div>
        
        <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-500">
            <div class="text-4xl mb-4">👻</div>
            <p>В этом инвентаре пока пусто или Steam скрыл предметы.</p>
        </div>

    </AuthenticatedLayout>
</template>