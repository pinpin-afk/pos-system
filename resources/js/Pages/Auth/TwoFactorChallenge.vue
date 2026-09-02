<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    code: '',
    recovery_code: '',
});

function submit() {
    form.post('/two-factor-challenge');
}
</script>

<template>
    <GuestLayout>
        <Head title="Kode 2FA" />
        <form class="rounded-[28px] bg-white p-8 shadow-[0_16px_40px_rgb(15,23,42,0.04)]" @submit.prevent="submit">
            <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Keamanan</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Kode autentikasi</h1>
            <p class="auth-muted mt-1 text-sm">Masukkan kode dari aplikasi authenticator.</p>

            <label class="mt-6 block text-sm font-medium">Kode OTP</label>
            <input v-model="form.code" class="input-soft mt-1 w-full" autocomplete="one-time-code" inputmode="numeric">
            <p v-if="form.errors.code" class="mt-1 text-sm text-rose-600">{{ form.errors.code }}</p>

            <label class="mt-4 block text-sm font-medium">Atau recovery code</label>
            <input v-model="form.recovery_code" class="input-soft mt-1 w-full">
            <p v-if="form.errors.recovery_code" class="mt-1 text-sm text-rose-600">{{ form.errors.recovery_code }}</p>

            <button type="submit" class="btn-primary mt-6 w-full" :disabled="form.processing">Lanjutkan</button>
        </form>
    </GuestLayout>
</template>
