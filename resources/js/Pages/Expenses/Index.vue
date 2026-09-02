<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils/money';
import { ref, watch } from 'vue';

const props = defineProps({
    expenses: Object,
    creating: { type: Boolean, default: false },
    editingExpense: { type: Object, default: null },
    categories: Array,
});

const showCreate = ref(props.creating);
const editing = ref(props.editingExpense);
watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingExpense, (value) => { editing.value = value; });

const form = useForm({
    category: props.categories[0] ?? 'Operasional',
    amount: '',
    spent_on: new Date().toISOString().slice(0, 10),
    description: '',
});

function openCreate() {
    form.reset();
    form.category = props.categories[0] ?? 'Operasional';
    form.spent_on = new Date().toISOString().slice(0, 10);
    editing.value = null;
    showCreate.value = true;
}

function openEdit(expense) {
    form.category = expense.category;
    form.amount = expense.amount;
    form.spent_on = expense.spent_on;
    form.description = expense.description ?? '';
    editing.value = expense;
    showCreate.value = false;
}

function closeModal() {
    showCreate.value = false;
    editing.value = null;
}

function submit() {
    const options = { onSuccess: closeModal };

    if (editing.value) {
        form.put(`/expenses/${editing.value.id}`, options);
        return;
    }

    form.post('/expenses', options);
}

function remove(expense) {
    if (confirm('Hapus pengeluaran ini?')) {
        router.delete(`/expenses/${expense.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Pengeluaran" />
        <PageHeader title="Pengeluaran" subtitle="Biaya operasional toko.">
            <button class="btn-primary" @click="openCreate">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada pengeluaran." :is-empty="!expenses.data.length">
            <template #head>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th class="text-right">Nominal</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="expense in expenses.data" :key="expense.id">
                <td>{{ expense.spent_on }}</td>
                <td class="font-semibold">{{ expense.category }}</td>
                <td class="text-right">{{ formatRupiah(expense.amount) }}</td>
                <td class="text-right">
                    <button class="btn-ghost" @click="openEdit(expense)">Edit</button>
                    <button class="btn-danger" @click="remove(expense)">Hapus</button>
                </td>
            </tr>
            <template v-if="expenses.links.length > 3" #footer>
                <Pagination :links="expenses.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate || !!editing" :title="editing ? 'Edit pengeluaran' : 'Tambah pengeluaran'" @close="closeModal">
            <form class="space-y-3" @submit.prevent="submit">
                <select v-model="form.category" class="input-soft w-full">
                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                </select>
                <input v-model="form.amount" type="number" min="0.01" step="0.01" placeholder="Nominal" class="input-soft w-full" required>
                <input v-model="form.spent_on" type="date" class="input-soft w-full" required>
                <input v-model="form.description" placeholder="Keterangan" class="input-soft w-full">
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="closeModal">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
