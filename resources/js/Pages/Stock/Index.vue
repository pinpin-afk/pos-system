<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { formatNumber } from '@/utils/money';
import { ref } from 'vue';

const props = defineProps({
    stocks: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const adjusting = ref(null);
const form = useForm({
    quantity: 0,
    notes: '',
});

function submitSearch() {
    router.get('/stock', { search: search.value }, { preserveState: true });
}

function openAdjust(stock) {
    adjusting.value = stock;
    form.quantity = 0;
    form.notes = '';
}

function submitAdjust() {
    form.post(`/stock/${adjusting.value.product_id}/adjust`, {
        onSuccess: () => {
            adjusting.value = null;
        },
    });
}

function isLow(stock) {
    return Number(stock.quantity) <= Number(stock.minimum_stock);
}
</script>

<template>
    <AdminLayout>
        <Head title="Stok" />
        <PageHeader title="Stok" subtitle="Pantau jumlah barang dan lakukan penyesuaian.">
            <Link href="/stock/movements" class="btn-secondary">Riwayat pergerakan</Link>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada data stok." :is-empty="!stocks.data.length">
            <template #toolbar>
                <form class="w-full max-w-sm" @submit.prevent="submitSearch">
                    <input v-model="search" placeholder="Cari produk" class="input-soft w-full">
                </form>
                <p class="text-xs text-slate-400">{{ stocks.total }} item</p>
            </template>
            <template #head>
                <tr>
                    <th>Produk</th>
                    <th class="text-right">Stok</th>
                    <th class="text-right">Minimum</th>
                    <th>Kondisi</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="stock in stocks.data" :key="stock.id">
                <td>
                    <div class="flex items-center gap-3">
                        <Avatar :name="stock.product?.name" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ stock.product?.name }}</p>
                            <p class="text-xs text-slate-400">{{ stock.product?.sku }}</p>
                        </div>
                    </div>
                </td>
                <td class="text-right tabular-nums" :class="isLow(stock) ? 'font-semibold text-amber-700' : 'font-semibold text-slate-900'">
                    {{ formatNumber(stock.quantity) }}
                </td>
                <td class="text-right tabular-nums">{{ formatNumber(stock.minimum_stock) }}</td>
                <td>
                    <StatusBadge :tone="isLow(stock) ? 'amber' : 'teal'">
                        {{ isLow(stock) ? 'Menipis' : 'Aman' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="openAdjust(stock)">Penyesuaian</button>
                </td>
            </tr>
            <template v-if="stocks.links.length > 3" #footer>
                <Pagination :links="stocks.links" />
            </template>
        </DataTable>

        <Modal
            :show="!!adjusting"
            title="Penyesuaian stok"
            :subtitle="adjusting ? `${adjusting.product?.name} · angka negatif untuk mengurangi.` : ''"
            @close="adjusting = null"
        >
            <form class="space-y-3" @submit.prevent="submitAdjust">
                <input v-model="form.quantity" type="number" step="0.001" class="input-soft w-full" required>
                <input v-model="form.notes" placeholder="Alasan" class="input-soft w-full" required>
                <p v-if="form.errors.quantity" class="text-sm text-rose-600">{{ form.errors.quantity }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="adjusting = null">Batal</button>
                    <button class="btn-primary">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
