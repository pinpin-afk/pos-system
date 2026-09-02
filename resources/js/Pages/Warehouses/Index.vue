<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    warehouses: Object,
    creating: { type: Boolean, default: false },
});

const showCreate = ref(props.creating);
watch(() => props.creating, (value) => { showCreate.value = value; });

const form = useForm({
    name: '',
    is_default: false,
    is_active: true,
});

function submit() {
    form.post('/warehouses', { onSuccess: () => { showCreate.value = false; form.reset(); form.is_active = true; } });
}

function remove(warehouse) {
    if (confirm(`Hapus gudang ${warehouse.name}?`)) {
        router.delete(`/warehouses/${warehouse.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Gudang" />
        <PageHeader title="Gudang" subtitle="Stok per lokasi penyimpanan.">
            <button class="btn-primary" @click="showCreate = true">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada gudang." :is-empty="!warehouses.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Cabang</th>
                    <th>Default</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="warehouse in warehouses.data" :key="warehouse.id">
                <td class="font-semibold">{{ warehouse.name }}</td>
                <td>{{ warehouse.branch?.name }}</td>
                <td>{{ warehouse.is_default ? 'Ya' : '-' }}</td>
                <td>
                    <StatusBadge :tone="warehouse.is_active ? 'teal' : 'slate'">
                        {{ warehouse.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-danger" @click="remove(warehouse)">Hapus</button>
                </td>
            </tr>
            <template v-if="warehouses.links.length > 3" #footer>
                <Pagination :links="warehouses.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah gudang" @close="showCreate = false">
            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.name" placeholder="Nama gudang" class="input-soft w-full" required>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_default" type="checkbox" class="rounded border-slate-300 text-teal-600">
                    Gudang default cabang
                </label>
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
