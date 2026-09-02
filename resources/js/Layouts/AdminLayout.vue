<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import BrandMark from '@/Components/BrandMark.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import SidebarIcon from '@/Components/SidebarIcon.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const storeName = computed(() => page.props.store?.store_name ?? 'POS');
const logo = computed(() => page.props.store?.logo);
const permissions = computed(() => user.value?.permissions ?? []);

function can(permission) {
    return permissions.value.includes(permission);
}

const navGroups = computed(() => [
    {
        title: 'Operasi',
        items: [
            can('dashboard.view') && { href: '/dashboard', label: 'Dashboard', icon: 'dashboard' },
            { href: '/pos', label: 'Kasir', icon: 'pos', newTab: true },
        ].filter(Boolean),
    },
    {
        title: 'Katalog',
        items: [
            can('products.view') && { href: '/products', label: 'Produk', icon: 'products' },
            can('categories.manage') && { href: '/categories', label: 'Kategori', icon: 'categories' },
            can('brands.manage') && { href: '/brands', label: 'Merek', icon: 'brands' },
            can('stock.view') && { href: '/stock', label: 'Stok', icon: 'stock' },
            can('stock.opname') && { href: '/stock-opnames', label: 'Stock opname', icon: 'opname' },
            can('transfers.view') && { href: '/transfers', label: 'Transfer stok', icon: 'returns' },
            can('customers.view') && { href: '/customers', label: 'Pelanggan', icon: 'customers' },
        ].filter(Boolean),
    },
    {
        title: 'Bisnis',
        items: [
            can('suppliers.view') && { href: '/suppliers', label: 'Supplier', icon: 'suppliers' },
            can('purchases.view') && { href: '/purchases', label: 'Pembelian', icon: 'purchases' },
            can('purchases.view') && { href: '/purchase-returns', label: 'Retur supplier', icon: 'returns' },
            can('sales.view') && { href: '/sales', label: 'Penjualan', icon: 'sales' },
            can('expenses.view') && { href: '/expenses', label: 'Pengeluaran', icon: 'purchases' },
            can('promotions.manage') && { href: '/promotions', label: 'Promo', icon: 'labels' },
            can('reports.view') && { href: '/reports', label: 'Laporan', icon: 'reports' },
            can('insights.view') && { href: '/insights', label: 'Insights', icon: 'reports' },
            can('shifts.view') && { href: '/shifts', label: 'Shift', icon: 'shifts' },
        ].filter(Boolean),
    },
    {
        title: 'Sistem',
        items: [
            can('imports.manage') && { href: '/imports', label: 'Import', icon: 'imports' },
            can('labels.print') && { href: '/labels', label: 'Label barcode', icon: 'labels' },
            can('users.manage') && { href: '/users', label: 'Pengguna', icon: 'users' },
            can('branches.manage') && { href: '/branches', label: 'Cabang', icon: 'suppliers' },
            can('warehouses.manage') && { href: '/warehouses', label: 'Gudang', icon: 'stock' },
            can('activity.view') && { href: '/activity', label: 'Activity log', icon: 'opname' },
            can('settings.manage') && { href: '/settings', label: 'Pengaturan', icon: 'settings' },
            { href: '/profile', label: 'Profil', icon: 'users' },
        ].filter(Boolean),
    },
].filter((group) => group.items.length));

function isActive(item) {
    return page.url === item.href || page.url.startsWith(`${item.href}/`) || page.url.startsWith(`${item.href}?`);
}

function initial(name) {
    return String(name || 'A').trim().charAt(0).toUpperCase();
}

const sidebarOpen = ref(false);
const showNotes = ref(false);
const notifications = computed(() => page.props.notifications ?? []);
const branches = computed(() => page.props.location?.branches ?? []);
const currentBranch = computed(() => page.props.location?.branch);

function closeSidebar() {
    sidebarOpen.value = false;
}

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        closeSidebar();
    }
}

watch(() => page.url, closeSidebar);

watch(sidebarOpen, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
    if (!open) {
        showNotes.value = false;
    }
});

function switchBranch(event) {
    const id = Number(event.target.value);

    if (!id) {
        return;
    }

    router.post(`/branches/${id}/switch`);
}

function markRead(id) {
    router.post(`/notifications/${id}/read`);
}

let poll = null;

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    poll = setInterval(() => {
        router.reload({ only: ['notifications'], preserveScroll: true, preserveState: true });
    }, 30000);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');

    if (poll) {
        clearInterval(poll);
    }
});
</script>

<template>
    <div class="admin-shell min-h-screen overflow-x-clip bg-[var(--admin-canvas)] text-[var(--admin-text)]">
        <FlashMessage />

        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200/80 bg-white/95 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur xl:hidden dark:border-white/10 dark:bg-[#121a2b]/95">
            <button
                type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-700 dark:bg-white/5 dark:text-slate-200"
                aria-label="Buka menu"
                :aria-expanded="sidebarOpen"
                @click="toggleSidebar"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
            <BrandMark :src="logo" size="sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ storeName }}</p>
                <p class="truncate text-[11px] text-slate-400">{{ currentBranch?.name ?? 'Admin' }}</p>
            </div>
            <span
                v-if="notifications.length"
                class="rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-bold text-white"
            >
                {{ notifications.length }}
            </span>
        </header>

        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/55 xl:hidden"
            @click="closeSidebar"
        />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[min(260px,88vw)] flex-col border-r border-slate-200/80 bg-white transition-transform duration-200 dark:border-white/10 dark:bg-[#121a2b] xl:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : 'max-xl:-translate-x-full'"
        >
            <div class="flex items-center gap-3 px-5 py-5">
                <BrandMark :src="logo" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ storeName }}</p>
                    <p class="text-[11px] text-slate-400">Admin dashboard</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 xl:hidden dark:bg-white/5 dark:text-slate-300"
                    aria-label="Tutup menu"
                    @click="closeSidebar"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="pos-scroll flex-1 space-y-5 overflow-y-auto px-3 pb-4">
                <div v-for="group in navGroups" :key="group.title">
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                        {{ group.title }}
                    </p>
                    <div class="space-y-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition"
                            :class="isActive(item) ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-white/5'"
                            :target="item.newTab ? '_blank' : undefined"
                            :rel="item.newTab ? 'noopener' : undefined"
                        >
                            <SidebarIcon :name="item.icon" />
                            <span class="flex-1">{{ item.label }}</span>
                            <svg
                                v-if="item.newTab"
                                class="h-3.5 w-3.5 opacity-50"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7M9 7h8v8" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="space-y-3 border-t border-slate-100 p-4 dark:border-white/10">
                <select
                    v-if="branches.length > 1"
                    class="input-soft w-full text-sm"
                    :value="currentBranch?.id"
                    @change="switchBranch"
                >
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                </select>
                <div class="relative">
                    <button class="flex w-full items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm dark:bg-white/5" @click="showNotes = !showNotes">
                        <span>Notifikasi</span>
                        <span v-if="notifications.length" class="rounded-full bg-amber-500 px-2 text-[11px] font-bold text-white">{{ notifications.length }}</span>
                    </button>
                    <div v-if="showNotes" class="absolute bottom-12 left-0 right-0 z-20 space-y-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-lg dark:border-white/10 dark:bg-[#121a2b]">
                        <p v-if="!notifications.length" class="px-1 py-4 text-center text-xs text-slate-500">Tidak ada notifikasi baru.</p>
                        <button
                            v-for="note in notifications"
                            :key="note.id"
                            class="block w-full rounded-xl bg-slate-50 p-2 text-left text-xs dark:bg-white/5"
                            @click="markRead(note.id)"
                        >
                            <span class="font-semibold">{{ note.title }}</span>
                            <span class="mt-0.5 block text-slate-500">{{ note.message }}</span>
                        </button>
                    </div>
                </div>
                <ThemeToggle />
                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-2 dark:bg-white/5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-xs font-bold text-slate-700 shadow-sm dark:bg-[#1a2436] dark:text-slate-100">
                        {{ initial(user?.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ user?.name }}</p>
                        <p class="text-[11px] capitalize text-slate-400">{{ user?.role }}</p>
                    </div>
                    <Link href="/logout" method="post" as="button" class="rounded-lg px-2 py-1 text-[11px] font-medium text-slate-500 hover:bg-white hover:text-slate-800 dark:hover:bg-white/10 dark:hover:text-white">
                        Keluar
                    </Link>
                </div>
            </div>
        </aside>

        <main class="min-h-screen px-4 py-5 sm:px-6 sm:py-6 xl:ml-[260px] xl:p-8">
            <slot />
        </main>
    </div>
</template>
