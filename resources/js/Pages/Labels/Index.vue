<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils/money';
import { code128Svg } from '@/utils/code128';
import { computed, ref } from 'vue';

const props = defineProps({
    products: Array,
});

const selected = ref([]);
const copies = ref(1);

const labels = computed(() => {
    const items = props.products.filter((product) => selected.value.includes(product.id));
    const result = [];

    items.forEach((product) => {
        const code = product.barcode || product.sku;
        for (let i = 0; i < Number(copies.value || 1); i += 1) {
            result.push({
                id: `${product.id}-${i}`,
                name: product.name,
                sku: product.sku,
                code,
                price: product.selling_price,
                svg: code128Svg(code),
            });
        }
    });

    return result;
});

function printLabels() {
    window.print();
}
</script>

<template>
    <AdminLayout>
        <Head title="Label Barcode" />
        <PageHeader title="Cetak label barcode" subtitle="Pilih produk, lalu cetak. Scan di kasir sudah tersedia.">
            <button class="btn-primary" :disabled="!labels.length" @click="printLabels">Cetak</button>
        </PageHeader>

        <div class="admin-card mt-6 p-5 print:hidden">
            <div class="flex flex-wrap items-end gap-4">
                <label class="text-sm">
                    Jumlah salinan
                    <input v-model="copies" type="number" min="1" max="20" class="input-soft mt-1 w-24">
                </label>
            </div>
            <div class="mt-4 grid max-h-72 gap-2 overflow-y-auto sm:grid-cols-2 xl:grid-cols-3">
                <label v-for="product in products" :key="product.id" class="flex items-center gap-2 text-sm">
                    <input v-model="selected" type="checkbox" :value="product.id" class="rounded border-slate-300 text-teal-600">
                    <span>{{ product.name }} · {{ product.barcode || product.sku }}</span>
                </label>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 print:grid-cols-3">
            <div v-for="label in labels" :key="label.id" class="rounded-2xl border border-slate-200 bg-white p-4 text-center">
                <p class="text-sm font-semibold">{{ label.name }}</p>
                <div class="mt-3 h-12 w-full" v-html="label.svg" />
                <p class="mt-2 font-mono text-xs tracking-widest">{{ label.code }}</p>
                <p class="mt-1 text-sm font-bold">{{ formatRupiah(label.price) }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
