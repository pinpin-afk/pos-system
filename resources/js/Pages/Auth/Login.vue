<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Admin" />
        <form class="rounded-[28px] bg-white p-8 shadow-[0_16px_40px_rgb(15,23,42,0.04)]" @submit.prevent="submit">
            <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Admin</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Masuk Admin</h1>
            <p class="auth-muted mt-1 text-sm">Kelola toko, stok, dan laporan.</p>

            <label class="mt-6 block text-sm font-medium">Email</label>
            <input v-model="form.email" type="email" class="input-soft mt-1 w-full" required>
            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>

            <label class="mt-4 block text-sm font-medium">Password</label>
            <input v-model="form.password" type="password" class="input-soft mt-1 w-full" required>
            <p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p>

            <div class="mt-4 flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-teal-600">
                    Ingat saya
                </label>
                <Link href="/forgot-password" class="text-sm font-semibold text-teal-700">Lupa password?</Link>
            </div>

            <button type="submit" class="btn-primary mt-6 w-full" :disabled="form.processing">
                Masuk Admin
            </button>

            <p class="auth-muted mt-5 text-center text-sm">
                Kasir?
                <Link href="/kasir/login" class="font-semibold text-teal-700">Masuk di sini</Link>
            </p>
        </form>
    </GuestLayout>
</template>
