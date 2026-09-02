<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, useForm } from '@inertiajs/vue3';

const productForm = useForm({ file: null });
const customerForm = useForm({ file: null });
const stockForm = useForm({ file: null });

function upload(form, url) {
    form.post(url, { forceFormData: true });
}
</script>

<template>
    <AdminLayout>
        <Head title="Import" />
        <PageHeader title="Import data" subtitle="Unggah CSV. Baris pertama adalah header.">
            <a href="/exports/products" class="btn-secondary">Contoh produk</a>
        </PageHeader>

        <div class="mt-6 grid gap-4 xl:grid-cols-3">
            <form class="admin-card space-y-3 p-6" @submit.prevent="upload(productForm, '/imports/products')">
                <h2 class="font-semibold">Produk</h2>
                <p class="text-sm text-slate-500">Header: name, sku, barcode, category, purchase_price, selling_price, wholesale_price, unit, stock, minimum_stock</p>
                <input type="file" accept=".csv,text/csv" class="input-soft w-full" @change="productForm.file = $event.target.files[0]">
                <p v-if="productForm.errors.file" class="text-sm text-rose-600">{{ productForm.errors.file }}</p>
                <button class="btn-primary" :disabled="productForm.processing">Import produk</button>
            </form>
            <form class="admin-card space-y-3 p-6" @submit.prevent="upload(customerForm, '/imports/customers')">
                <h2 class="font-semibold">Pelanggan</h2>
                <p class="text-sm text-slate-500">Header: name, phone, email, address, birthday, member_number</p>
                <input type="file" accept=".csv,text/csv" class="input-soft w-full" @change="customerForm.file = $event.target.files[0]">
                <p v-if="customerForm.errors.file" class="text-sm text-rose-600">{{ customerForm.errors.file }}</p>
                <button class="btn-primary" :disabled="customerForm.processing">Import pelanggan</button>
            </form>
            <form class="admin-card space-y-3 p-6" @submit.prevent="upload(stockForm, '/imports/stock')">
                <h2 class="font-semibold">Stok</h2>
                <p class="text-sm text-slate-500">Header: sku, quantity, minimum_stock</p>
                <input type="file" accept=".csv,text/csv" class="input-soft w-full" @change="stockForm.file = $event.target.files[0]">
                <p v-if="stockForm.errors.file" class="text-sm text-rose-600">{{ stockForm.errors.file }}</p>
                <button class="btn-primary" :disabled="stockForm.processing">Import stok</button>
            </form>
        </div>
    </AdminLayout>
</template>
