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
    vouchers: Object,
    discountTypes: Array,
    creating: { type: Boolean, default: false },
});

const showCreate = ref(props.creating);
watch(() => props.creating, (value) => { showCreate.value = value; });

const form = useForm({
    code: '',
    name: '',
    discount_type: 'fixed',
    discount_value: 5000,
    max_uses: 10,
    expires_at: '',
    is_active: true,
});

function submit() {
    form.post('/vouchers', { onSuccess: () => { showCreate.value = false; form.reset(); form.discount_type = 'fixed'; form.is_active = true; } });
}

function remove(voucher) {
    if (confirm(`Hapus voucher ${voucher.code}?`)) {
        router.delete(`/vouchers/${voucher.id}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Voucher" />
        <PageHeader title="Voucher" subtitle="Kode diskon untuk kasir.">
            <a href="/promotions" class="btn-secondary">Promo</a>
            <button class="btn-primary" @click="showCreate = true">Tambah</button>
        </PageHeader>

        <DataTable class="mt-6" empty="Belum ada voucher." :is-empty="!vouchers.data.length">
            <template #head>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Diskon</th>
                    <th>Pemakaian</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </template>
            <tr v-for="voucher in vouchers.data" :key="voucher.id">
                <td class="font-semibold">{{ voucher.code }}</td>
                <td>{{ voucher.name }}</td>
                <td>{{ voucher.discount_type === 'percent' ? `${voucher.discount_value}%` : voucher.discount_value }}</td>
                <td>{{ voucher.used_count }} / {{ voucher.max_uses ?? '∞' }}</td>
                <td>
                    <StatusBadge :tone="voucher.is_active ? 'teal' : 'slate'">
                        {{ voucher.is_active ? 'Aktif' : 'Nonaktif' }}
                    </StatusBadge>
                </td>
                <td class="text-right">
                    <button class="btn-danger" @click="remove(voucher)">Hapus</button>
                </td>
            </tr>
            <template v-if="vouchers.links.length > 3" #footer>
                <Pagination :links="vouchers.links" />
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Tambah voucher" @close="showCreate = false">
            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.code" placeholder="Kode" class="input-soft w-full uppercase" required>
                <input v-model="form.name" placeholder="Nama" class="input-soft w-full" required>
                <select v-model="form.discount_type" class="input-soft w-full">
                    <option v-for="type in discountTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <input v-model="form.discount_value" type="number" min="0.01" step="0.01" class="input-soft w-full" required>
                <input v-model="form.max_uses" type="number" min="1" placeholder="Maks. pemakaian" class="input-soft w-full">
                <input v-model="form.expires_at" type="datetime-local" class="input-soft w-full">
                <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
