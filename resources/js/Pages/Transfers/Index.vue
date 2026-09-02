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
    transfers: Object,
    warehouses: Array,
    products: Array,
    creating: { type: Boolean, default: false },
});

const showCreate = ref(props.creating);
watch(() => props.creating, (value) => { showCreate.value = value; });

const form = useForm({
    from_warehouse_id: '',
    to_warehouse_id: '',
    notes: '',
    items: [{ product_id: '', quantity: 1 }],
});

function addItem() {
    form.items.push({ product_id: '', quantity: 1 });
}

function submit() {
    form.post('/transfers', { onSuccess: () => { showCreate.value = false; form.reset(); form.items = [{ product_id: '', quantity: 1 }]; } });
}

function receive(transfer) {
    router.post(`/transfers/${transfer.id}/receive`);
}

function tone(status) {
    return { pending: 'amber', received: 'teal', cancelled: 'slate' }[status] ?? 'slate';
}

function label(status) {
    return { pending: 'Menunggu', received: 'Diterima', cancelled: 'Batal' }[status] ?? status;
}
</script>

<template>
    <AdminLayout>
        <Head title="Transfer stok" />
        <PageHeader title="Transfer stok" subtitle="Pindahkan stok antar gudang.">
            <button class="btn-primary" @click="showCreate = true">Buat transfer</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada transfer." :is-empty="!transfers.data.length">
            <template #head>
                <tr>
                    <th>Nomor</th>
                    <th>Dari</th>
                    <th>Ke</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="transfer in transfers.data" :key="transfer.id">
                <td class="font-semibold">{{ transfer.number }}</td>
                <td>{{ transfer.from_warehouse?.name }}</td>
                <td>{{ transfer.to_warehouse?.name }}</td>
                <td>
                    <StatusBadge :tone="tone(transfer.status)">{{ label(transfer.status) }}</StatusBadge>
                </td>
                <td class="text-right">
                    <button v-if="transfer.status === 'pending'" class="btn-primary" @click="receive(transfer)">Terima</button>
                </td>
            </tr>
            <template v-if="transfers.links.length > 3" #footer>
                <Pagination :links="transfers.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Transfer stok" @close="showCreate = false">
            <form class="space-y-3" @submit.prevent="submit">
                <select v-model="form.from_warehouse_id" class="input-soft w-full" required>
                    <option value="" disabled>Gudang asal</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }} · {{ warehouse.branch?.name }}</option>
                </select>
                <select v-model="form.to_warehouse_id" class="input-soft w-full" required>
                    <option value="" disabled>Gudang tujuan</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }} · {{ warehouse.branch?.name }}</option>
                </select>
                <div v-for="(item, index) in form.items" :key="index" class="flex gap-2">
                    <select v-model="item.product_id" class="input-soft flex-1" required>
                        <option value="" disabled>Produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                    <input v-model="item.quantity" type="number" min="0.001" step="0.001" class="input-soft w-24">
                </div>
                <button type="button" class="btn-ghost" @click="addItem">Tambah item</button>
                <textarea v-model="form.notes" placeholder="Catatan" class="input-soft w-full" rows="2" />
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
