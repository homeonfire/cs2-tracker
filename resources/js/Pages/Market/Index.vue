<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    items: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const performSearch = debounce((value) => {
    router.get(route('market.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 500);

watch(search, (value) => {
    performSearch(value);
});

const toggleWishlist = (item) => {
    router.post(route('market.wishlist', item.id), {}, { preserveScroll: true });
};

const getBestPrice = (item) => {
    const prices = [parseFloat(item.price_skinport), parseFloat(item.price_dmarket), parseFloat(item.price_steam)];
    const validPrices = prices.filter(p => !isNaN(p) && p > 0);
    if (validPrices.length === 0) return null;
    return Math.min(...validPrices);
};

const formatPrice = (val) => {
    if (val === null) return 'Нет предложений';
    return '$' + val.toFixed(2);
};
</script>

<template>
    <Head title="База Скинов" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="text-3xl font-black text-white italic tracking-tight uppercase">
                        База <span class="text-emerald-500">Скинов</span>
                    </h1>
                    <p class="text-xs text-gray-500 font-mono mt-1">
                        Глобальная База • {{ items.total }} Предметов
                    </p>
                </div>
                <div class="w-full md:max-w-md relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-500 group-focus-within:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input 
                        v-model="search"
                        type="text" 
                        class="block w-full pl-12 pr-4 py-3 bg-[#15171c] border border-gray-700 rounded-xl text-gray-200 placeholder-gray-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all shadow-lg shadow-black/20"
                        placeholder="Поиск скина (например: AK-47)..."
                    >
                </div>
            </div>
        </template>

        <div class="pb-10">
            <div v-if="items.data.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-500">
                <svg class="w-16 h-16 mb-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-lg font-medium">Ничего не найдено</p>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-4">
                <div v-for="item in items.data" :key="item.id" class="group bg-[#15171c] border border-gray-800 hover:border-emerald-500/50 rounded-xl p-3 transition-all duration-300 relative hover:-translate-y-1 hover:shadow-2xl hover:shadow-emerald-900/10 flex flex-col">
                    <button @click.prevent="toggleWishlist(item)" class="absolute top-2 right-2 z-20 p-2 rounded-full bg-black/20 hover:bg-black/50 backdrop-blur-sm transition-all group/heart">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-colors duration-300" :class="item.is_favorite ? 'text-red-500 fill-red-500' : 'text-gray-400 group-hover/heart:text-red-400'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                    <div class="absolute bottom-0 left-3 right-3 h-[2px] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300" :style="{ background: item.rarity_color ? `#${item.rarity_color}` : '#10b981' }"></div>
                    <div class="aspect-[1.3] flex items-center justify-center mb-3 relative overflow-hidden rounded-lg bg-gradient-to-b from-gray-800/30 to-transparent">
                         <div class="absolute inset-0 opacity-0 group-hover:opacity-20 transition-opacity duration-500" :style="{ background: `radial-gradient(circle at center, #${item.rarity_color || '10b981'} 0%, transparent 70%)` }"></div>
                        <img :src="item.image_url" :alt="item.name" loading="lazy" class="max-h-[85%] w-auto drop-shadow-lg z-10 group-hover:scale-110 transition-transform duration-300 ease-out select-none">
                    </div>
                    <div class="mt-auto space-y-1">
                        <div class="font-medium text-gray-200 text-xs truncate leading-tight" :title="item.market_hash_name">{{ item.market_hash_name }}</div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-800/50">
                            <div>
                                <div class="text-[9px] text-gray-600 font-bold uppercase tracking-wider">От</div>
                                <div class="text-sm font-mono font-bold text-white group-hover:text-emerald-400 transition-colors">
                                    {{ formatPrice(getBestPrice(item)) }}
                                </div>
                            </div>
                            <div class="opacity-50 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="items.links && items.links.length > 3" class="mt-10 flex justify-center">
                <div class="flex flex-wrap gap-1 bg-[#15171c] p-1.5 rounded-xl border border-gray-800 shadow-lg">
                    <template v-for="(link, key) in items.links" :key="key">
                        <div v-if="link.url === null" class="px-3 py-2 text-xs text-gray-600 border border-transparent rounded-lg" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-3 py-2 text-xs font-bold border rounded-lg transition-all duration-200" :class="link.active ? 'bg-emerald-600 text-white border-emerald-500 shadow-lg shadow-emerald-500/20' : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-800 hover:text-white'" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>