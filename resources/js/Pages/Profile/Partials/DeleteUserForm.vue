<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-bold text-red-500 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                Удалить аккаунт
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                После удаления вашей учетной записи все ее ресурсы и данные будут удалены безвозвратно.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion" class="bg-red-600 hover:bg-red-500 border-none shadow-lg shadow-red-500/20">
            Удалить аккаунт
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 bg-[#1e2128] border border-gray-700">
                <h2 class="text-lg font-medium text-white">
                    Вы уверены, что хотите удалить свою учетную запись?
                </h2>

                <p class="mt-1 text-sm text-gray-400">
                    После удаления вашей учетной записи все ее ресурсы и данные будут удалены безвозвратно. Пожалуйста, введите свой пароль, чтобы подтвердить, что вы хотите навсегда удалить свою учетную запись.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Пароль" class="sr-only" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4 bg-[#0f1115] border-gray-600 text-white focus:border-red-500 focus:ring-red-500"
                        placeholder="Пароль"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" class="bg-gray-700 text-gray-200 border-gray-600 hover:bg-gray-600"> 
                        Отмена 
                    </SecondaryButton>

                    <DangerButton
                        class="ml-3 bg-red-600 hover:bg-red-500 border-none"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Удалить аккаунт
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>