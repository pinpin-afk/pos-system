<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { formatRupiah, formatNumber } from '@/utils/money';
import { ref, watch } from 'vue';

const props = defineProps({
    returns: Object,
    suppliers: Array,
    products: Array,
    creating: { type: Boolean, default: false },
});

const showCreate = ref(props.creating);
watch(() => props.creating, (value) => { showCreate.value = value; });

const form = useForm({
    supplier_id: '',
    reason: '',
    items: [{ product_id: '', quantity: 1, unit_cost: 0 }],
});

function addLine() {
    form.items.push({ product_id: '', quantity: 1, unit_cost: 0 });
}

function fillCost(line) {
    const product = props.products.find((row) => row.id === Number(line.product_id));
    if (product) {
        line.unit_cost = Number(product.purchase_price);
    }
}

function submit() {
    form.post('/purchase-returns', { onSuccess: () => { showCreate.value = false; } });
}
</script>

<template>
    <AdminLayout>
        <Head title="Retur Supplier" />
        <PageHeader title="Retur ke supplier" subtitle="Barang rusak atau tidak sesuai dikembalikan.">
            <button class="btn-primary" @click="showCreate = true">Buat retur</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada retur." :is-empty="!returns.data.length">
            <template #head>
                <tr>
                    <th>Nomor</th>
                    <th>Supplier</th>
                    <th>Alasan</th>
                    <th class="text-right">Total</th>
                </tr>
            </template>
            <tr v-for="row in returns.data" :key="row.id">
                <td class="font-semibold">{{ row.number }}</td>
                <td>{{ row.supplier?.name }}</td>
                <td>{{ row.reason }}</td>
                <td class="text-right">{{ formatRupiah(row.subtotal) }}</td>
            </tr>
            <template v-if="returns.links.length > 3" #footer>
                <Pagination :links="returns.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Retur supplier" wide @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="submit">
                <select v-model="form.supplier_id" class="input-soft w-full" required>
                    <option value="" disabled>Pilih supplier</option>
                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                </select>
                <input v-model="form.reason" class="input-soft w-full" placeholder="Alasan retur" required>
                <div v-for="(line, index) in form.items" :key="index" class="grid gap-2 sm:grid-cols-3">
                    <select v-model="line.product_id" class="input-soft" required @change="fillCost(line)">
                        <option value="" disabled>Produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                    <input v-model="line.quantity" type="number" min="0.001" step="0.001" class="input-soft">
                    <input v-model="line.unit_cost" type="number" min="0" class="input-soft">
                </div>
                <button type="button" class="btn-ghost" @click="addLine">Tambah baris</button>
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan retur</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
