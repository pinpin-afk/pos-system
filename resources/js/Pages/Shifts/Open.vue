<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    opening_cash: 0,
});
</script>

<template>
    <GuestLayout>
        <Head title="Buka Shift" />
        <form class="rounded-2xl bg-white p-8 shadow-xl" @submit.prevent="form.post('/shifts')">
            <p class="text-xs uppercase tracking-widest text-teal-700">Kasir</p>
            <h1 class="mt-2 text-2xl font-semibold">Buka Shift</h1>
            <p class="mt-1 text-sm text-slate-500">Masukkan modal kas awal sebelum mulai transaksi.</p>
            <label class="mt-6 block text-sm font-medium">Modal awal</label>
            <input v-model="form.opening_cash" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <p v-if="form.errors.opening_cash" class="mt-1 text-sm text-rose-600">{{ form.errors.opening_cash }}</p>
            <button class="mt-6 w-full rounded-lg bg-teal-700 py-2.5 font-semibold text-white" :disabled="form.processing">Buka Shift</button>
        </form>
    </GuestLayout>
</template>
