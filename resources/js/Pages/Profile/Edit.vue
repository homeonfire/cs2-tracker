<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Профиль" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-3xl text-white italic tracking-tight uppercase">
                Настройки <span class="text-indigo-500">Профиля</span>
            </h2>
        </template>

        <div class="pb-12 space-y-6">
            
            <div class="bg-[#15171c] border border-gray-800 rounded-2xl p-8 flex flex-col md:flex-row items-center gap-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600/10 blur-[80px] rounded-full pointer-events-none"></div>

                <div class="w-24 h-24 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center shadow-2xl shadow-indigo-500/20 text-4xl font-bold text-white relative z-10">
                    {{ $page.props.auth.user.name.charAt(0) }}
                </div>

                <div class="text-center md:text-left relative z-10">
                    <h3 class="text-2xl font-bold text-white mb-1">{{ $page.props.auth.user.name }}</h3>
                    <p class="text-gray-500 font-mono text-sm mb-4">{{ $page.props.auth.user.email }}</p>
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                        Аккаунт Активен
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                
                <div class="space-y-6">
                    <div class="p-4 sm:p-8 bg-[#15171c] border border-gray-800 rounded-2xl shadow-lg relative overflow-hidden">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl relative z-10"
                        />
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-4 sm:p-8 bg-[#15171c] border border-gray-800 rounded-2xl shadow-lg">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>

                    <div class="p-4 sm:p-8 bg-[#15171c] border border-red-900/30 rounded-2xl shadow-lg">
                        <DeleteUserForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>