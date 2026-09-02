<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UserForm from './Form.vue';
import { Head, router } from '@inertiajs/vue3';
import { roleLabel } from '@/utils/labels';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    roles: Array,
    creating: { type: Boolean, default: false },
    editingUser: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
});

const showCreate = ref(props.creating);
const editing = ref(props.editingUser);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingUser, (value) => { editing.value = value; });

function closeModal() {
    showCreate.value = false;
    editing.value = null;

    if (props.creating || props.editingUser) {
        router.get('/users', {}, { preserveState: true, preserveScroll: true });
    }
}

function remove(user) {
    if (confirm(`Hapus ${user.name}?`)) {
        router.delete(`/users/${user.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Pengguna" />
        <PageHeader title="Pengguna" subtitle="Role, permission, dan akses ke admin atau kasir.">
            <button class="btn-primary" @click="showCreate = true; editing = null">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada pengguna." :is-empty="!users.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="user in users.data" :key="user.id">
                <td>
                    <div class="flex items-center gap-3">
                        <Avatar :name="user.name" />
                        <p class="font-semibold text-slate-900">{{ user.name }}</p>
                    </div>
                </td>
                <td>{{ user.email }}</td>
                <td>
                    <StatusBadge :tone="user.role === 'owner' ? 'violet' : user.role === 'cashier' ? 'sky' : 'amber'">
                        {{ roleLabel(user.role) }}
                    </StatusBadge>
                </td>
                <td>
                    <StatusBadge :tone="user.is_active ? 'teal' : 'slate'">
                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="editing = user; showCreate = false">Edit</button>
                    <button class="btn-danger" @click="remove(user)">Hapus</button>
                </td>
            </tr>
            <template v-if="users.links.length > 3" #footer>
                <Pagination :links="users.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah Pengguna" @close="closeModal">
            <UserForm :key="'create'" :roles="roles" :branches="branches" @close="closeModal" />
        </Modal>
        <Modal :show="!!editing" title="Edit Pengguna" :subtitle="editing?.name" @close="closeModal">
            <UserForm v-if="editing" :key="editing.id" :user-model="editing" :roles="roles" :branches="branches" @close="closeModal" />
        </Modal>
    </AdminLayout>
</template>
