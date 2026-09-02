<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    categories: Array,
    editingCategory: { type: Object, default: null },
});

const form = useForm({
    name: '',
    is_active: true,
});

const editing = ref(props.editingCategory);
const editForm = useForm({
    name: props.editingCategory?.name ?? '',
    is_active: props.editingCategory?.is_active ?? true,
});

watch(() => props.editingCategory, (category) => {
    editing.value = category;

    if (category) {
        editForm.name = category.name;
        editForm.is_active = category.is_active;
    }
});

function submit() {
    form.post('/categories', { onSuccess: () => form.reset('name') });
}

function openEdit(category) {
    editing.value = category;
    editForm.name = category.name;
    editForm.is_active = category.is_active;
}

function closeEdit() {
    editing.value = null;

    if (props.editingCategory) {
        router.get('/categories', {}, { preserveState: true, preserveScroll: true });
    }
}

function saveEdit() {
    editForm.put(`/categories/${editing.value.id}`, { onSuccess: closeEdit });
}

function remove(category) {
    if (confirm(`Hapus kategori ${category.name}?`)) {
        router.delete(`/categories/${category.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Kategori" />
        <PageHeader title="Kategori" subtitle="Kelompokkan produk supaya kasir lebih cepat mencari." />

        <DataTable class="mt-6" empty="Belum ada kategori." :is-empty="!categories.length">
            <template #toolbar>
                <form class="flex w-full max-w-xl gap-2" @submit.prevent="submit">
                    <input v-model="form.name" placeholder="Nama kategori baru" class="input-soft flex-1" required>
                    <button class="btn-primary">Tambah</button>
                </form>
                <p class="text-xs text-slate-400">{{ categories.length }} kategori</p>
            </template>
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th class="text-right">Produk</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="category in categories" :key="category.id">
                <td class="font-semibold text-slate-900">{{ category.name }}</td>
                <td class="text-right tabular-nums">{{ category.products_count }}</td>
                <td>
                    <StatusBadge :tone="category.is_active ? 'teal' : 'slate'">
                        {{ category.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="openEdit(category)">Edit</button>
                    <button class="btn-danger" @click="remove(category)">Hapus</button>
                </td>
            </tr>
        </DataTable>

        <Modal :show="!!editing" title="Edit Kategori" @close="closeEdit">
            <form class="space-y-4" @submit.prevent="saveEdit">
                <div>
                    <label class="text-sm font-medium">Nama</label>
                    <input v-model="editForm.name" class="input-soft mt-1 w-full" required>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600">
                    Aktif
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="closeEdit">Batal</button>
                    <button class="btn-primary" :disabled="editForm.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
