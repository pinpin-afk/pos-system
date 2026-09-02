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
    branches: Object,
    creating: { type: Boolean, default: false },
    editingBranch: { type: Object, default: null },
    plan: { type: String, default: null },
});

const showCreate = ref(props.creating);
const editing = ref(props.editingBranch);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingBranch, (value) => { editing.value = value; });

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    is_active: true,
});

function openCreate() {
    form.reset();
    form.is_active = true;
    editing.value = null;
    showCreate.value = true;
}

function openEdit(branch) {
    form.name = branch.name;
    form.code = branch.code ?? '';
    form.address = branch.address ?? '';
    form.phone = branch.phone ?? '';
    form.is_active = branch.is_active;
    editing.value = branch;
    showCreate.value = false;
}

function closeModal() {
    showCreate.value = false;
    editing.value = null;
}

function submit() {
    const options = { onSuccess: closeModal };

    if (editing.value) {
        form.put(`/branches/${editing.value.id}`, options);
        return;
    }

    form.post('/branches', options);
}

function remove(branch) {
    if (confirm(`Hapus cabang ${branch.name}?`)) {
        router.delete(`/branches/${branch.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Cabang" />
        <PageHeader title="Cabang" :subtitle="plan ? `Paket: ${plan}` : 'Kelola cabang toko.'">
            <button class="btn-primary" @click="openCreate">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada cabang." :is-empty="!branches.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Kode</th>
                    <th>Gudang</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="branch in branches.data" :key="branch.id">
                <td class="font-semibold">{{ branch.name }}</td>
                <td>{{ branch.code || '-' }}</td>
                <td>{{ branch.warehouses_count }}</td>
                <td>
                    <StatusBadge :tone="branch.is_active ? 'teal' : 'slate'">
                        {{ branch.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="openEdit(branch)">Edit</button>
                    <button class="btn-danger" @click="remove(branch)">Hapus</button>
                </td>
            </tr>
            <template v-if="branches.links.length > 3" #footer>
                <Pagination :links="branches.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate || !!editing" :title="editing ? 'Edit cabang' : 'Tambah cabang'" @close="closeModal">
            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.name" placeholder="Nama cabang" class="input-soft w-full" required>
                <input v-model="form.code" placeholder="Kode" class="input-soft w-full">
                <input v-model="form.address" placeholder="Alamat" class="input-soft w-full">
                <input v-model="form.phone" placeholder="Telepon" class="input-soft w-full">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600">
                    Aktif
                </label>
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="closeModal">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
