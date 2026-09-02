<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SaleDetail from './Detail.vue';
import { Head, router } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils/money';
import { paymentMethodsLabel, primaryPaymentMethod } from '@/utils/labels';
import { ref } from 'vue';

const props = defineProps({
    sales: Object,
    filters: Object,
    viewingSale: { type: Object, default: null },
});

const search = ref(props.filters.search ?? '');

function submitSearch() {
    router.get('/sales', { search: search.value }, { preserveState: true });
}

function openSale(sale) {
    router.get('/sales', { search: search.value, sale: sale.id }, { preserveState: true, preserveScroll: true });
}

function closeSale() {
    router.get('/sales', { search: search.value }, { preserveState: true, preserveScroll: true });
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
</script>

<template>
    <AdminLayout>
        <Head title="Penjualan" />
        <PageHeader title="Penjualan" subtitle="Riwayat transaksi yang sudah selesai." />

        <DataTable class="mt-6" empty="Belum ada penjualan." :is-empty="!sales.data.length">
            <template #toolbar>
                <form class="w-full max-w-sm" @submit.prevent="submitSearch">
                    <input v-model="search" placeholder="Cari nomor invoice" class="input-soft w-full">
                </form>
                <p class="text-xs text-slate-400">{{ sales.total }} transaksi</p>
            </template>
            <template #head>
                <tr>
                    <th>Invoice</th>
                    <th>Kasir</th>
                    <th>Pelanggan</th>
                    <th>Pembayaran</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Laba</th>
                </tr>
            </template>
            <tr v-for="sale in sales.data" :key="sale.id" class="cursor-pointer" @click="openSale(sale)">
                <td>
                    <button class="font-semibold text-slate-900 hover:text-teal-700" @click.stop="openSale(sale)">
                        {{ sale.invoice_number }}
                    </button>
                </td>
                <td>{{ sale.cashier?.name }}</td>
                <td>{{ sale.customer?.name }}</td>
                <td>
                    <StatusBadge :tone="paymentTone(primaryPaymentMethod(sale))">
                        {{ paymentMethodsLabel(sale) }}
                    </StatusBadge>
                </td>
                <td class="text-right font-semibold tabular-nums text-slate-900">{{ formatRupiah(sale.grand_total) }}</td>
                <td class="text-right tabular-nums">{{ formatRupiah(sale.profit) }}</td>
            </tr>
            <template v-if="sales.links.length > 3" #footer>
                <Pagination :links="sales.links" />
            </template>
        </DataTable>

        <Modal
            :show="!!viewingSale"
            :title="viewingSale?.invoice_number ?? 'Detail penjualan'"
            :subtitle="viewingSale ? `${viewingSale.cashier?.name} · ${viewingSale.customer?.name}` : ''"
            wide
            @close="closeSale"
        >
            <SaleDetail v-if="viewingSale" :sale="viewingSale" />
        </Modal>
    </AdminLayout>
</template>
