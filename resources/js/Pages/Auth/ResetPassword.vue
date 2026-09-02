<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password');
}
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />
        <form class="rounded-[28px] bg-white p-8 shadow-[0_16px_40px_rgb(15,23,42,0.04)]" @submit.prevent="submit">
            <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Akun</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Password baru</h1>
            <p class="auth-muted mt-1 text-sm">Masukkan password baru untuk akun ini.</p>

            <label class="mt-6 block text-sm font-medium">Email</label>
            <input v-model="form.email" type="email" class="input-soft mt-1 w-full" required>
            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>

            <label class="mt-4 block text-sm font-medium">Password baru</label>
            <input v-model="form.password" type="password" class="input-soft mt-1 w-full" required>
            <p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p>

            <label class="mt-4 block text-sm font-medium">Ulangi password</label>
            <input v-model="form.password_confirmation" type="password" class="input-soft mt-1 w-full" required>

            <button type="submit" class="btn-primary mt-6 w-full" :disabled="form.processing">
                Simpan password
            </button>
        </form>
    </GuestLayout>
</template>
