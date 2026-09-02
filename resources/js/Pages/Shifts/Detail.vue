<script setup>
import { formatRupiah } from '@/utils/money';

defineProps({
    shift: Object,
    expectedCash: Number,
});

function movementLabel(type) {
    return {
        in: 'Kas masuk',
        out: 'Kas keluar',
    }[type] ?? type;
}
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <p class="text-xs text-slate-500">Modal</p>
                <p class="mt-1 font-semibold">{{ formatRupiah(shift.opening_cash) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <p class="text-xs text-slate-500">Kas diharapkan</p>
                <p class="mt-1 font-semibold">{{ formatRupiah(expectedCash) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <p class="text-xs text-slate-500">Kas aktual</p>
                <p class="mt-1 font-semibold">{{ shift.actual_cash === null ? '-' : formatRupiah(shift.actual_cash) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <p class="text-xs text-slate-500">Selisih</p>
                <p class="mt-1 font-semibold" :class="Number(shift.difference) < 0 ? 'text-rose-500' : ''">
                    {{ shift.difference === null ? '-' : formatRupiah(shift.difference) }}
                </p>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold">Kas masuk/keluar</h3>
            <div class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-100 dark:divide-white/10 dark:border-white/10">
                <p
                    v-for="movement in shift.cash_movements"
                    :key="movement.id"
                    class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                >
                    <span>
                        {{ movementLabel(movement.type) }}
                        <span class="text-slate-400"> · {{ movement.reason }}</span>
                    </span>
                    <span class="font-semibold tabular-nums">{{ formatRupiah(movement.amount) }}</span>
                </p>
                <p v-if="!shift.cash_movements?.length" class="px-4 py-6 text-center text-sm text-slate-500">
                    Tidak ada pergerakan kas.
                </p>
            </div>
        </div>
    </div>
</template>
