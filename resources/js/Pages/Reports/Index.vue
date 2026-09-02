<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router } from '@inertiajs/vue3';
import { formatRupiah, formatNumber } from '@/utils/money';
import { paymentLabel } from '@/utils/labels';
import { reactive } from 'vue';

const props = defineProps({
    filters: Object,
    sales: Object,
    productSales: Array,
    payments: Array,
    cashierSales: Array,
    inventory: Array,
    categorySales: { type: Array, default: () => [] },
    customerSales: { type: Array, default: () => [] },
    slowMoving: { type: Array, default: () => [] },
    expenses: { type: Object, default: () => ({ total: 0, rows: [] }) },
});

const filters = reactive({
    from: props.filters.from,
    to: props.filters.to,
});

function apply() {
    router.get('/reports', filters, { preserveState: true });
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

function isLow(row) {
    return Number(row.quantity) <= Number(row.minimum_stock);
}
</script>

<template>
    <AdminLayout>
        <Head title="Laporan" />
        <PageHeader title="Laporan" subtitle="Omzet, laba, pajak, pelanggan, dan stok lambat.">
            <a href="/exports/sales" class="btn-secondary">Export CSV</a>
            <a href="/exports/sales/print" target="_blank" class="btn-primary">Cetak PDF</a>
        </PageHeader>

        <form class="admin-card mt-6 flex flex-wrap items-end gap-3 px-5 py-4" @submit.prevent="apply">
            <div>
                <label class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Dari</label>
                <input v-model="filters.from" type="date" class="input-soft mt-1 block">
            </div>
            <div>
                <label class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Sampai</label>
                <input v-model="filters.to" type="date" class="input-soft mt-1 block">
            </div>
            <button class="btn-primary">Terapkan</button>
        </form>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[28px] bg-slate-900 p-5 text-white">
                <p class="text-sm text-slate-300">Omzet</p>
                <p class="mt-2 text-2xl font-bold tracking-tight">{{ formatRupiah(sales.revenue) }}</p>
            </div>
            <div class="admin-card p-5">
                <p class="text-sm text-slate-500">Laba kotor</p>
                <p class="mt-2 text-2xl font-bold">{{ formatRupiah(sales.profit) }}</p>
            </div>
            <div class="admin-card p-5">
                <p class="text-sm text-slate-500">Transaksi</p>
                <p class="mt-2 text-2xl font-bold">{{ sales.transactions }}</p>
            </div>
            <div class="admin-card p-5">
                <p class="text-sm text-slate-500">Pajak</p>
                <p class="mt-2 text-2xl font-bold">{{ formatRupiah(sales.tax) }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section>
                <h2 class="mb-3 px-1 font-semibold">Produk terlaris</h2>
                <DataTable compact empty="Belum ada penjualan produk." :is-empty="!productSales.length">
                    <template #head>
                        <tr>
                            <th>Produk</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Omzet</th>
                            <th class="text-right">Laba</th>
                        </tr>
                    </template>
                    <tr v-for="row in productSales" :key="row.product_id">
                        <td class="font-semibold text-slate-900">{{ row.product_name }}</td>
                        <td class="text-right tabular-nums">{{ formatNumber(row.quantity) }}</td>
                        <td class="text-right tabular-nums">{{ formatRupiah(row.revenue) }}</td>
                        <td class="text-right tabular-nums">{{ formatRupiah(row.profit) }}</td>
                    </tr>
                </DataTable>
            </section>
            <section class="space-y-6">
                <div>
                    <h2 class="mb-3 px-1 font-semibold">Metode pembayaran</h2>
                    <DataTable compact empty="Belum ada pembayaran." :is-empty="!payments.length">
                        <template #head>
                            <tr>
                                <th>Metode</th>
                                <th class="text-right">Jumlah</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </template>
                        <tr v-for="row in payments" :key="row.method">
                            <td>
                                <StatusBadge :tone="paymentTone(row.method)">
                                    {{ paymentLabel(row.method) }}
                                </StatusBadge>
                            </td>
                            <td class="text-right tabular-nums">{{ row.count }}</td>
                            <td class="text-right font-semibold tabular-nums text-slate-900">{{ formatRupiah(row.total) }}</td>
                        </tr>
                    </DataTable>
                </div>
                <div>
                    <h2 class="mb-3 px-1 font-semibold">Penjualan kasir</h2>
                    <DataTable compact empty="Belum ada data kasir." :is-empty="!cashierSales.length">
                        <template #head>
                            <tr>
                                <th>Kasir</th>
                                <th class="text-right">Trx</th>
                                <th class="text-right">Omzet</th>
                            </tr>
                        </template>
                        <tr v-for="row in cashierSales" :key="row.name">
                            <td class="font-semibold text-slate-900">{{ row.name }}</td>
                            <td class="text-right tabular-nums">{{ row.transactions }}</td>
                            <td class="text-right tabular-nums">{{ formatRupiah(row.revenue) }}</td>
                        </tr>
                    </DataTable>
                </div>
            </section>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section>
                <h2 class="mb-3 px-1 font-semibold">Penjualan kategori</h2>
                <DataTable compact empty="Belum ada data kategori." :is-empty="!categorySales.length">
                    <template #head>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Omzet</th>
                        </tr>
                    </template>
                    <tr v-for="row in categorySales" :key="row.category">
                        <td class="font-semibold">{{ row.category }}</td>
                        <td class="text-right">{{ formatNumber(row.quantity) }}</td>
                        <td class="text-right">{{ formatRupiah(row.revenue) }}</td>
                    </tr>
                </DataTable>
            </section>
            <section>
                <h2 class="mb-3 px-1 font-semibold">Pelanggan</h2>
                <DataTable compact empty="Belum ada data pelanggan." :is-empty="!customerSales.length">
                    <template #head>
                        <tr>
                            <th>Nama</th>
                            <th class="text-right">Trx</th>
                            <th class="text-right">Omzet</th>
                        </tr>
                    </template>
                    <tr v-for="row in customerSales" :key="row.name">
                        <td class="font-semibold">{{ row.name }}</td>
                        <td class="text-right">{{ row.transactions }}</td>
                        <td class="text-right">{{ formatRupiah(row.revenue) }}</td>
                    </tr>
                </DataTable>
            </section>
        </div>

        <section class="mt-6">
            <h2 class="mb-3 px-1 font-semibold">Pengeluaran</h2>
            <p class="mb-3 px-1 text-sm text-slate-500">Total {{ formatRupiah(expenses.total) }}</p>
            <DataTable compact empty="Tidak ada pengeluaran di periode ini." :is-empty="!expenses.rows.length">
                <template #head>
                    <tr>
                        <th>Kategori</th>
                        <th class="text-right">Total</th>
                    </tr>
                </template>
                <tr v-for="row in expenses.rows" :key="row.category">
                    <td class="font-semibold">{{ row.category }}</td>
                    <td class="text-right">{{ formatRupiah(row.total) }}</td>
                </tr>
            </DataTable>
        </section>

        <section class="mt-6">
            <h2 class="mb-3 px-1 font-semibold">Slow moving / dead stock (30 hari)</h2>
            <DataTable compact empty="Tidak ada stok mati." :is-empty="!slowMoving.length">
                <template #head>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th class="text-right">Qty</th>
                    </tr>
                </template>
                <tr v-for="row in slowMoving" :key="row.id">
                    <td class="font-semibold">{{ row.product?.name }}</td>
                    <td>{{ row.product?.sku }}</td>
                    <td class="text-right">{{ formatNumber(row.quantity) }}</td>
                </tr>
            </DataTable>
        </section>

        <section class="mt-6">
            <h2 class="mb-3 px-1 font-semibold">Stok saat ini</h2>
            <DataTable compact empty="Belum ada data stok." :is-empty="!inventory.length">
                <template #head>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th class="text-right">Qty</th>
                        <th>Kondisi</th>
                    </tr>
                </template>
                <tr v-for="row in inventory" :key="row.id">
                    <td class="font-semibold text-slate-900">{{ row.product?.name }}</td>
                    <td class="text-slate-500">{{ row.product?.sku }}</td>
                    <td class="text-right tabular-nums" :class="isLow(row) ? 'font-semibold text-amber-700' : ''">
                        {{ formatNumber(row.quantity) }}
                    </td>
                    <td>
                        <StatusBadge :tone="isLow(row) ? 'amber' : 'teal'">
                            {{ isLow(row) ? 'Menipis' : 'Aman' }}
                        </StatusBadge>
                    </td>
                </tr>
            </DataTable>
        </section>
    </AdminLayout>
</template>
