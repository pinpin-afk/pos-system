<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProductForm from './Form.vue';
import { Head, router } from '@inertiajs/vue3';
import { formatRupiah, formatNumber } from '@/utils/money';
import { ref, watch } from 'vue';

const props = defineProps({
    products: Object,
    categories: Array,
    brands: { type: Array, default: () => [] },
    filters: Object,
    creating: { type: Boolean, default: false },
    editingProduct: { type: Object, default: null },
});

const search = ref(props.filters.search ?? '');
const showCreate = ref(props.creating);
const editing = ref(props.editingProduct);

watch(() => props.creating, (value) => { showCreate.value = value; });
watch(() => props.editingProduct, (value) => { editing.value = value; });

function submitSearch() {
    router.get('/products', { search: search.value }, { preserveState: true });
}

function isLowStock(product) {
    return Number(product.stock?.quantity) <= Number(product.stock?.minimum_stock);
}

function closeModal() {
    showCreate.value = false;
    editing.value = null;

    if (props.creating || props.editingProduct) {
        router.get('/products', { search: search.value }, { preserveState: true, preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Produk" />
        <PageHeader title="Produk" subtitle="Katalog, harga jual, dan status stok.">
            <a href="/exports/products" class="btn-secondary">Export CSV</a>
            <button class="btn-primary" @click="showCreate = true; editing = null">Tambah Produk</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada produk." :is-empty="!products.data.length">
            <template #toolbar>
                <form class="w-full max-w-sm" @submit.prevent="submitSearch">
                    <input v-model="search" placeholder="Cari nama, SKU, barcode" class="input-soft w-full">
                </form>
                <p class="text-xs text-slate-400">{{ products.total }} produk</p>
            </template>
            <template #head>
                <tr>
                    <th>Produk</th>
                    <th>SKU</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Stok</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="product in products.data" :key="product.id">
                <td>
                    <div class="flex items-center gap-3">
                        <Avatar :name="product.name" :src="product.image ? `/storage/${product.image}` : null" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ product.name }}</p>
                            <p class="text-xs text-slate-400">
                                {{ product.category?.name || 'Tanpa kategori' }}
                                <span v-if="product.brand"> · {{ product.brand.name }}</span>
                            </p>
                        </div>
                    </div>
                </td>
                <td class="font-medium text-slate-500">{{ product.sku }}</td>
                <td class="text-right font-semibold tabular-nums text-slate-900">{{ formatRupiah(product.selling_price) }}</td>
                <td class="text-right tabular-nums" :class="isLowStock(product) ? 'font-semibold text-amber-700' : ''">
                    {{ formatNumber(product.stock?.quantity ?? 0) }}
                </td>
                <td>
                    <StatusBadge :tone="product.is_active ? 'teal' : 'slate'">
                        {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-ghost" @click="editing = product; showCreate = false">Edit</button>
                </td>
            </tr>
            <template v-if="products.links.length > 3" #footer>
                <Pagination :links="products.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah Produk" subtitle="Isi katalog dan stok awal." wide @close="closeModal">
            <ProductForm :key="'create'" :categories="categories" :brands="brands" @close="closeModal" />
        </Modal>
        <Modal :show="!!editing" title="Edit Produk" :subtitle="editing?.name" wide @close="closeModal">
            <ProductForm v-if="editing" :key="editing.id" :categories="categories" :brands="brands" :product="editing" @close="closeModal" />
        </Modal>
    </AdminLayout>
</template>
