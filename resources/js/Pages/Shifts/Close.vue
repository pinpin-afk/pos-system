<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils/money';

const props = defineProps({
    shift: Object,
    expectedCash: Number,
});

const form = useForm({
    actual_cash: props.expectedCash,
});
</script>

<template>
    <GuestLayout>
        <Head title="Tutup Shift" />
        <form class="rounded-2xl bg-white p-8 shadow-xl" @submit.prevent="form.post('/shifts/close')">
            <h1 class="text-2xl font-semibold">Tutup Shift</h1>
            <p class="mt-3 text-sm text-slate-500">Modal awal: {{ formatRupiah(shift.opening_cash) }}</p>
            <p class="text-sm text-slate-500">Kas diharapkan: {{ formatRupiah(expectedCash) }}</p>
            <label class="mt-6 block text-sm font-medium">Kas aktual</label>
            <input v-model="form.actual_cash" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <p class="mt-2 text-sm">Selisih: {{ formatRupiah(Number(form.actual_cash || 0) - expectedCash) }}</p>
            <button class="mt-6 w-full rounded-lg bg-teal-700 py-2.5 font-semibold text-white">Tutup Shift</button>
        </form>
    </GuestLayout>
</template>
