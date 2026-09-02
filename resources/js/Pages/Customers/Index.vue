<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CustomerForm from './Form.vue';
import { formatRupiah } from '@/utils/money';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    customers: Object,
    creating: { type: Boolean, default: false },
    editingCustomer: { type: Object, default: null },
});

const showCreate = ref(props.creating);
const editing = ref(props.editingCustomer);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingCustomer, (value) => { editing.value = value; });

function closeModal() {
    showCreate.value = false;
    editing.value = null;

    if (props.creating || props.editingCustomer) {
        router.get('/customers', {}, { preserveState: true, preserveScroll: true });
    }
}

function remove(customer) {
    if (confirm(`Hapus ${customer.name}?`)) {
        router.delete(`/customers/${customer.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Pelanggan" />
        <PageHeader title="Pelanggan" subtitle="Data pelanggan toko dan pelanggan walk-in.">
            <a href="/exports/customers" class="btn-secondary">Export CSV</a>
            <button class="btn-primary" @click="showCreate = true; editing = null">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada pelanggan." :is-empty="!customers.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th>Member</th>
                    <th>Poin</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="customer in customers.data" :key="customer.id">
                <td>
                    <div class="flex items-center gap-3">
                        <Avatar :name="customer.name" />
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ customer.name }}</p>
                            <StatusBadge v-if="customer.is_walk_in" tone="slate">Walk-in</StatusBadge>
                        </div>
                    </div>
                </td>
                <td>{{ customer.phone || '-' }}</td>
                <td>{{ customer.email || '-' }}</td>
                <td>{{ customer.member_number || '-' }}</td>
                <td>{{ formatRupiah(customer.points ?? 0) }}</td>
                <td class="text-right">
                    <button class="btn-ghost" @click="editing = customer; showCreate = false">Edit</button>
                    <button v-if="!customer.is_walk_in" class="btn-danger" @click="remove(customer)">Hapus</button>
                </td>
            </tr>
            <template v-if="customers.links.length > 3" #footer>
                <Pagination :links="customers.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah Pelanggan" @close="closeModal">
            <CustomerForm :key="'create'" @close="closeModal" />
        </Modal>
        <Modal :show="!!editing" title="Edit Pelanggan" :subtitle="editing?.name" @close="closeModal">
            <CustomerForm v-if="editing" :key="editing.id" :customer="editing" @close="closeModal" />
        </Modal>
    </AdminLayout>
</template>
