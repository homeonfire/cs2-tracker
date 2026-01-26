<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue'; 
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    inventories: Array,
});

const formatPrice = (value) => {
    let val = parseFloat(value);
    if (isNaN(val)) val = 0;
    return '$' + val.toFixed(2);
};

const showCreateModal = ref(false);
const form = useForm({
    steam_id: '',
    name: '',
});

const submit = () => {
    form.post(route('inventories.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Мои Портфели" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center gap-4">
                <h1 class="text-3xl font-black text-white italic tracking-tight uppercase">
                    Мои <span class="text-indigo-500">Портфели</span>
                </h1>
                <button 
                    @click="showCreateModal = true"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-lg shadow-indigo-500/20 flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Добавить
                </button>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 w-full pb-10">
            
            <Link 
                v-for="inventory in inventories" 
                :key="inventory.id" 
                :href="route('inventories.show', inventory.id)"
                class="group bg-[#15171c] border border-gray-800 hover:border-indigo-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-indigo-500/10 flex flex-col justify-between h-[220px]"
            >
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 border border-white/5 flex items-center justify-center text-2xl shadow-inner group-hover:scale-110 transition">📦</div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Стоимость</div>
                        <div class="text-3xl font-mono font-bold text-white group-hover:text-indigo-400 transition tracking-tight">
                            {{ formatPrice(inventory.total_value) }}
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-lg text-white mb-1 truncate">{{ inventory.name }}</h3>
                    <p class="text-xs text-gray-500 font-mono mb-4 flex items-center gap-1">
                        <span>ID:</span> {{ inventory.steam_id }}
                    </p>
                    
                    <div class="pt-4 border-t border-gray-800 flex justify-between items-center">
                        <span class="text-xs text-gray-500">{{ inventory.items_count || 0 }} предметов</span>
                        <span class="text-xs text-indigo-500 font-bold opacity-0 group-hover:opacity-100 transition-opacity transform translate-x-[-10px] group-hover:translate-x-0 duration-300">Открыть →</span>
                    </div>
                </div>
            </Link>

            <button 
                @click="showCreateModal = true"
                class="border border-dashed border-gray-800 hover:border-indigo-500/50 hover:bg-[#15171c] rounded-2xl flex flex-col items-center justify-center text-gray-600 hover:text-indigo-400 transition h-[220px] group"
            >
                <div class="w-14 h-14 rounded-full bg-gray-800/50 group-hover:bg-indigo-500/20 flex items-center justify-center mb-3 transition"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></div>
                <span class="font-bold text-sm">Добавить портфель</span>
            </button>

        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6 bg-[#1e2128] text-white">
                <h2 class="text-lg font-bold mb-4">Добавить портфель</h2>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Название" class="text-gray-400"/>
                        <TextInput v-model="form.name" class="w-full bg-[#131519] border-gray-700 text-white mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Steam ID 64" class="text-gray-400"/>
                        <TextInput v-model="form.steam_id" class="w-full bg-[#131519] border-gray-700 text-white mt-1" placeholder="765611..." />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showCreateModal = false" class="px-4 py-2 bg-gray-700 rounded-lg text-sm">Отмена</button>
                    <PrimaryButton @click="submit" :disabled="form.processing">Сохранить</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>