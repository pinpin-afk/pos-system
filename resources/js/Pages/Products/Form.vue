<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    categories: Array,
    brands: { type: Array, default: () => [] },
    product: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    category_id: props.product?.category_id ?? '',
    brand_id: props.product?.brand_id ?? '',
    name: props.product?.name ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? '',
    purchase_price: props.product?.purchase_price ?? '',
    selling_price: props.product?.selling_price ?? '',
    wholesale_price: props.product?.wholesale_price ?? '',
    unit: props.product?.unit ?? 'PCS',
    description: props.product?.description ?? '',
    image: null,
    initial_stock: 0,
    minimum_stock: props.product?.stock?.minimum_stock ?? 5,
    is_active: props.product?.is_active ?? true,
    variants: (props.product?.variants ?? []).map((variant) => ({
        id: variant.id,
        name: variant.name,
        sku: variant.sku,
        barcode: variant.barcode ?? '',
        purchase_price: variant.purchase_price,
        selling_price: variant.selling_price,
        wholesale_price: variant.wholesale_price ?? '',
        quantity: variant.quantity ?? 0,
        is_active: variant.is_active ?? true,
    })),
});

const selectedImage = ref(null);

const imagePreview = computed(() => {
    if (selectedImage.value) {
        return URL.createObjectURL(selectedImage.value);
    }

    return props.product?.image ? `/storage/${props.product.image}` : null;
});

function setImage(event) {
    const file = event.target.files?.[0] ?? null;
    form.image = file;
    selectedImage.value = file;
}

function addVariant() {
    form.variants.push({
        name: '',
        sku: '',
        barcode: '',
        purchase_price: form.purchase_price,
        selling_price: form.selling_price,
        wholesale_price: form.wholesale_price,
        quantity: 0,
        is_active: true,
    });
}

function removeVariant(index) {
    form.variants.splice(index, 1);
}

function submit() {
    const options = { forceFormData: true, onSuccess: () => emit('close') };

    form.transform((data) => {
        const payload = { ...data };

        if (!payload.image) {
            delete payload.image;
        }

        if (props.product) {
            payload._method = 'put';
        }

        return payload;
    });

    if (props.product) {
        form.post(`/products/${props.product.id}`, options);
        return;
    }

    form.post('/products', options);
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-sm font-medium">Kategori</label>
                <select v-model="form.category_id" class="input-soft mt-1 w-full" required>
                    <option value="" disabled>Pilih kategori</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Merek</label>
                <select v-model="form.brand_id" class="input-soft mt-1 w-full">
                    <option value="">Tanpa merek</option>
                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                </select>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium">Nama</label>
            <input v-model="form.name" class="input-soft mt-1 w-full" required>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-sm font-medium">SKU</label>
                <input v-model="form.sku" class="input-soft mt-1 w-full" required>
            </div>
            <div>
                <label class="text-sm font-medium">Barcode</label>
                <input v-model="form.barcode" class="input-soft mt-1 w-full">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-4">
            <div>
                <label class="text-sm font-medium">Harga beli</label>
                <input v-model="form.purchase_price" type="number" min="0" class="input-soft mt-1 w-full" required>
            </div>
            <div>
                <label class="text-sm font-medium">Harga jual</label>
                <input v-model="form.selling_price" type="number" min="0" class="input-soft mt-1 w-full" required>
            </div>
            <div>
                <label class="text-sm font-medium">Harga grosir</label>
                <input v-model="form.wholesale_price" type="number" min="0" class="input-soft mt-1 w-full">
            </div>
            <div>
                <label class="text-sm font-medium">Satuan</label>
                <input v-model="form.unit" class="input-soft mt-1 w-full" required>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium">Deskripsi</label>
            <textarea v-model="form.description" class="input-soft mt-1 w-full" rows="2" />
        </div>
        <div>
            <label class="text-sm font-medium">Foto produk</label>
            <input type="file" accept="image/jpeg,image/png,image/webp" class="input-soft mt-1 w-full" @change="setImage">
            <img v-if="imagePreview" :src="imagePreview" alt="" class="mt-2 h-20 w-20 rounded-2xl object-cover">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div v-if="!product">
                <label class="text-sm font-medium">Stok awal</label>
                <input v-model="form.initial_stock" type="number" min="0" class="input-soft mt-1 w-full" required>
            </div>
            <div>
                <label class="text-sm font-medium">Stok minimum</label>
                <input v-model="form.minimum_stock" type="number" min="0" class="input-soft mt-1 w-full" required>
            </div>
        </div>
        <div class="space-y-3 rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">Varian</p>
                <button type="button" class="btn-ghost" @click="addVariant">Tambah varian</button>
            </div>
            <div v-for="(variant, index) in form.variants" :key="index" class="grid gap-2 sm:grid-cols-6">
                <input v-model="variant.name" class="input-soft" placeholder="Nama">
                <input v-model="variant.sku" class="input-soft" placeholder="SKU">
                <input v-model="variant.barcode" class="input-soft" placeholder="Barcode">
                <input v-model="variant.selling_price" type="number" class="input-soft" placeholder="Harga">
                <input v-model="variant.quantity" type="number" class="input-soft" placeholder="Stok">
                <button type="button" class="btn-danger" @click="removeVariant(index)">Hapus</button>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600">
            Aktif
        </label>
        <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="btn-secondary" @click="emit('close')">Batal</button>
            <button class="btn-primary" :disabled="form.processing">Simpan</button>
        </div>
    </form>
</template>
