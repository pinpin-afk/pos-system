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
    promotions: Object,
    products: Array,
    types: Array,
    creating: { type: Boolean, default: false },
});

const showCreate = ref(props.creating);
watch(() => props.creating, (value) => { showCreate.value = value; });

const form = useForm({
    name: '',
    type: 'percent',
    value: 10,
    buy_qty: 2,
    get_qty: 1,
    product_id: '',
    starts_at: '',
    ends_at: '',
    is_active: true,
});

function submit() {
    form.post('/promotions', { onSuccess: () => { showCreate.value = false; form.reset(); form.type = 'percent'; form.is_active = true; } });
}

function remove(promotion) {
    if (confirm(`Hapus ${promotion.name}?`)) {
        router.delete(`/promotions/${promotion.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Promo" />
        <PageHeader title="Promo" subtitle="Diskon otomatis, member, happy hour, dan beli X gratis Y.">
            <a href="/vouchers" class="btn-secondary">Voucher</a>
            <button class="btn-primary" @click="showCreate = true">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada promo." :is-empty="!promotions.data.length">
            <template #head>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Nilai</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="promotion in promotions.data" :key="promotion.id">
                <td class="font-semibold">{{ promotion.name }}</td>
                <td>{{ promotion.type }}</td>
                <td>{{ promotion.value }}</td>
                <td>
                    <StatusBadge :tone="promotion.is_active ? 'teal' : 'slate'">
                        {{ promotion.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-danger" @click="remove(promotion)">Hapus</button>
                </td>
            </tr>
            <template v-if="promotions.links.length > 3" #footer>
                <Pagination :links="promotions.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah promo" @close="showCreate = false">
            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.name" placeholder="Nama promo" class="input-soft w-full" required>
                <select v-model="form.type" class="input-soft w-full">
                    <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <input v-if="form.type !== 'buy_x_get_y'" v-model="form.value" type="number" min="0" step="0.01" placeholder="Nilai (%)" class="input-soft w-full">
                <div v-if="form.type === 'buy_x_get_y'" class="grid grid-cols-2 gap-2">
                    <input v-model="form.buy_qty" type="number" min="1" placeholder="Beli" class="input-soft">
                    <input v-model="form.get_qty" type="number" min="1" placeholder="Gratis" class="input-soft">
                    <select v-model="form.product_id" class="input-soft col-span-2">
                        <option value="">Pilih produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                </div>
                <div v-if="form.type === 'happy_hour'" class="grid grid-cols-2 gap-2">
                    <input v-model="form.starts_at" type="time" class="input-soft">
                    <input v-model="form.ends_at" type="time" class="input-soft">
                </div>
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
