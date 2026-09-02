<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import BrandForm from './Form.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    brands: Object,
    creating: { type: Boolean, default: false },
    editingBrand: { type: Object, default: null },
});

const showCreate = ref(props.creating);
const editing = ref(props.editingBrand);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingBrand, (value) => { editing.value = value; });

function closeModal() {
    showCreate.value = false;
    editing.value = null;

    if (props.creating || props.editingBrand) {
        router.get('/brands', {}, { preserveState: true, preserveScroll: true });
    }
}

function remove(brand) {
    if (confirm(`Hapus ${brand.name}?`)) {
        router.delete(`/brands/${brand.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Merek" />
        <PageHeader title="Merek" subtitle="Brand produk di katalog.">
            <button class="btn-primary" @click="showCreate = true; editing = null">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada merek." :is-empty="!brands.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="brand in brands.data" :key="brand.id">
                <td class="font-semibold text-slate-900">{{ brand.name }}</td>
                <td>
                    <StatusBadge :tone="brand.is_active ? 'teal' : 'slate'">
                        {{ brand.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="editing = brand; showCreate = false">Edit</button>
                    <button class="btn-danger" @click="remove(brand)">Hapus</button>
                </td>
            </tr>
            <template v-if="brands.links.length > 3" #footer>
                <Pagination :links="brands.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah Merek" @close="closeModal">
            <BrandForm :key="'create'" @close="closeModal" />
        </Modal>
        <Modal :show="!!editing" title="Edit Merek" :subtitle="editing?.name" @close="closeModal">
            <BrandForm v-if="editing" :key="editing.id" :brand="editing" @close="closeModal" />
        </Modal>
    </AdminLayout>
</template>
