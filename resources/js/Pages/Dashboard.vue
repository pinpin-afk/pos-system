<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SidebarIcon from '@/Components/SidebarIcon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatNumber, formatRupiah } from '@/utils/money';
import { paymentMethodsLabel } from '@/utils/labels';

const props = defineProps({
    stats: Object,
    chart: { type: Array, default: () => [] },
    recentSales: Array,
    topProducts: Array,
    lowStock: Array,
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? 'Owner');
const branchName = computed(() => page.props.location?.branch?.name ?? page.props.store?.store_name ?? 'Toko');

const todayLabel = computed(() => new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
}).format(new Date()));

const todayDate = computed(() => new Date().toISOString().slice(0, 10));

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    if (hour < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
});

const averageTicket = computed(() => {
    if (! props.stats.transactions_today) {
        return 0;
    }

    return props.stats.revenue_today / props.stats.transactions_today;
});

const profitMargin = computed(() => {
    if (! props.stats.revenue_today) {
        return 0;
    }

    return Math.round((Number(props.stats.profit_today) / Number(props.stats.revenue_today)) * 100);
});

const maxSold = computed(() => Math.max(...props.topProducts.map((item) => Number(item.quantity) || 0), 1));
const maxChart = computed(() => Math.max(...props.chart.map((item) => Number(item.revenue) || 0), 1));

const weekRevenue = computed(() => props.chart.reduce((sum, day) => sum + (Number(day.revenue) || 0), 0));

const metricTiles = computed(() => [
    { label: 'Transaksi', value: formatNumber(props.stats.transactions_today), hint: 'Nota hari ini', href: '/sales', icon: 'sales', tone: 'teal' },
    { label: 'Rata-rata nota', value: formatRupiah(averageTicket.value), hint: 'Per transaksi', href: '/sales', icon: 'reports', tone: 'sky' },
    { label: 'Produk cabang', value: formatNumber(props.stats.products), hint: 'Ada di gudang ini', href: '/products', icon: 'products', tone: 'violet' },
    { label: 'Member cabang', value: formatNumber(props.stats.customers), hint: 'Pelanggan terdaftar', href: '/customers', icon: 'customers', tone: 'amber' },
]);

const shortcuts = [
    { href: '/pos', label: 'Buka kasir', icon: 'pos', newTab: true },
    { href: '/products', label: 'Produk', icon: 'products' },
    { href: '/stock', label: 'Stok', icon: 'stock' },
    { href: '/customers', label: 'Pelanggan', icon: 'customers' },
    { href: '/sales', label: 'Penjualan', icon: 'sales' },
    { href: '/reports', label: 'Laporan', icon: 'reports' },
];

const iconTones = {
    teal: 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-200',
    sky: 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
    violet: 'bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-200',
    amber: 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
};

function saleTime(sale) {
    if (! sale.completed_at) {
        return '';
    }

    return new Date(sale.completed_at).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function stockPercent(row) {
    const min = Number(row.minimum_stock) || 1;
    const qty = Number(row.quantity) || 0;

    return Math.min(100, Math.round((qty / min) * 100));
}

function productTone(name) {
    const palettes = [
        'bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-200',
        'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-200',
        'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
        'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
    ];
    const total = [...String(name || '')].reduce((sum, char) => sum + char.charCodeAt(0), 0);

    return palettes[total % palettes.length];
}

function chartHeight(day) {
    return Math.max(10, (Number(day.revenue) / maxChart.value) * 100);
}

function isToday(day) {
    return day.date === todayDate.value;
}

function compactRupiah(value) {
    const amount = Number(value) || 0;
    const abs = Math.abs(amount);

    if (abs >= 1_000_000_000) {
        return `Rp${(amount / 1_000_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`;
    }

    if (abs >= 1_000_000) {
        return `Rp${(amount / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    }

    if (abs >= 1_000) {
        return `Rp${Math.round(amount / 1_000).toLocaleString('id-ID')} rb`;
    }

    return formatRupiah(amount);
}
</script>

<template>
    <AdminLayout>
        <Head title="Dashboard" />

        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <span>{{ todayLabel }}</span>
                    <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600" />
                    <span class="inline-flex max-w-full items-center truncate rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800 dark:bg-teal-500/15 dark:text-teal-200">
                        {{ branchName }}
                    </span>
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{{ greeting }}, {{ userName }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Ringkasan cabang ini — omzet, stok, dan transaksi hari ini.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                <Link href="/reports" class="btn-secondary w-full sm:w-auto">Laporan</Link>
                <Link href="/pos" target="_blank" rel="noopener" class="btn-primary w-full sm:w-auto">Buka Kasir</Link>
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:mt-8 sm:gap-4 lg:grid-cols-12">
            <section class="relative min-w-0 overflow-hidden rounded-[24px] bg-slate-900 p-5 text-white shadow-[0_20px_50px_rgb(15,23,42,0.18)] sm:rounded-[28px] sm:p-7 lg:col-span-7 dark:bg-[#0f1729]">
                <div class="absolute -right-10 -top-12 h-44 w-44 rounded-full bg-teal-400/25 blur-2xl" />
                <div class="absolute -bottom-16 right-10 h-36 w-36 rounded-full bg-sky-400/10 blur-2xl" />
                <div class="relative flex h-full flex-col justify-between gap-6 sm:gap-8">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-300">Omzet hari ini</p>
                            <p class="mt-2 break-words text-[clamp(1.75rem,7vw,3rem)] font-bold leading-tight tracking-tight sm:mt-3">
                                {{ formatRupiah(stats.revenue_today) }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-teal-100">
                            Live
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:gap-4">
                        <div class="rounded-2xl bg-white/8 px-2.5 py-3 ring-1 ring-white/10 sm:px-4">
                            <p class="text-[10px] uppercase leading-tight tracking-wide text-slate-400 sm:text-[11px]">Rata-rata nota</p>
                            <p class="mt-1 truncate text-sm font-semibold sm:text-lg">{{ compactRupiah(averageTicket) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/8 px-2.5 py-3 ring-1 ring-white/10 sm:px-4">
                            <p class="text-[10px] uppercase leading-tight tracking-wide text-slate-400 sm:text-[11px]">Transaksi</p>
                            <p class="mt-1 truncate text-sm font-semibold sm:text-lg">{{ formatNumber(stats.transactions_today) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/8 px-2.5 py-3 ring-1 ring-white/10 sm:px-4">
                            <p class="text-[10px] uppercase leading-tight tracking-wide text-slate-400 sm:text-[11px]">7 hari</p>
                            <p class="mt-1 truncate text-sm font-semibold sm:text-lg">{{ compactRupiah(weekRevenue) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid min-w-0 gap-3 sm:grid-cols-2 sm:gap-4 lg:col-span-5 lg:grid-cols-1">
                <section class="admin-card flex items-center justify-between gap-4 p-4 sm:p-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Laba kotor</p>
                        <p class="mt-2 truncate text-xl font-bold tracking-tight sm:text-2xl">{{ formatRupiah(stats.profit_today) }}</p>
                        <p class="mt-2 text-xs text-slate-400">Margin {{ profitMargin }}% dari omzet.</p>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 sm:h-12 sm:w-12 dark:bg-emerald-500/15 dark:text-emerald-200">
                        <SidebarIcon name="reports" />
                    </div>
                </section>
                <section class="admin-card flex items-center justify-between gap-4 p-4 sm:p-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Stok menipis</p>
                        <p class="mt-2 text-xl font-bold tracking-tight sm:text-2xl" :class="stats.low_stock ? 'text-amber-600 dark:text-amber-400' : ''">
                            {{ formatNumber(stats.low_stock) }}
                        </p>
                        <Link href="/stock" class="mt-2 inline-flex text-xs font-semibold text-teal-700 dark:text-teal-300">Lihat stok</Link>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl sm:h-12 sm:w-12" :class="stats.low_stock ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-200'">
                        <SidebarIcon name="stock" />
                    </div>
                </section>
            </div>
        </div>

        <div class="mt-3 grid gap-3 sm:mt-4 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
            <Link
                v-for="tile in metricTiles"
                :key="tile.label"
                :href="tile.href"
                class="admin-card group flex items-center gap-3 p-4 transition hover:-translate-y-0.5 sm:gap-4 sm:p-5"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl sm:h-11 sm:w-11" :class="iconTones[tile.tone]">
                    <SidebarIcon :name="tile.icon" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ tile.label }}</p>
                    <p class="mt-0.5 truncate text-base font-bold tracking-tight sm:text-lg">{{ tile.value }}</p>
                    <p class="text-[11px] text-slate-400">{{ tile.hint }}</p>
                </div>
            </Link>
        </div>

        <div class="mt-5 grid gap-3 sm:mt-6 sm:gap-4 lg:grid-cols-12">
            <section class="admin-card min-w-0 p-4 sm:p-6 lg:col-span-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Penjualan 7 hari</h2>
                        <p class="mt-1 text-xs text-slate-400">Omzet harian cabang aktif.</p>
                    </div>
                    <p class="text-sm font-semibold text-teal-700 dark:text-teal-300">{{ formatRupiah(weekRevenue) }}</p>
                </div>
                <div class="mt-5 flex items-end gap-1.5 sm:mt-6 sm:gap-3">
                    <div
                        v-for="day in chart"
                        :key="day.date"
                        class="group flex min-w-0 flex-1 flex-col items-center gap-1.5 sm:gap-2"
                    >
                        <p class="w-full truncate text-center text-[9px] font-medium text-slate-500 sm:text-[10px] dark:text-slate-400">
                            {{ compactRupiah(day.revenue) }}
                        </p>
                        <div class="flex h-24 w-full items-end overflow-hidden rounded-xl bg-slate-100 sm:h-32 sm:rounded-2xl dark:bg-white/5">
                            <div
                                class="w-full rounded-xl transition duration-300 group-hover:brightness-110 sm:rounded-2xl"
                                :class="isToday(day) ? 'bg-linear-to-t from-teal-700 to-teal-400' : 'bg-linear-to-t from-teal-600 to-teal-400/80'"
                                :style="{ height: `${chartHeight(day)}%` }"
                            />
                        </div>
                        <p class="text-[10px] font-medium sm:text-[11px]" :class="isToday(day) ? 'text-teal-700 dark:text-teal-300' : 'text-slate-500 dark:text-slate-400'">
                            {{ day.label }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="admin-card min-w-0 p-4 sm:p-6 lg:col-span-4">
                <h2 class="font-semibold">Aksi cepat</h2>
                <p class="mt-1 text-xs text-slate-400">Langsung ke halaman yang sering dipakai.</p>
                <div class="mt-4 grid grid-cols-2 gap-2 sm:mt-5 md:grid-cols-3 lg:grid-cols-2">
                    <Link
                        v-for="item in shortcuts"
                        :key="item.href"
                        :href="item.href"
                        class="flex min-w-0 items-center gap-2 rounded-2xl bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-teal-500/10 dark:hover:text-teal-200"
                        :target="item.newTab ? '_blank' : undefined"
                        :rel="item.newTab ? 'noopener' : undefined"
                    >
                        <SidebarIcon :name="item.icon" class="shrink-0" />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </div>
            </section>
        </div>

        <div class="mt-5 grid gap-4 sm:mt-6 sm:gap-6 xl:grid-cols-5">
            <section class="admin-card min-w-0 p-4 sm:p-6 xl:col-span-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold">Transaksi terbaru</h2>
                        <p class="mt-1 text-xs text-slate-400">Nota selesai di cabang ini.</p>
                    </div>
                    <Link href="/sales" class="shrink-0 text-xs font-semibold text-teal-700 dark:text-teal-300">Lihat semua</Link>
                </div>
                <div v-if="recentSales.length" class="mt-4 divide-y divide-slate-100 dark:divide-white/5">
                    <Link
                        v-for="sale in recentSales"
                        :key="sale.id"
                        :href="`/sales/${sale.id}`"
                        class="-mx-1 flex items-center gap-3 rounded-2xl px-1 py-3 transition first:pt-3 last:pb-3 hover:bg-slate-50 sm:mx-0 sm:gap-4 sm:px-2 dark:hover:bg-white/5"
                    >
                        <div class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xs font-bold text-slate-600 sm:flex dark:bg-white/10 dark:text-slate-200">
                            {{ sale.invoice_number?.slice(-2) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ sale.invoice_number }}</p>
                            <p class="truncate text-xs text-slate-400">
                                {{ sale.cashier?.name }} · {{ sale.customer?.name }} · {{ saleTime(sale) }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-bold tabular-nums">{{ formatRupiah(sale.grand_total) }}</p>
                            <p class="mt-0.5 text-[10px] text-slate-400 sm:hidden">{{ paymentMethodsLabel(sale) }}</p>
                            <StatusBadge class="mt-1 max-sm:hidden">{{ paymentMethodsLabel(sale) }}</StatusBadge>
                        </div>
                    </Link>
                </div>
                <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center sm:mt-8 sm:py-12 dark:border-white/10">
                    <p class="text-sm font-medium">Belum ada transaksi</p>
                    <p class="mt-1 text-xs text-slate-400">Nota pertama hari ini akan muncul di sini.</p>
                    <Link href="/pos" target="_blank" rel="noopener" class="btn-primary mt-4">Buka Kasir</Link>
                </div>
            </section>

            <section class="admin-card min-w-0 p-4 sm:p-6 xl:col-span-2">
                <h2 class="font-semibold">Produk terlaris hari ini</h2>
                <p class="mt-1 text-xs text-slate-400">Berdasarkan jumlah terjual.</p>
                <div v-if="topProducts.length" class="mt-5 flex flex-col gap-5">
                    <div v-for="(item, index) in topProducts" :key="item.name">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl text-xs font-bold" :class="productTone(item.name)">
                                    {{ index + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ item.name }}</p>
                                    <p class="text-xs text-slate-400">{{ formatNumber(item.quantity) }} terjual</p>
                                </div>
                            </div>
                            <p class="shrink-0 text-sm font-semibold tabular-nums">{{ formatRupiah(item.revenue) }}</p>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full rounded-full bg-teal-500" :style="{ width: `${(Number(item.quantity) / maxSold) * 100}%` }" />
                        </div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 sm:mt-8 sm:py-12 dark:border-white/10">
                    Belum ada produk terlaris.
                </div>
            </section>
        </div>

        <section class="admin-card mt-5 p-4 sm:mt-6 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="font-semibold">Stok menipis</h2>
                    <p class="mt-1 text-xs text-slate-400">Di bawah atau sama dengan stok minimum gudang ini.</p>
                </div>
                <Link href="/stock" class="shrink-0 text-xs font-semibold text-teal-700 dark:text-teal-300">Kelola stok</Link>
            </div>
            <div v-if="lowStock.length" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="row in lowStock"
                    :key="row.id"
                    class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4 dark:border-amber-500/20 dark:bg-amber-500/10"
                >
                    <p class="truncate text-sm font-semibold">{{ row.product?.name }}</p>
                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ row.product?.sku }}</p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white dark:bg-white/10">
                        <div class="h-full rounded-full bg-amber-500" :style="{ width: `${stockPercent(row)}%` }" />
                    </div>
                    <p class="mt-2 text-xs font-medium text-amber-800 dark:text-amber-200">
                        {{ formatNumber(row.quantity) }} / min {{ formatNumber(row.minimum_stock) }}
                    </p>
                </div>
            </div>
            <div v-else class="mt-6 rounded-2xl bg-teal-50 px-4 py-8 text-center text-sm font-medium text-teal-800 dark:bg-teal-500/10 dark:text-teal-200">
                Semua stok masih aman.
            </div>
        </section>
    </AdminLayout>
</template>
