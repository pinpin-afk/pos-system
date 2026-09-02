<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
});

function submit() {
    form.post('/forgot-password');
}
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />
        <form class="rounded-[28px] bg-white p-8 shadow-[0_16px_40px_rgb(15,23,42,0.04)]" @submit.prevent="submit">
            <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Akun</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Lupa password</h1>
            <p class="auth-muted mt-1 text-sm">Kami kirim tautan reset ke email yang terdaftar.</p>

            <p v-if="status" class="mt-4 rounded-2xl bg-teal-50 px-4 py-3 text-sm text-teal-800">{{ status }}</p>

            <label class="mt-6 block text-sm font-medium">Email</label>
            <input v-model="form.email" type="email" class="input-soft mt-1 w-full" required>
            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>

            <button type="submit" class="btn-primary mt-6 w-full" :disabled="form.processing">
                Kirim tautan reset
            </button>

            <p class="auth-muted mt-5 text-center text-sm">
                <Link href="/login" class="font-semibold text-teal-700">Kembali masuk</Link>
            </p>
        </form>
    </GuestLayout>
</template>
