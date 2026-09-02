<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { formatNumber, formatRupiah } from '@/utils/money';
import { paymentLabel, saleStatusLabel } from '@/utils/labels';
import { computed } from 'vue';

const props = defineProps({
    sale: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);

const refundForm = useForm({
    reason: '',
    items: (props.sale.items ?? []).map((item) => ({
        sale_item_id: item.id,
        quantity: item.quantity,
        name: item.product_name,
    })),
});

const voidForm = useForm({
    reason: '',
});

function can(permission) {
    return permissions.value.includes(permission);
}

function paymentTone(method) {
    return {
        cash: 'teal',
        qris: 'sky',
        transfer: 'violet',
        card: 'amber',
        points: 'emerald',
        ewallet: 'sky',
        other: 'slate',
    }[method] ?? 'slate';
}

function submitRefund() {
    refundForm.post(`/sales/${props.sale.id}/refund`);
}

function submitVoid() {
    if (confirm('Void transaksi ini? Stok akan dikembalikan.')) {
        voidForm.post(`/sales/${props.sale.id}/void`);
    }
}
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <template v-if="sale.payments?.length || sale.payment">
                    <StatusBadge
                        v-for="payment in (sale.payments?.length ? sale.payments : [sale.payment])"
                        :key="payment.id ?? payment.method"
                        :tone="paymentTone(payment.method)"
                    >
                        {{ paymentLabel(payment.method) }}
                    </StatusBadge>
                </template>
                <StatusBadge v-else tone="slate">-</StatusBadge>
                <StatusBadge tone="slate">{{ saleStatusLabel(sale.status) }}</StatusBadge>
                <StatusBadge tone="slate">{{ sale.items?.length || 0 }} item</StatusBadge>
            </div>
            <Link :href="`/receipts/${sale.id}`" class="btn-primary">Lihat struk</Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-100 dark:border-white/10">
            <table class="admin-table admin-table-compact">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Harga</th>
                        <th class="text-right">Diskon</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in sale.items" :key="item.id">
                        <td class="font-semibold">
                            {{ item.product_name }}
                            <span v-if="item.variant_name" class="text-xs text-slate-400"> · {{ item.variant_name }}</span>
                        </td>
                        <td class="text-right tabular-nums">{{ formatNumber(item.quantity) }}</td>
                        <td class="text-right tabular-nums">{{ formatRupiah(item.selling_price) }}</td>
                        <td class="text-right tabular-nums">{{ formatRupiah(item.discount_amount) }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ formatRupiah(item.subtotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="ml-auto grid w-full gap-2 text-sm sm:w-80">
            <div class="flex justify-between text-slate-500 dark:text-slate-400">
                <span>Subtotal</span>
                <span class="tabular-nums">{{ formatRupiah(sale.subtotal) }}</span>
            </div>
            <div class="flex justify-between text-slate-500 dark:text-slate-400">
                <span>Pajak</span>
                <span class="tabular-nums">{{ formatRupiah(sale.tax) }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-100 pt-2 font-semibold dark:border-white/10">
                <span>Total</span>
                <span class="tabular-nums">{{ formatRupiah(sale.grand_total) }}</span>
            </div>
            <div
                v-for="payment in sale.payments"
                :key="payment.id"
                class="flex justify-between text-slate-500 dark:text-slate-400"
            >
                <span>{{ paymentLabel(payment.method) }}</span>
                <span class="tabular-nums">{{ formatRupiah(payment.amount) }}</span>
            </div>
            <div v-if="Number(sale.refunded_amount)" class="flex justify-between text-rose-600">
                <span>Sudah refund</span>
                <span class="tabular-nums">{{ formatRupiah(sale.refunded_amount) }}</span>
            </div>
        </div>

        <form v-if="can('sales.refund') && ['completed', 'partially_refunded'].includes(sale.status)" class="space-y-3 rounded-2xl bg-slate-50 p-4 dark:bg-white/5" @submit.prevent="submitRefund">
            <p class="text-sm font-semibold">Refund</p>
            <div v-for="item in refundForm.items" :key="item.sale_item_id" class="grid gap-2 sm:grid-cols-2">
                <p class="text-sm">{{ item.name }}</p>
                <input v-model="item.quantity" type="number" min="0.001" step="0.001" class="input-soft">
            </div>
            <input v-model="refundForm.reason" class="input-soft w-full" placeholder="Alasan refund" required>
            <p v-for="error in Object.values(refundForm.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
            <button class="btn-primary" :disabled="refundForm.processing">Proses refund</button>
        </form>

        <form v-if="can('sales.void') && sale.status === 'completed'" class="space-y-3 rounded-2xl bg-rose-50 p-4" @submit.prevent="submitVoid">
            <p class="text-sm font-semibold text-rose-800">Void transaksi</p>
            <input v-model="voidForm.reason" class="input-soft w-full" placeholder="Alasan void" required>
            <p v-for="error in Object.values(voidForm.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
            <button class="btn-danger" :disabled="voidForm.processing">Void</button>
        </form>
    </div>
</template>
