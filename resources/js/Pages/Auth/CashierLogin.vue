<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const mode = ref('email');

const form = useForm({
    mode: 'email',
    email: '',
    password: '',
    pin: '',
    card_number: '',
    remember: false,
});

function setMode(value) {
    mode.value = value;
    form.mode = value;
}

function submit() {
    form.mode = mode.value;
    form.post('/kasir/login');
}
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Kasir" />
        <form class="rounded-[28px] bg-white p-8 shadow-[0_16px_40px_rgb(15,23,42,0.04)]" @submit.prevent="submit">
            <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Kasir</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Masuk Kasir</h1>
            <p class="auth-muted mt-1 text-sm">Email, PIN, atau kartu karyawan.</p>

            <div class="mt-6 grid grid-cols-3 gap-2">
                <button type="button" class="rounded-xl border py-2 text-sm" :class="mode === 'email' ? 'border-teal-600 bg-teal-50' : 'border-slate-200'" @click="setMode('email')">Email</button>
                <button type="button" class="rounded-xl border py-2 text-sm" :class="mode === 'pin' ? 'border-teal-600 bg-teal-50' : 'border-slate-200'" @click="setMode('pin')">PIN</button>
                <button type="button" class="rounded-xl border py-2 text-sm" :class="mode === 'card' ? 'border-teal-600 bg-teal-50' : 'border-slate-200'" @click="setMode('card')">Kartu</button>
            </div>

            <template v-if="mode === 'email'">
                <label class="mt-6 block text-sm font-medium">Email</label>
                <input v-model="form.email" type="email" class="input-soft mt-1 w-full" required>
                <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>

                <label class="mt-4 block text-sm font-medium">Password</label>
                <input v-model="form.password" type="password" class="input-soft mt-1 w-full" required>
                <p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p>
            </template>

            <template v-else-if="mode === 'pin'">
                <label class="mt-6 block text-sm font-medium">PIN kasir</label>
                <input v-model="form.pin" inputmode="numeric" class="input-soft mt-1 w-full" required>
                <p v-if="form.errors.pin" class="mt-1 text-sm text-rose-600">{{ form.errors.pin }}</p>
            </template>

            <template v-else>
                <label class="mt-6 block text-sm font-medium">Nomor kartu</label>
                <input v-model="form.card_number" class="input-soft mt-1 w-full" required>
                <p v-if="form.errors.card_number" class="mt-1 text-sm text-rose-600">{{ form.errors.card_number }}</p>
            </template>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-teal-600">
                Ingat saya
            </label>

            <button type="submit" class="btn-primary mt-6 w-full" :disabled="form.processing">
                Masuk Kasir
            </button>

            <p class="auth-muted mt-5 text-center text-sm">
                Admin?
                <Link href="/login" class="font-semibold text-teal-700">Masuk di sini</Link>
            </p>
        </form>
    </GuestLayout>
</template>
