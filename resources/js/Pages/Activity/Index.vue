<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head } from '@inertiajs/vue3';
import { formatDateTime } from '@/utils/labels';

defineProps({
    logs: Object,
});
</script>

<template>
    <AdminLayout>
        <Head title="Activity log" />
        <PageHeader title="Activity log" subtitle="Jejak operasi penting di toko." />

        <DataTable class="mt-6" empty="Belum ada aktivitas." :is-empty="!logs.data.length">
            <template #head>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Detail</th>
                </tr>
            </template>
            <tr v-for="log in logs.data" :key="log.id">
                <td>{{ formatDateTime(log.created_at) }}</td>
                <td>{{ log.user?.name || 'Sistem' }}</td>
                <td class="font-semibold">{{ log.action }}</td>
                <td class="text-sm text-slate-500">{{ log.properties ? JSON.stringify(log.properties) : '-' }}</td>
            </tr>
            <template v-if="logs.links.length > 3" #footer>
                <Pagination :links="logs.links" />
            </template>
        </DataTable>
    </AdminLayout>
</template>
