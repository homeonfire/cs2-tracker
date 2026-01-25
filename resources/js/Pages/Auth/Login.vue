<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход в аккаунт" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block font-medium text-sm text-gray-300">Email</label>
                <input 
                    v-model="form.email"
                    type="email" 
                    required 
                    autofocus
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

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="bg-[#131519] border-gray-600 text-indigo-600" />
                    <span class="ms-2 text-sm text-gray-400">Запомнить меня</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline text-sm text-gray-400 hover:text-white rounded-md focus:outline-none"
                >
                    Забыли пароль?
                </Link>
            </div>
            
            <button 
                class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition shadow-lg shadow-indigo-500/20 disabled:opacity-50"
                :disabled="form.processing"
            >
                Войти
            </button>

            <div class="text-center mt-4">
                <span class="text-sm text-gray-500">Нет аккаунта? </span>
                <Link :href="route('register')" class="text-sm text-indigo-400 hover:text-indigo-300 font-bold">
                    Создать
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>