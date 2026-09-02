<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    setting: Object,
});

const form = useForm({
    store_name: props.setting.store_name,
    address: props.setting.address,
    phone: props.setting.phone,
    email: props.setting.email,
    tax_rate: props.setting.tax_rate,
    tax_inclusive: props.setting.tax_inclusive,
    invoice_prefix: props.setting.invoice_prefix,
    receipt_footer: props.setting.receipt_footer,
    allow_discount: props.setting.allow_discount,
    allow_negative_stock: props.setting.allow_negative_stock,
    loyalty_enabled: props.setting.loyalty_enabled ?? true,
    loyalty_earn_points: props.setting.loyalty_earn_points ?? 1000,
    loyalty_spend_amount: props.setting.loyalty_spend_amount ?? 10000,
    timezone: props.setting.timezone ?? 'Asia/Jakarta',
    currency: props.setting.currency ?? 'IDR',
    logo: null,
});
</script>

<template>
    <AdminLayout>
        <Head title="Pengaturan" />
        <PageHeader title="Pengaturan Toko" subtitle="Nama toko, pajak, dan aturan kasir." />

        <form class="admin-card mt-6 max-w-2xl space-y-4 p-6" @submit.prevent="form.transform((data) => ({
            ...data,
            tax_inclusive: data.tax_inclusive ? 1 : 0,
            allow_discount: data.allow_discount ? 1 : 0,
            allow_negative_stock: data.allow_negative_stock ? 1 : 0,
            loyalty_enabled: data.loyalty_enabled ? 1 : 0,
            _method: 'put',
        })).post('/settings', { forceFormData: true })">
            <div>
                <label class="text-sm font-medium">Nama toko</label>
                <input v-model="form.store_name" class="input-soft mt-1 w-full" required>
            </div>
            <div>
                <label class="text-sm font-medium">Logo toko</label>
                <input type="file" accept="image/*" class="input-soft mt-1 w-full" @change="form.logo = $event.target.files[0]">
                <img v-if="setting.logo" :src="`/storage/${setting.logo}`" alt="" class="mt-2 h-16 rounded-xl object-contain">
            </div>
            <div>
                <label class="text-sm font-medium">Alamat</label>
                <textarea v-model="form.address" class="input-soft mt-1 w-full" rows="3" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Telepon</label>
                    <input v-model="form.phone" class="input-soft mt-1 w-full">
                </div>
                <div>
                    <label class="text-sm font-medium">Email</label>
                    <input v-model="form.email" class="input-soft mt-1 w-full">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Pajak %</label>
                    <input v-model="form.tax_rate" type="number" min="0" class="input-soft mt-1 w-full">
                </div>
                <div>
                    <label class="text-sm font-medium">Prefix invoice</label>
                    <input v-model="form.invoice_prefix" class="input-soft mt-1 w-full">
                </div>
            </div>
            <div>
                <label class="text-sm font-medium">Footer struk</label>
                <textarea v-model="form.receipt_footer" class="input-soft mt-1 w-full" rows="3" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.tax_inclusive" type="checkbox" class="rounded border-slate-300 text-teal-600">
                Pajak termasuk harga
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.allow_discount" type="checkbox" class="rounded border-slate-300 text-teal-600">
                Izinkan diskon
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.allow_negative_stock" type="checkbox" class="rounded border-slate-300 text-teal-600">
                Izinkan stok negatif
            </label>

            <div class="rounded-2xl border border-slate-200 p-4">
                <label class="flex items-center justify-between gap-3 text-sm font-medium">
                    <span>Poin member</span>
                    <span class="flex items-center gap-2 font-normal">
                        <span class="text-xs text-slate-500">{{ form.loyalty_enabled ? 'Aktif' : 'Nonaktif' }}</span>
                        <input v-model="form.loyalty_enabled" type="checkbox" class="rounded border-slate-300 text-teal-600">
                    </span>
                </label>
                <p class="mt-1 text-xs text-slate-500">Hanya member yang mendapat poin saat belanja. 1 poin = Rp 1 dan bisa dipakai langsung saat bayar.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2" :class="{ 'opacity-50': !form.loyalty_enabled }">
                    <div>
                        <label class="text-sm font-medium">Poin didapat</label>
                        <input v-model="form.loyalty_earn_points" type="number" min="1" class="input-soft mt-1 w-full" :disabled="!form.loyalty_enabled">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Setiap belanja (Rp)</label>
                        <input v-model="form.loyalty_spend_amount" type="number" min="1" class="input-soft mt-1 w-full" :disabled="!form.loyalty_enabled">
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Contoh: belanja Rp{{ Number(form.loyalty_spend_amount || 0).toLocaleString('id-ID') }}
                    mendapat {{ Number(form.loyalty_earn_points || 0).toLocaleString('id-ID') }} poin,
                    sama dengan Rp{{ Number(form.loyalty_earn_points || 0).toLocaleString('id-ID') }}.
                </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Timezone</label>
                    <input v-model="form.timezone" class="input-soft mt-1 w-full" required>
                </div>
                <div>
                    <label class="text-sm font-medium">Mata uang</label>
                    <input v-model="form.currency" class="input-soft mt-1 w-full" maxlength="3" required>
                </div>
            </div>
            <div class="pt-2">
                <button class="btn-primary" :disabled="form.processing">Simpan</button>
            </div>
        </form>
    </AdminLayout>
</template>
