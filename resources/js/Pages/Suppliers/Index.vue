<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SupplierForm from './Form.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    suppliers: Object,
    creating: { type: Boolean, default: false },
    editingSupplier: { type: Object, default: null },
});

const showCreate = ref(props.creating);
const editing = ref(props.editingSupplier);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingSupplier, (value) => { editing.value = value; });

function closeModal() {
    showCreate.value = false;
    editing.value = null;

    if (props.creating || props.editingSupplier) {
        router.get('/suppliers', {}, { preserveState: true, preserveScroll: true });
    }
}

function remove(supplier) {
    if (confirm(`Hapus ${supplier.name}?`)) {
        router.delete(`/suppliers/${supplier.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Supplier" />
        <PageHeader title="Supplier" subtitle="Vendor pembelian barang.">
            <button class="btn-primary" @click="showCreate = true; editing = null">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada supplier." :is-empty="!suppliers.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="supplier in suppliers.data" :key="supplier.id">
                <td>
                    <div class="flex items-center gap-3">
                        <Avatar :name="supplier.name" />
                        <p class="font-semibold text-slate-900">{{ supplier.name }}</p>
                    </div>
                </td>
                <td>{{ supplier.phone || '-' }}</td>
                <td>{{ supplier.email || '-' }}</td>
                <td>
                    <StatusBadge :tone="supplier.is_active ? 'teal' : 'slate'">
                        {{ supplier.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="editing = supplier; showCreate = false">Edit</button>
                    <button class="btn-danger" @click="remove(supplier)">Hapus</button>
                </td>
            </tr>
            <template v-if="suppliers.links.length > 3" #footer>
                <Pagination :links="suppliers.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah Supplier" @close="closeModal">
            <SupplierForm :key="'create'" @close="closeModal" />
        </Modal>
        <Modal :show="!!editing" title="Edit Supplier" :subtitle="editing?.name" @close="closeModal">
            <SupplierForm v-if="editing" :key="editing.id" :supplier="editing" @close="closeModal" />
        </Modal>
    </AdminLayout>
</template>
