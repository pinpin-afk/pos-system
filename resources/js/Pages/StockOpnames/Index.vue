<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { formatNumber } from '@/utils/money';
import { ref, watch } from 'vue';

const props = defineProps({
    opnames: Object,
    products: Array,
    creating: { type: Boolean, default: false },
    viewingOpname: { type: Object, default: null },
});

const showCreate = ref(props.creating);
const viewing = ref(props.viewingOpname);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.viewingOpname, (value) => { viewing.value = value; });

const form = useForm({
    notes: '',
    items: [{ product_id: '', actual_quantity: 0 }],
});

function addLine() {
    form.items.push({ product_id: '', actual_quantity: 0 });
}

function systemQty(productId) {
    return Number(props.products.find((row) => row.id === Number(productId))?.stock?.quantity ?? 0);
}

function submit() {
    form.post('/stock-opnames', { onSuccess: () => { showCreate.value = false; } });
}

function complete(opname) {
    if (confirm(`Terapkan ${opname.number}? Stok akan disesuaikan.`)) {
        router.post(`/stock-opnames/${opname.id}/complete`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Stock Opname" />
        <PageHeader title="Stock opname" subtitle="Hitung fisik lalu sesuaikan stok sistem.">
            <button class="btn-primary" @click="showCreate = true">Hitung stok</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada opname." :is-empty="!opnames.data.length">
            <template #head>
                <tr>
                    <th>Nomor</th>
                    <th>Petugas</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="opname in opnames.data" :key="opname.id">
                <td>
                    <button class="font-semibold hover:text-teal-700" @click="router.get('/stock-opnames', { opname: opname.id })">
                        {{ opname.number }}
                    </button>
                </td>
                <td>{{ opname.user?.name }}</td>
                <td>
                    <StatusBadge :tone="opname.status === 'completed' ? 'teal' : 'amber'">
                        {{ opname.status === 'completed' ? 'Selesai' : 'Draft' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button v-if="opname.status === 'draft'" class="btn-primary" @click="complete(opname)">Terapkan</button>
                </td>
            </tr>
            <template v-if="opnames.links.length > 3" #footer>
                <Pagination :links="opnames.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Hitung stok" wide @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="submit">
                <div v-for="(line, index) in form.items" :key="index" class="grid gap-2 sm:grid-cols-3">
                    <select v-model="line.product_id" class="input-soft" required>
                        <option value="" disabled>Produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                    <input :value="systemQty(line.product_id)" class="input-soft bg-slate-50" readonly>
                    <input v-model="line.actual_quantity" type="number" min="0" step="0.001" class="input-soft" placeholder="Qty fisik">
                </div>
                <button type="button" class="btn-ghost" @click="addLine">Tambah baris</button>
                <textarea v-model="form.notes" class="input-soft w-full" rows="2" placeholder="Catatan" />
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan draft</button>
                </div>
            </form>
        </Modal>

        <Modal :show="!!viewing" :title="viewing?.number" wide @close="viewing = null">
            <table v-if="viewing" class="admin-table admin-table-compact">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="text-right">Sistem</th>
                        <th class="text-right">Fisik</th>
                        <th class="text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in viewing.items" :key="item.id">
                        <td>{{ item.product?.name }}</td>
                        <td class="text-right">{{ formatNumber(item.system_quantity) }}</td>
                        <td class="text-right">{{ formatNumber(item.actual_quantity) }}</td>
                        <td class="text-right">{{ formatNumber(item.difference) }}</td>
                    </tr>
                </tbody>
            </table>
        </Modal>
    </AdminLayout>
</template>
