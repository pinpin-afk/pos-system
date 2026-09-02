<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatNumber } from '@/utils/money';
import { formatDateTime, movementTypeLabel } from '@/utils/labels';

defineProps({
    movements: Object,
});

function typeTone(type) {
    return {
        initial: 'sky',
        sale: 'teal',
        adjustment: 'amber',
    }[type] ?? 'slate';
}
</script>

<template>
    <AdminLayout>
        <Head title="Pergerakan Stok" />
        <PageHeader title="Pergerakan Stok" subtitle="Riwayat masuk, keluar, dan penyesuaian stok.">
            <Link href="/stock" class="btn-secondary">Kembali ke stok</Link>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada pergerakan stok." :is-empty="!movements.data.length">
            <template #head>
                <tr>
                    <th>Waktu</th>
                    <th>Produk</th>
                    <th>Tipe</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Sebelum</th>
                    <th class="text-right">Sesudah</th>
                    <th>User</th>
                </tr>
            </template>
            <tr v-for="movement in movements.data" :key="movement.id">
                <td class="whitespace-nowrap">{{ formatDateTime(movement.created_at) }}</td>
                <td class="font-semibold text-slate-900">{{ movement.product?.name }}</td>
                <td>
                    <StatusBadge :tone="typeTone(movement.type)">
                        {{ movementTypeLabel(movement.type) }}
                    </StatusBadge>
                </td>
                <td class="text-right tabular-nums font-semibold" :class="Number(movement.quantity) < 0 ? 'text-rose-600' : 'text-slate-900'">
                    {{ formatNumber(movement.quantity) }}
                </td>
                <td class="text-right tabular-nums">{{ formatNumber(movement.stock_before) }}</td>
                <td class="text-right tabular-nums">{{ formatNumber(movement.stock_after) }}</td>
                <td>{{ movement.user?.name }}</td>
            </tr>
            <template v-if="movements.links.length > 3" #footer>
                <Pagination :links="movements.links" />
            </template>
        </DataTable>
    </AdminLayout>
</template>
