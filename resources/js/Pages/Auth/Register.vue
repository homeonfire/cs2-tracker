<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Регистрация" />

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block font-medium text-sm text-gray-300">Имя (Никнейм)</label>
                <input 
                    v-model="form.name"
                    type="text" 
                    required 
                    autofocus
                    class="mt-1 block w-full bg-[#131519] border border-gray-600 rounded-lg text-white focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                >
                <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
            </div>

            <div>
                <label class="block font-medium text-sm text-gray-300">Email</label>
                <input 
                    v-model="form.email"
                    type="email" 
                    required 
                    class="mt-1 block w-full bg-[#131519] border border-gray-600 rounded-lg text-white focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                >
                <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
            </div>

            <div>
                <label class="block font-medium text-sm text-gray-300">Пароль</label>
                <input 
                    v-model="form.password"
                    type="password" 
                    required 
                    class="mt-1 block w-full bg-[#131519] border border-gray-600 rounded-lg text-white focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                >
                <div v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</div>
            </div>

            <div>
                <label class="block font-medium text-sm text-gray-300">Подтвердите пароль</label>
                <input 
                    v-model="form.password_confirmation"
                    type="password" 
                    required 
                    class="mt-1 block w-full bg-[#131519] border border-gray-600 rounded-lg text-white focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                >
                <div v-if="form.errors.password_confirmation" class="text-red-400 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
            </div>

            <button 
                class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition shadow-lg shadow-indigo-500/20 disabled:opacity-50"
                :disabled="form.processing"
            >
                Зарегистрироваться
            </button>

            <div class="text-center mt-4">
                <span class="text-sm text-gray-500">Уже есть аккаунт? </span>
                <Link :href="route('login')" class="text-sm text-indigo-400 hover:text-indigo-300 font-bold">
                    Войти
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>