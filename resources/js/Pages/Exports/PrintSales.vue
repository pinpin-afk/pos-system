<script setup>
import { Head } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils/money';
import { onMounted } from 'vue';

defineProps({
    sales: Array,
});

onMounted(() => {
    window.print();
});
</script>

<template>
    <div class="min-h-screen bg-white p-8 text-slate-900">
        <Head title="Cetak penjualan" />
        <h1 class="text-2xl font-semibold">Laporan penjualan</h1>
        <table class="mt-6 w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="py-2">Invoice</th>
                    <th>Kasir</th>
                    <th>Pelanggan</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Pajak</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="sale in sales" :key="sale.id" class="border-b">
                    <td class="py-2">{{ sale.invoice_number }}</td>
                    <td>{{ sale.cashier?.name }}</td>
                    <td>{{ sale.customer?.name }}</td>
                    <td class="text-right">{{ formatRupiah(sale.grand_total) }}</td>
                    <td class="text-right">{{ formatRupiah(sale.tax) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
