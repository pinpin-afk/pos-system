<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { formatRupiah, formatNumber } from '@/utils/money';
import { paymentLabel } from '@/utils/labels';
import { onMounted } from 'vue';

const props = defineProps({
    sale: Object,
    store: Object,
    whatsappUrl: { type: String, default: null },
});

onMounted(() => {
    window.print();
});

function sendEmail() {
    const email = window.prompt('Kirim struk ke email', props.sale.customer?.email || '');

    if (!email) {
        return;
    }

    router.post(`/receipts/${props.sale.id}/email`, { email });
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 p-6">
        <Head :title="`Struk ${sale.invoice_number}`" />
            <div class="mb-4 flex justify-center gap-3 print:hidden">
            <button class="rounded-lg bg-teal-700 px-4 py-2 text-white" @click="window.print()">Cetak / PDF</button>
            <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener" class="rounded-lg bg-emerald-600 px-4 py-2 text-white">WhatsApp</a>
            <button class="rounded-lg bg-sky-700 px-4 py-2 text-white" @click="sendEmail">Email</button>
            <Link href="/pos" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Transaksi baru</Link>
        </div>
        <div class="receipt-paper mx-auto w-[320px] bg-white p-6 text-sm shadow">
            <div class="text-center">
                <h1 class="text-lg font-semibold">{{ store.store_name }}</h1>
                <p>{{ store.address }}</p>
                <p>{{ store.phone }}</p>
            </div>
            <p class="mt-4">{{ sale.invoice_number }}</p>
            <p>Kasir: {{ sale.cashier?.name }}</p>
            <p>Pelanggan: {{ sale.customer?.name }}</p>
            <p>{{ sale.completed_at }}</p>
            <hr class="my-3">
            <div v-for="item in sale.items" :key="item.id" class="flex justify-between">
                <div>
                    <p>{{ item.product_name }}</p>
                    <p class="text-xs">{{ formatNumber(item.quantity) }} x {{ formatRupiah(item.selling_price) }}</p>
                </div>
                <p>{{ formatRupiah(item.subtotal) }}</p>
            </div>
            <hr class="my-3">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ formatRupiah(sale.subtotal) }}</span></div>
            <div class="flex justify-between"><span>Diskon</span><span>{{ formatRupiah(sale.discount_amount) }}</span></div>
            <div class="flex justify-between"><span>Pajak</span><span>{{ formatRupiah(sale.tax) }}</span></div>
            <div class="flex justify-between font-semibold"><span>Total</span><span>{{ formatRupiah(sale.grand_total) }}</span></div>
            <div v-if="sale.customer && !sale.customer.is_walk_in" class="flex justify-between">
                <span>Saldo poin</span><span>{{ formatRupiah(sale.customer.points) }}</span>
            </div>
            <div v-for="payment in sale.payments" :key="payment.id" class="mt-2">
                <div class="flex justify-between"><span>{{ paymentLabel(payment.method) }}</span><span>{{ formatRupiah(payment.amount) }}</span></div>
                <div v-if="payment.method === 'cash'" class="flex justify-between">
                    <span>Bayar</span><span>{{ formatRupiah(payment.tendered) }}</span>
                </div>
                <div v-if="payment.method === 'cash'" class="flex justify-between">
                    <span>Kembali</span><span>{{ formatRupiah(payment.change_amount) }}</span>
                </div>
            </div>
            <p class="mt-6 text-center">{{ store.receipt_footer }}</p>
        </div>
    </div>
</template>
