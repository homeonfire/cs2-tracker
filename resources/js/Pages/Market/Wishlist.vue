<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    items: Object
});

// Функция удаления из избранного
const removeFromWishlist = (item) => {
    if (confirm('Убрать из избранного?')) {
        router.post(route('market.wishlist', item.id));
    }
};
</script>

<template>
    <Head title="Мое Избранное" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="font-bold text-2xl text-white uppercase italic">
                    Мое <span class="text-pink-500">Избранное</span>
                </h2>
                <div class="bg-pink-500/10 text-pink-400 border border-pink-500/20 px-3 py-1 rounded text-xs font-mono">
                    {{ items.total }} ITEMS
                </div>
            </div>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto min-h-screen">
            
            <div v-if="items.data.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                <div class="bg-[#1e2128] p-6 rounded-full mb-6 shadow-2xl shadow-pink-500/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">Список пуст</h3>
                <p class="text-gray-400 mb-8 max-w-md">Вы пока не добавили ни одного скина в избранное. Перейдите в каталог, чтобы найти интересные предметы.</p>
                <Link :href="route('market.index')" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition shadow-lg shadow-indigo-500/20">
                    Перейти на Рынок
                </Link>
            </div>

            <div v-else>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 mb-8">
                    <div 
                        v-for="item in items.data" 
                        :key="item.id"
                        class="bg-[#1e2128] rounded-xl p-3 hover:bg-[#252932] transition relative group border-b-[3px]"
                        :style="{ borderColor: item.rarity_color ? `#${item.rarity_color}` : '#4b5563' }"
                    >
                        <button 
                            @click="removeFromWishlist(item)"
                            class="absolute top-2 right-2 z-20 p-2 rounded-full bg-black/40 hover:bg-red-500 text-gray-400 hover:text-white transition backdrop-blur-sm"
                            title="Убрать из избранного"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="aspect-[1.3] flex items-center justify-center mb-3 bg-black/20 rounded-lg relative overflow-hidden">
                            <img :src="item.image" loading="lazy" class="max-h-[90%] w-auto drop-shadow-2xl z-10 group-hover:scale-110 transition-transform duration-300">
                        </div>

                        <div class="space-y-1">
                            <div class="font-medium text-gray-200 text-sm truncate">{{ item.name }}</div>
                            <div class="font-mono text-green-400 font-bold text-sm">{{ item.price_formatted }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="items.links.length > 3" class="flex justify-center gap-1">
                    <component 
                        :is="link.url ? 'Link' : 'span'"
                        v-for="(link, index) in items.links" 
                        :key="index"
                        :href="link.url"
                        class="px-3 py-1 rounded text-sm transition"
                        :class="{ 'bg-pink-600 text-white': link.active, 'text-gray-400 hover:text-white': !link.active && link.url }"
                        v-html="link.label"
                    />
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>