<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { formatRupiah, formatNumber } from '@/utils/money';
import { ref, watch } from 'vue';

const props = defineProps({
    purchases: Object,
    suppliers: Array,
    products: Array,
    creating: { type: Boolean, default: false },
    viewingPurchase: { type: Object, default: null },
});

const showCreate = ref(props.creating);
const viewing = ref(props.viewingPurchase);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.viewingPurchase, (value) => { viewing.value = value; });

const form = useForm({
    supplier_id: '',
    notes: '',
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
    form.post('/purchases', { onSuccess: () => { showCreate.value = false; } });
}

function receive(purchase) {
    if (confirm(`Terima barang ${purchase.number}? Stok akan bertambah.`)) {
        router.post(`/purchases/${purchase.id}/receive`);
    }
}

function statusTone(status) {
    return { ordered: 'sky', received: 'teal', cancelled: 'slate' }[status] ?? 'slate';
}

function statusLabel(status) {
    return { ordered: 'Dipesan', received: 'Diterima', cancelled: 'Batal', draft: 'Draft' }[status] ?? status;
}

function close() {
    showCreate.value = false;
    viewing.value = null;
    if (props.creating || props.viewingPurchase) {
        router.get('/purchases', {}, { preserveState: true, preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Pembelian" />
        <PageHeader title="Pembelian" subtitle="PO supplier dan penerimaan barang.">
            <button class="btn-primary" @click="showCreate = true">Buat PO</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada pembelian." :is-empty="!purchases.data.length">
            <template #head>
                <tr>
                    <th>Nomor</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th class="text-right">Total</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="purchase in purchases.data" :key="purchase.id">
                <td>
                    <button class="font-semibold hover:text-teal-700" @click="router.get('/purchases', { purchase: purchase.id })">
                        {{ purchase.number }}
                    </button>
                </td>
                <td>{{ purchase.supplier?.name }}</td>
                <td>
                    <StatusBadge :tone="statusTone(purchase.status)">{{ statusLabel(purchase.status) }}</StatusBadge>
                </td>
                <td class="text-right tabular-nums">{{ formatRupiah(purchase.subtotal) }}</td>
                <td class="text-right">
                    <button
                        v-if="purchase.status === 'ordered'"
                        class="btn-primary"
                        @click="receive(purchase)"
                    >
                        Terima
                    </button>
                </td>
            </tr>
            <template v-if="purchases.links.length > 3" #footer>
                <Pagination :links="purchases.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Pesanan pembelian" wide @close="close">
            <form class="space-y-4" @submit.prevent="submit">
                <select v-model="form.supplier_id" class="input-soft w-full" required>
                    <option value="" disabled>Pilih supplier</option>
                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                </select>
                <div v-for="(line, index) in form.items" :key="index" class="grid gap-2 sm:grid-cols-3">
                    <select v-model="line.product_id" class="input-soft" required @change="fillCost(line)">
                        <option value="" disabled>Produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                    <input v-model="line.quantity" type="number" min="0.001" step="0.001" class="input-soft" placeholder="Qty">
                    <input v-model="line.unit_cost" type="number" min="0" class="input-soft" placeholder="Harga beli">
                </div>
                <button type="button" class="btn-ghost" @click="addLine">Tambah baris</button>
                <textarea v-model="form.notes" class="input-soft w-full" rows="2" placeholder="Catatan" />
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="close">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan PO</button>
                </div>
            </form>
        </Modal>

        <Modal :show="!!viewing" :title="viewing?.number" :subtitle="viewing?.supplier?.name" wide @close="close">
            <div v-if="viewing" class="space-y-4">
                <table class="admin-table admin-table-compact">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in viewing.items" :key="item.id">
                            <td>{{ item.product?.name }}</td>
                            <td class="text-right">{{ formatNumber(item.quantity) }}</td>
                            <td class="text-right">{{ formatRupiah(item.unit_cost) }}</td>
                            <td class="text-right">{{ formatRupiah(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>
                <button v-if="viewing.status === 'ordered'" class="btn-primary" @click="receive(viewing)">Terima barang</button>
            </div>
        </Modal>
    </AdminLayout>
</template>
