<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ShiftDetail from './Detail.vue';
import { Head, router } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils/money';
import { formatDateTime, shiftStatusLabel } from '@/utils/labels';

const props = defineProps({
    shifts: Object,
    viewingShift: { type: Object, default: null },
    expectedCash: { type: Number, default: null },
});

function openShift(shift) {
    router.get('/shifts', { shift: shift.id }, { preserveState: true, preserveScroll: true });
}

function closeShift() {
    router.get('/shifts', {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <Head title="Shift" />
        <PageHeader title="Shift Kasir" subtitle="Modal, tutup kas, dan selisih setiap shift." />

        <DataTable class="mt-6" empty="Belum ada shift." :is-empty="!shifts.data.length">
            <template #head>
                <tr>
                    <th>Kasir</th>
                    <th>Buka</th>
                    <th>Tutup</th>
                    <th class="text-right">Modal</th>
                    <th class="text-right">Selisih</th>
                    <th>Status</th>
                </tr>
            </template>
            <tr v-for="shift in shifts.data" :key="shift.id" class="cursor-pointer" @click="openShift(shift)">
                <td>
                    <div class="flex items-center gap-3">
                        <Avatar :name="shift.user?.name" />
                        <p class="font-semibold text-slate-900">{{ shift.user?.name }}</p>
                    </div>
                </td>
                <td class="whitespace-nowrap">{{ formatDateTime(shift.opened_at) }}</td>
                <td class="whitespace-nowrap">{{ formatDateTime(shift.closed_at) }}</td>
                <td class="text-right tabular-nums">{{ formatRupiah(shift.opening_cash) }}</td>
                <td
                    class="text-right tabular-nums font-semibold"
                    :class="Number(shift.difference) < 0 ? 'text-rose-600' : 'text-slate-900'"
                >
                    {{ shift.difference === null ? '-' : formatRupiah(shift.difference) }}
                </td>
                <td>
                    <StatusBadge :tone="shift.status === 'open' ? 'teal' : 'slate'">
                        {{ shiftStatusLabel(shift.status) }}
                    </StatusBadge>
                </td>
            </tr>
            <template v-if="shifts.links.length > 3" #footer>
                <Pagination :links="shifts.links" />
            </template>
        </DataTable>

        <Modal
            :show="!!viewingShift"
            :title="viewingShift ? `Shift ${viewingShift.user?.name}` : 'Detail shift'"
            wide
            @close="closeShift"
        >
            <ShiftDetail v-if="viewingShift" :shift="viewingShift" :expected-cash="expectedCash" />
        </Modal>
    </AdminLayout>
</template>
