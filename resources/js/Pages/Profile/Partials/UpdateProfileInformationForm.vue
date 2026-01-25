<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                Информация профиля
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Обновите имя вашей учетной записи и адрес электронной почты.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Имя" class="text-gray-400" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-[#0f1115] border-gray-700 text-white focus:border-indigo-500 focus:ring-indigo-500 placeholder-gray-600"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-gray-400" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-[#0f1115] border-gray-700 text-white focus:border-indigo-500 focus:ring-indigo-500 placeholder-gray-600"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-gray-200">
                    Ваш адрес электронной почты не подтвержден.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-gray-400 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Нажмите здесь, чтобы отправить письмо с подтверждением повторно.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 font-medium text-sm text-green-400"
                >
                    Новая ссылка для подтверждения была отправлена на ваш адрес электронной почты.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton 
                    :disabled="form.processing" 
                    class="bg-indigo-600 hover:bg-indigo-500 border-none px-6 py-3 text-sm font-bold shadow-lg shadow-indigo-500/20"
                >
                    Сохранить
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-400 font-medium">Сохранено.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>