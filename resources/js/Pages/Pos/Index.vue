<script setup>
import PosLayout from '@/Layouts/PosLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { formatRupiah } from '@/utils/money';
import BrandMark from '@/Components/BrandMark.vue';

const props = defineProps({
    products: Array,
    categories: Array,
    customers: Array,
    heldSales: Array,
    heldSale: Object,
    shift: Object,
    settings: Object,
    paymentMethods: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const storeName = computed(() => page.props.store?.store_name ?? 'POS');
const storeLogo = computed(() => page.props.store?.logo);

const search = ref('');
const categoryId = ref(null);
const searchInput = ref(null);
const showPay = ref(false);
const showHold = ref(false);
const showCustomer = ref(false);
const showCustomerPicker = ref(false);
const showKas = ref(false);
const showCart = ref(false);
const showActions = ref(false);
const discountItemId = ref(null);
const now = ref(new Date());
const cart = reactive([]);
const heldSaleId = ref(props.heldSale?.id ?? null);
const customerDirectory = ref([...(props.customers ?? [])]);
const customerId = ref(props.customers.find((c) => c.is_walk_in)?.id ?? props.customers[0]?.id);
const customerQuery = ref('');
const customerResults = ref([]);
const customerPickerInput = ref(null);
const discountType = ref(null);
const discountValue = ref(0);
let customerSearchTimer = null;

const palettes = [
    'bg-teal-100 text-teal-800',
    'bg-sky-100 text-sky-800',
    'bg-violet-100 text-violet-800',
    'bg-amber-100 text-amber-800',
    'bg-rose-100 text-rose-800',
    'bg-emerald-100 text-emerald-800',
    'bg-orange-100 text-orange-800',
    'bg-indigo-100 text-indigo-800',
];

const customerForm = useForm({
    name: '',
    phone: '',
});

const kasForm = useForm({
    type: 'out',
    amount: 0,
    reason: '',
});

const payForm = useForm({
    method: 'cash',
    tendered: 0,
    amount: 0,
    reference_number: '',
    label: '',
    voucher_code: '',
    redeem_points: 0,
});

const extraPayments = ref([]);
const QUEUE_KEY = 'pos_offline_queue';

let clock = null;

onMounted(() => {
    if (props.heldSale) {
        loadHeld(props.heldSale);
    }

    if (window.matchMedia('(min-width: 1024px)').matches) {
        searchInput.value?.focus();
    }

    clock = setInterval(() => {
        now.value = new Date();
    }, 1000);

    syncOfflineQueue();
    window.addEventListener('online', syncOfflineQueue);
});

onUnmounted(() => {
    if (clock) {
        clearInterval(clock);
    }

    if (customerSearchTimer) {
        clearTimeout(customerSearchTimer);
    }

    window.removeEventListener('online', syncOfflineQueue);
});

onUnmounted(() => {
    if (clock) {
        clearInterval(clock);
    }
});

watch(() => props.heldSale, (sale) => {
    if (sale) {
        loadHeld(sale);
    }
});

function loadHeld(sale) {
    cart.splice(0, cart.length);
    sale.items.forEach((item) => {
        const product = props.products.find((row) => row.id === item.product_id);
        cart.push({
            product_id: item.product_id,
            product_variant_id: item.product_variant_id ?? null,
            name: item.variant_name ? `${item.product_name} · ${item.variant_name}` : item.product_name,
            sku: product?.sku ?? item.sku,
            selling_price: Number(item.selling_price),
            stock: product?.stock ?? 0,
            quantity: Number(item.quantity),
            discount_type: item.discount_type,
            discount_value: Number(item.discount_value || 0),
        });
    });
    customerId.value = sale.customer_id;
    discountType.value = sale.discount_type;
    discountValue.value = Number(sale.discount_value || 0);
    heldSaleId.value = sale.id;
}

const filteredProducts = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.products.filter((product) => {
        const matchCategory = !categoryId.value || product.category_id === categoryId.value;
        const matchQuery = !query
            || product.name.toLowerCase().includes(query)
            || product.sku.toLowerCase().includes(query)
            || (product.barcode && product.barcode.includes(query))
            || (product.variants || []).some((variant) => variant.sku?.toLowerCase().includes(query) || variant.barcode?.includes(query));

        return matchCategory && matchQuery;
    });
});

const itemCount = computed(() => cart.reduce((sum, item) => sum + Number(item.quantity), 0));

const clockLabel = computed(() => now.value.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
}));

function productTone(name) {
    const total = [...String(name)].reduce((sum, char) => sum + char.charCodeAt(0), 0);

    return palettes[total % palettes.length];
}

function initial(name) {
    return String(name || '?').trim().charAt(0).toUpperCase();
}

function qtyInCart(productId) {
    return cart.filter((item) => item.product_id === productId).reduce((sum, item) => sum + Number(item.quantity), 0);
}

function addVariant(product, variant) {
    if (!props.settings.allow_negative_stock && Number(variant.quantity) <= 0 && Number(product.stock) <= 0) {
        return;
    }

    const existing = cart.find((item) => item.product_id === product.id && item.product_variant_id === variant.id);

    if (existing) {
        existing.quantity += 1;
        return;
    }

    cart.push({
        product_id: product.id,
        product_variant_id: variant.id,
        name: `${product.name} · ${variant.name}`,
        sku: variant.sku,
        selling_price: Number(variant.selling_price),
        stock: Number(variant.quantity),
        quantity: 1,
        discount_type: null,
        discount_value: 0,
    });
}

function addProduct(product) {
    const variants = product.variants || [];

    if (variants.length === 1) {
        addVariant(product, variants[0]);
        return;
    }

    if (variants.length > 1) {
        const chosen = window.prompt(`Pilih varian: ${variants.map((variant) => variant.name).join(', ')}`, variants[0].name);
        const variant = variants.find((row) => row.name.toLowerCase() === String(chosen || '').toLowerCase()) ?? variants[0];
        addVariant(product, variant);
        return;
    }

    if (!props.settings.allow_negative_stock && Number(product.stock) <= 0) {
        return;
    }

    const existing = cart.find((item) => item.product_id === product.id && !item.product_variant_id);

    if (existing) {
        existing.quantity += 1;
        return;
    }

    cart.push({
        product_id: product.id,
        product_variant_id: null,
        name: product.name,
        sku: product.sku,
        selling_price: Number(product.selling_price),
        stock: Number(product.stock),
        quantity: 1,
        discount_type: null,
        discount_value: 0,
    });
}

function onSearchEnter() {
    const query = search.value.trim();

    for (const product of props.products) {
        const variant = (product.variants || []).find((row) => row.barcode === query || row.sku === query);

        if (variant) {
            addVariant(product, variant);
            search.value = '';
            return;
        }
    }

    const exact = props.products.find((product) => product.barcode === query || product.sku === query);

    if (exact) {
        addProduct(exact);
        search.value = '';
    }
}

function changeQty(item, delta) {
    item.quantity = Math.max(1, Number(item.quantity) + delta);
}

function removeItem(item) {
    cart.splice(cart.indexOf(item), 1);
}

function lineDiscount(item) {
    const gross = item.selling_price * item.quantity;

    if (!item.discount_type || !item.discount_value) {
        return 0;
    }

    return item.discount_type === 'percent'
        ? gross * (item.discount_value / 100)
        : Number(item.discount_value);
}

function lineTotal(item) {
    return Math.max(item.selling_price * item.quantity - lineDiscount(item), 0);
}

const subtotal = computed(() => cart.reduce((sum, item) => sum + lineTotal(item), 0));

const transactionDiscount = computed(() => {
    if (!discountType.value || !discountValue.value) {
        return 0;
    }

    return discountType.value === 'percent'
        ? subtotal.value * (discountValue.value / 100)
        : Number(discountValue.value);
});

const afterDiscount = computed(() => Math.max(subtotal.value - transactionDiscount.value, 0));

const tax = computed(() => {
    const rate = Number(props.settings.tax_rate || 0) / 100;

    if (!rate) {
        return 0;
    }

    return props.settings.tax_inclusive
        ? afterDiscount.value - afterDiscount.value / (1 + rate)
        : afterDiscount.value * rate;
});

const grandTotal = computed(() => (
    props.settings.tax_inclusive ? afterDiscount.value : afterDiscount.value + tax.value
));

const quickTenders = computed(() => {
    const total = Math.ceil(payableTotal.value);
    const values = new Set([total]);
    const rounded = Math.ceil(total / 10000) * 10000;

    if (rounded > total) {
        values.add(rounded);
    }

    [20000, 50000, 100000, 150000, 200000, 500000].forEach((amount) => {
        if (amount >= total) {
            values.add(amount);
        }
    });

    return [...values].sort((left, right) => left - right).slice(0, 6);
});

const selectedCustomer = computed(() => customerDirectory.value.find((row) => row.id === customerId.value)
    ?? props.customers.find((row) => row.id === customerId.value));

const maxRedeemablePoints = computed(() => {
    if (!props.settings.loyalty_enabled || !selectedCustomer.value || selectedCustomer.value.is_walk_in) {
        return 0;
    }

    return Math.min(Number(selectedCustomer.value.points || 0), Math.floor(grandTotal.value));
});

const payableTotal = computed(() => Math.max(grandTotal.value - Number(payForm.redeem_points || 0), 0));

const change = computed(() => Math.max(Number(payForm.tendered || 0) - payableTotal.value, 0));

watch(() => props.customers, (list) => {
    (list ?? []).forEach(rememberCustomer);
});

watch(() => page.props.flash?.created_customer, (customer) => {
    if (!customer) {
        return;
    }

    rememberCustomer(customer);
    customerId.value = customer.id;
});

watch(showCustomerPicker, (open) => {
    if (!open) {
        return;
    }

    customerQuery.value = '';
    loadCustomers('');
    nextTick(() => customerPickerInput.value?.focus());
});

watch(customerQuery, (query) => {
    if (!showCustomerPicker.value) {
        return;
    }

    if (customerSearchTimer) {
        clearTimeout(customerSearchTimer);
    }

    customerSearchTimer = setTimeout(() => {
        loadCustomers(query);
    }, 200);
});

function rememberCustomer(customer) {
    const index = customerDirectory.value.findIndex((row) => row.id === customer.id);

    if (index === -1) {
        customerDirectory.value.push(customer);
        return;
    }

    customerDirectory.value[index] = customer;
}

function customerDisplayName(customer) {
    if (!customer) {
        return 'Pilih pelanggan';
    }

    return customer.is_walk_in ? 'Pelanggan umum' : customer.name;
}

async function loadCustomers(query) {
    const response = await fetch(`/pos/customers?q=${encodeURIComponent(query.trim())}`, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        return;
    }

    const payload = await response.json();
    customerResults.value = payload.customers ?? [];
}

function chooseCustomer(customer) {
    rememberCustomer(customer);
    customerId.value = customer.id;
    showCustomerPicker.value = false;
}

function openNewCustomer() {
    showCustomerPicker.value = false;
    showCustomer.value = true;
}

const earnedPoints = computed(() => {
    if (!props.settings.loyalty_enabled || !selectedCustomer.value || selectedCustomer.value.is_walk_in) {
        return 0;
    }

    const spend = Number(props.settings.loyalty_spend_amount || 0);
    const points = Number(props.settings.loyalty_earn_points || 0);

    if (spend <= 0 || points <= 0) {
        return 0;
    }

    return Math.floor(payableTotal.value * points / spend);
});

function payload() {
    return {
        customer_id: customerId.value,
        held_sale_id: heldSaleId.value,
        discount_type: discountType.value,
        discount_value: discountValue.value,
        voucher_code: payForm.voucher_code || null,
        redeem_points: Number(payForm.redeem_points || 0),
        items: cart.map((item) => ({
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            quantity: item.quantity,
            discount_type: item.discount_type,
            discount_value: item.discount_value,
        })),
    };
}

function paymentRows() {
    const rows = [{
        method: payForm.method,
        amount: extraPayments.value.length ? Number(payForm.amount || 0) : payableTotal.value,
        tendered: payForm.method === 'cash' ? Number(payForm.tendered || 0) : Number(payForm.amount || payableTotal.value),
        reference_number: payForm.reference_number,
        label: payForm.label,
    }];

    extraPayments.value.forEach((row) => {
        rows.push({
            method: row.method,
            amount: Number(row.amount || 0),
            tendered: row.method === 'cash' ? Number(row.tendered || row.amount || 0) : Number(row.amount || 0),
            reference_number: row.reference_number,
            label: row.label,
        });
    });

    return rows;
}

function openPay() {
    payForm.method = 'cash';
    payForm.redeem_points = 0;
    payForm.tendered = Math.ceil(grandTotal.value);
    payForm.amount = grandTotal.value;
    payForm.reference_number = '';
    payForm.label = '';
    extraPayments.value = [];
    showCart.value = false;
    showActions.value = false;
    showPay.value = true;
}

function capRedeemPoints() {
    const max = maxRedeemablePoints.value;
    const current = Math.max(0, Math.floor(Number(payForm.redeem_points || 0)));
    payForm.redeem_points = Math.min(current, max);
    payForm.amount = payableTotal.value;

    if (payForm.method === 'cash' && Number(payForm.tendered) < payableTotal.value) {
        payForm.tendered = Math.ceil(payableTotal.value);
    }
}

function useAllPoints() {
    payForm.redeem_points = maxRedeemablePoints.value;
    capRedeemPoints();
}

function clearPoints() {
    payForm.redeem_points = 0;
    capRedeemPoints();
}

function addSplit() {
    extraPayments.value.push({
        method: 'qris',
        amount: 0,
        tendered: 0,
        reference_number: '',
        label: '',
    });
}

function readQueue() {
    try {
        return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]');
    } catch {
        return [];
    }
}

function queueOffline(body) {
    const queue = readQueue();
    queue.push(body);
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    showPay.value = false;
    cart.splice(0, cart.length);
    window.alert('Koneksi terputus. Transaksi disimpan offline dan akan dikirim saat online.');
}

function syncOfflineQueue() {
    if (!navigator.onLine) {
        return;
    }

    const queue = readQueue();

    if (!queue.length) {
        return;
    }

    router.post('/pos/sync', { checkouts: queue }, {
        onSuccess: () => localStorage.removeItem(QUEUE_KEY),
    });
}

function checkout() {
    const body = extraPayments.value.length
        ? { ...payload(), payments: paymentRows() }
        : {
            ...payload(),
            payment: {
                method: payForm.method,
                amount: payableTotal.value,
                tendered: payForm.method === 'cash' ? payForm.tendered : payableTotal.value,
                reference_number: payForm.reference_number,
                label: payForm.label,
            },
        };

    if (!navigator.onLine) {
        queueOffline(body);
        return;
    }

    payForm
        .transform(() => body)
        .post('/pos/checkout', {
            onError: () => {
                if (!navigator.onLine) {
                    queueOffline(body);
                }
            },
        });
}

function hold() {
    showCart.value = false;
    router.post('/pos/hold', payload());
}

function addCustomer() {
    customerForm.post('/pos/customers', {
        onSuccess: () => {
            showCustomer.value = false;
            customerForm.reset();
        },
    });
}

function submitKas() {
    kasForm.post('/shifts/cash-movements', {
        onSuccess: () => {
            showKas.value = false;
            kasForm.reset();
            kasForm.type = 'out';
        },
    });
}

function resume(sale) {
    showHold.value = false;
    router.get('/pos', { held: sale.id });
}

function discardHold(sale) {
    router.delete(`/pos/held/${sale.id}`, {
        onSuccess: () => {
            if (heldSaleId.value === sale.id) {
                heldSaleId.value = null;
                cart.splice(0, cart.length);
            }
        },
    });
}

const paymentMeta = {
    cash: { label: 'Tunai', hint: 'Uang fisik' },
    qris: { label: 'QRIS', hint: 'Scan QR' },
    transfer: { label: 'Transfer', hint: 'Bank' },
    card: { label: 'Kartu', hint: 'Debit / kredit' },
    ewallet: { label: 'E-Wallet', hint: 'OVO / Dana / GoPay' },
    other: { label: 'Lainnya', hint: 'Custom' },
};
</script>

<template>
    <PosLayout>
        <Head title="Kasir" />
        <div class="flex min-h-0 flex-1 flex-col overflow-x-clip">
            <header class="relative z-20 shrink-0 border-b border-slate-200/80 bg-white">
                <div class="flex items-center gap-2 px-3 py-2.5 lg:gap-4 lg:px-5 lg:py-3">
                    <div class="flex min-w-0 items-center gap-2 lg:gap-3">
                        <BrandMark :src="storeLogo" class="hidden lg:block" />
                        <BrandMark :src="storeLogo" size="sm" class="lg:hidden" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold leading-tight">{{ storeName }}</p>
                            <p class="truncate text-[11px] text-slate-500 lg:text-xs">{{ clockLabel }} · Shift terbuka</p>
                        </div>
                    </div>

                    <div class="relative mx-2 hidden min-w-0 flex-1 lg:block">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                            </svg>
                        </span>
                        <input
                            ref="searchInput"
                            v-model="search"
                            placeholder="Scan barcode, cari nama, atau SKU lalu Enter"
                            class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 text-sm outline-none ring-teal-600/20 transition focus:border-teal-600 focus:bg-white focus:ring-4"
                            @keydown.enter.prevent="onSearchEnter"
                        >
                    </div>

                    <div class="ml-auto flex shrink-0 items-center gap-2">
                        <button
                            class="relative rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium hover:bg-slate-50 lg:px-3"
                            @click="showHold = true"
                        >
                            Hold
                            <span
                                v-if="heldSales.length"
                                class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white"
                            >
                                {{ heldSales.length }}
                            </span>
                        </button>
                        <button class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium hover:bg-slate-50 lg:inline-flex" @click="showKas = true">
                            Kas
                        </button>
                        <Link href="/shifts/close" class="hidden rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800 lg:inline-flex">
                            Tutup Shift
                        </Link>
                        <div class="ml-1 hidden items-center gap-2 rounded-2xl bg-slate-100 py-1 pl-1 pr-3 lg:flex">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-xs font-bold text-slate-700">
                                {{ initial(user?.name) }}
                            </div>
                            <div class="leading-tight">
                                <p class="text-xs font-semibold">{{ user?.name }}</p>
                                <div class="flex gap-2 text-[11px]">
                                    <Link v-if="user?.can_access_admin" href="/dashboard" target="_blank" rel="noopener" class="text-teal-700">Admin</Link>
                                    <Link href="/logout" method="post" as="button" class="text-slate-500">Keluar</Link>
                                </div>
                            </div>
                        </div>
                        <div class="relative lg:hidden">
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white"
                                aria-label="Menu kasir"
                                @click="showActions = !showActions"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                </svg>
                            </button>
                            <div
                                v-if="showActions"
                                class="absolute right-0 top-12 z-20 w-48 rounded-2xl border border-slate-200 bg-white p-2 shadow-lg"
                            >
                                <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-50" @click="showActions = false; showKas = true">Kas</button>
                                <Link href="/shifts/close" class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-50">Tutup Shift</Link>
                                <Link v-if="user?.can_access_admin" href="/dashboard" target="_blank" rel="noopener" class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-50">Admin</Link>
                                <Link href="/logout" method="post" as="button" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">Keluar</Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-3 pb-3 lg:hidden">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            placeholder="Scan / cari produk"
                            class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm outline-none ring-teal-600/20 transition focus:border-teal-600 focus:bg-white focus:ring-4"
                            @keydown.enter.prevent="onSearchEnter"
                        >
                    </div>
                </div>
            </header>

            <div class="flex min-h-0 flex-1 overflow-hidden">
                <section class="flex min-h-0 min-w-0 flex-1 flex-col px-3 pt-2 pb-[5.75rem] lg:p-4">
                    <div class="flex gap-2 overflow-x-auto pb-3">
                        <button
                            class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                            :class="!categoryId ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50'"
                            @click="categoryId = null"
                        >
                            Semua
                        </button>
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                            :class="categoryId === category.id ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50'"
                            @click="categoryId = category.id"
                        >
                            {{ category.name }}
                        </button>
                    </div>

                    <div class="pos-scroll grid min-h-0 flex-1 auto-rows-min grid-cols-2 gap-2 overflow-y-auto pr-0.5 sm:gap-3 md:grid-cols-3 xl:grid-cols-4">
                        <button
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="group relative flex h-full flex-col rounded-2xl border border-white bg-white p-3 text-left shadow-[0_8px_30px_rgb(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_rgb(15,23,42,0.08)] disabled:cursor-not-allowed disabled:opacity-50 sm:rounded-3xl sm:p-4"
                            :disabled="!settings.allow_negative_stock && Number(product.stock) <= 0"
                            @click="addProduct(product)"
                        >
                            <span
                                v-if="qtyInCart(product.id)"
                                class="absolute right-2 top-2 rounded-full bg-teal-600 px-2 py-0.5 text-[11px] font-bold text-white sm:right-3 sm:top-3"
                            >
                                {{ qtyInCart(product.id) }}
                            </span>
                            <img
                                v-if="product.image"
                                :src="`/storage/${product.image}`"
                                :alt="product.name"
                                class="h-10 w-10 rounded-2xl object-cover sm:h-12 sm:w-12"
                            >
                            <div v-else class="flex h-10 w-10 items-center justify-center rounded-2xl text-base font-bold sm:h-12 sm:w-12 sm:text-lg" :class="productTone(product.name)">
                                {{ initial(product.name) }}
                            </div>
                            <p class="mt-2 line-clamp-2 min-h-9 text-sm font-semibold leading-5 sm:mt-3 sm:min-h-10">{{ product.name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-400">{{ product.sku }}</p>
                            <div class="mt-auto flex items-end justify-between gap-1 pt-3 sm:pt-4">
                                <p class="truncate text-sm font-bold text-slate-900 sm:text-base">{{ formatRupiah(product.selling_price) }}</p>
                                <p class="shrink-0 text-[11px]" :class="Number(product.stock) <= 5 ? 'font-semibold text-amber-600' : 'text-slate-400'">
                                    Stok {{ product.stock }}
                                </p>
                            </div>
                        </button>

                        <div v-if="!filteredProducts.length" class="col-span-full flex h-64 flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-white/70 text-slate-500">
                            <p class="font-medium">Produk tidak ditemukan</p>
                            <p class="mt-1 text-sm">Coba nama lain, SKU, atau ganti kategori.</p>
                        </div>
                    </div>
                </section>

                <div
                    v-if="showCart"
                    class="fixed inset-0 z-20 bg-slate-950/50 lg:hidden"
                    @click="showCart = false"
                />

                <aside
                    class="flex min-h-0 flex-col overflow-hidden bg-white shadow-[0_20px_60px_rgb(15,23,42,0.08)] max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-30 max-lg:max-h-[min(92dvh,100%)] max-lg:rounded-t-[28px] lg:my-4 lg:mr-4 lg:w-[420px] lg:shrink-0 lg:rounded-[28px]"
                    :class="showCart ? 'max-lg:flex' : 'max-lg:hidden'"
                >
                    <div class="border-b border-slate-100 px-4 py-3 sm:px-5 sm:py-4">
                        <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-200 lg:hidden" />
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Pesanan</p>
                                <p class="text-xs text-slate-400">{{ itemCount }} item</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="openNewCustomer">
                                    Baru
                                </button>
                                <button
                                    type="button"
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 lg:hidden"
                                    aria-label="Tutup pesanan"
                                    @click="showCart = false"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="mt-3 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-left transition hover:border-teal-500 hover:bg-white"
                            @click="showCustomerPicker = true"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ customerDisplayName(selectedCustomer) }}</p>
                                    <p v-if="selectedCustomer && !selectedCustomer.is_walk_in" class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ selectedCustomer.phone || 'Tanpa telepon' }}
                                    </p>
                                    <p v-else class="mt-0.5 text-xs text-slate-400">Walk-in · tanpa poin</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p v-if="selectedCustomer && !selectedCustomer.is_walk_in" class="text-sm font-bold text-teal-700">
                                        {{ formatRupiah(selectedCustomer.points ?? 0) }}
                                    </p>
                                    <p class="text-[11px] font-medium text-slate-400">Ganti</p>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div class="pos-scroll min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-4">
                        <div
                            v-for="item in cart"
                            :key="`${item.product_id}-${item.product_variant_id || 0}`"
                            class="rounded-2xl bg-slate-50 p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ item.name }}</p>
                                    <p class="text-xs text-slate-400">{{ formatRupiah(item.selling_price) }} / pcs</p>
                                </div>
                                <button class="text-slate-400 hover:text-rose-500" @click="removeItem(item)">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 0 1 12h8l1-12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center rounded-xl bg-white p-1 shadow-sm">
                                    <button class="h-8 w-8 rounded-lg text-lg leading-none hover:bg-slate-100" @click="changeQty(item, -1)">-</button>
                                    <input v-model.number="item.quantity" type="number" min="1" class="w-10 bg-transparent text-center text-sm font-semibold outline-none">
                                    <button class="h-8 w-8 rounded-lg text-lg leading-none hover:bg-slate-100" @click="changeQty(item, 1)">+</button>
                                </div>
                                <p class="text-sm font-bold">{{ formatRupiah(lineTotal(item)) }}</p>
                            </div>
                            <button
                                v-if="settings.allow_discount"
                                class="mt-2 text-xs font-medium text-teal-700"
                                @click="discountItemId = discountItemId === item.product_id ? null : item.product_id"
                            >
                                {{ lineDiscount(item) ? `Diskon ${formatRupiah(lineDiscount(item))}` : 'Tambah diskon' }}
                            </button>
                            <div v-if="settings.allow_discount && discountItemId === item.product_id" class="mt-2 flex gap-2">
                                <select v-model="item.discount_type" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs">
                                    <option :value="null">Tanpa diskon</option>
                                    <option value="percent">Persen</option>
                                    <option value="fixed">Rupiah</option>
                                </select>
                                <input v-model.number="item.discount_value" type="number" min="0" class="w-24 rounded-lg border border-slate-200 px-2 py-1 text-xs">
                            </div>
                        </div>

                        <div v-if="!cart.length" class="flex h-full min-h-56 flex-col items-center justify-center text-center text-slate-400">
                            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13 5.4 5M7 13l-2 6h14M10 19a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" />
                                </svg>
                            </div>
                            <p class="font-medium text-slate-600">Keranjang masih kosong</p>
                            <p class="mt-1 max-w-40 text-xs">Scan barcode atau ketuk produk untuk mulai transaksi.</p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 px-5 py-4">
                        <div v-if="settings.allow_discount" class="mb-3 flex gap-2">
                            <select v-model="discountType" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                                <option :value="null">Diskon nota</option>
                                <option value="percent">Persen</option>
                                <option value="fixed">Rupiah</option>
                            </select>
                            <input v-model.number="discountValue" type="number" min="0" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs" placeholder="0">
                        </div>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ formatRupiah(subtotal) }}</span></div>
                            <div class="flex justify-between text-slate-500"><span>Diskon</span><span>{{ formatRupiah(transactionDiscount) }}</span></div>
                            <div class="flex justify-between text-slate-500"><span>Pajak</span><span>{{ formatRupiah(tax) }}</span></div>
                            <div
                                v-if="selectedCustomer && !selectedCustomer.is_walk_in"
                                class="flex justify-between text-teal-700"
                            >
                                <span>Saldo poin</span>
                                <span>{{ formatRupiah(selectedCustomer.points ?? 0) }}{{ earnedPoints > 0 ? ` · +${formatRupiah(earnedPoints)}` : '' }}</span>
                            </div>
                            <div class="flex items-end justify-between gap-3 pt-2">
                                <span class="shrink-0 text-sm font-medium">Total</span>
                                <span class="min-w-0 truncate text-right text-xl font-bold tracking-tight sm:text-2xl">{{ formatRupiah(grandTotal) }}</span>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-[1fr_1.6fr] gap-2">
                            <button
                                class="rounded-2xl border border-slate-200 py-3.5 text-sm font-semibold disabled:opacity-40"
                                :disabled="!cart.length"
                                @click="hold"
                            >
                                Tahan
                            </button>
                            <button
                                class="rounded-2xl bg-teal-600 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-600/20 disabled:opacity-40"
                                :disabled="!cart.length"
                                @click="openPay"
                            >
                                Bayar
                            </button>
                        </div>
                    </div>
                </aside>
            </div>

            <div v-if="showActions" class="fixed inset-0 z-10 lg:hidden" @click="showActions = false" />

            <div
                v-show="!showCart"
                class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white px-3 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden"
            >
                <div class="grid grid-cols-[1.35fr_1fr] gap-2">
                    <button
                        type="button"
                        class="flex min-w-0 items-center justify-between gap-2 rounded-2xl bg-slate-900 px-4 py-3.5 text-left text-white"
                        @click="showCart = true"
                    >
                        <span class="text-sm font-medium">{{ itemCount }} item</span>
                        <span class="truncate text-sm font-bold tabular-nums">{{ formatRupiah(grandTotal) }}</span>
                    </button>
                    <button
                        type="button"
                        class="rounded-2xl bg-teal-600 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-600/20 disabled:opacity-40"
                        :disabled="!cart.length"
                        @click="openPay"
                    >
                        Bayar
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showPay" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4">
            <form class="pos-scroll max-h-[92dvh] w-full max-w-lg overflow-y-auto rounded-t-[28px] bg-white p-5 shadow-2xl sm:rounded-[28px] sm:p-6" @submit.prevent="checkout">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Total tagihan</p>
                        <p class="mt-1 break-words text-2xl font-bold tracking-tight sm:text-3xl">{{ formatRupiah(payableTotal) }}</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ customerDisplayName(selectedCustomer) }}</p>
                        <p v-if="selectedCustomer && !selectedCustomer.is_walk_in" class="text-xs text-slate-500">
                            {{ selectedCustomer.phone || 'Tanpa telepon' }} · saldo {{ formatRupiah(selectedCustomer.points ?? 0) }}
                        </p>
                        <p v-if="Number(payForm.redeem_points) > 0" class="mt-1 text-xs text-teal-700">
                            Pakai poin {{ formatRupiah(payForm.redeem_points) }}
                        </p>
                    </div>
                    <button type="button" class="rounded-full p-2 text-slate-400 hover:bg-slate-100" @click="showPay = false">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                <div v-if="payableTotal > 0" class="mt-5 grid grid-cols-2 gap-2">
                    <button
                        v-for="method in paymentMethods"
                        :key="method.value"
                        type="button"
                        class="rounded-2xl border px-4 py-3 text-left transition"
                        :class="payForm.method === method.value ? 'border-teal-600 bg-teal-50 ring-4 ring-teal-600/10' : 'border-slate-200 hover:bg-slate-50'"
                        @click="payForm.method = method.value"
                    >
                        <p class="text-sm font-semibold">{{ paymentMeta[method.value]?.label ?? method.label }}</p>
                        <p class="text-xs text-slate-500">{{ paymentMeta[method.value]?.hint }}</p>
                    </button>
                </div>
                <div v-else class="mt-5 rounded-2xl bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">
                    Lunas dengan poin. Tidak perlu bayar tunai.
                </div>

                <div v-if="payForm.method === 'cash' && payableTotal > 0" class="mt-5">
                    <label class="text-sm font-medium">Uang diterima</label>
                    <input v-model="payForm.tendered" type="number" min="0" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-lg font-semibold outline-none focus:border-teal-600">
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="amount in quickTenders"
                            :key="amount"
                            type="button"
                            class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium hover:border-teal-600 hover:text-teal-700"
                            @click="payForm.tendered = amount"
                        >
                            {{ amount === Math.ceil(payableTotal) ? 'Uang pas' : formatRupiah(amount) }}
                        </button>
                    </div>
                    <div class="mt-4 flex items-center justify-between rounded-2xl bg-teal-50 px-4 py-3">
                        <span class="text-sm text-teal-800">Kembalian</span>
                        <span class="text-lg font-bold text-teal-800">{{ formatRupiah(change) }}</span>
                    </div>
                </div>
                <div v-else class="mt-5 space-y-3">
                    <label class="text-sm font-medium">No. referensi / label</label>
                    <input v-model="payForm.reference_number" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 outline-none focus:border-teal-600" placeholder="Opsional">
                    <input v-if="payForm.method === 'ewallet' || payForm.method === 'other'" v-model="payForm.label" class="h-12 w-full rounded-2xl border border-slate-200 px-4 outline-none focus:border-teal-600" placeholder="Contoh: OVO / Piutang">
                </div>

                <div class="mt-5 grid gap-3">
                    <input v-model="payForm.voucher_code" placeholder="Kode voucher" class="h-11 w-full rounded-2xl border border-slate-200 px-4 text-sm uppercase">
                    <div
                        v-if="props.settings.loyalty_enabled && selectedCustomer && !selectedCustomer.is_walk_in"
                        class="rounded-2xl border border-teal-100 bg-teal-50 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-teal-900">Bayar pakai poin</p>
                                <p class="mt-0.5 text-xs text-teal-800">1 poin = Rp 1 · saldo {{ formatRupiah(selectedCustomer.points ?? 0) }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="rounded-full bg-white px-3 py-1 text-xs font-medium text-teal-800" @click="useAllPoints">Pakai semua</button>
                                <button v-if="Number(payForm.redeem_points) > 0" type="button" class="rounded-full px-3 py-1 text-xs font-medium text-teal-700" @click="clearPoints">Hapus</button>
                            </div>
                        </div>
                        <input
                            v-model="payForm.redeem_points"
                            type="number"
                            min="0"
                            :max="maxRedeemablePoints"
                            class="mt-3 h-11 w-full rounded-2xl border border-teal-200 bg-white px-4 text-sm outline-none focus:border-teal-600"
                            placeholder="Nominal poin (Rp)"
                            @input="capRedeemPoints"
                        >
                        <p class="mt-2 text-xs text-teal-800">
                            Sisa dibayar {{ formatRupiah(payableTotal) }}
                            <span v-if="earnedPoints > 0"> · transaksi ini menambah {{ formatRupiah(earnedPoints) }} poin</span>
                        </p>
                    </div>
                    <button type="button" class="text-left text-sm font-medium text-teal-700" @click="addSplit">+ Split payment</button>
                    <div v-for="(row, index) in extraPayments" :key="index" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_110px_auto]">
                        <select v-model="row.method" class="h-11 rounded-2xl border border-slate-200 px-3 text-sm">
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                        </select>
                        <input v-model="row.amount" type="number" min="0" class="h-11 rounded-2xl border border-slate-200 px-3 text-sm" placeholder="Nominal">
                        <button type="button" class="text-xs text-rose-600" @click="extraPayments.splice(index, 1)">Hapus</button>
                    </div>
                </div>

                <p v-for="error in Object.values(payForm.errors)" :key="error" class="mt-3 text-sm text-rose-600">{{ error }}</p>

                <button
                    class="mt-5 w-full rounded-2xl bg-teal-600 py-4 text-sm font-semibold text-white disabled:opacity-50"
                    :disabled="payForm.processing"
                >
                    Konfirmasi pembayaran
                </button>
            </form>
        </div>

        <div v-if="showHold" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4">
            <div class="max-h-[92dvh] w-full max-w-lg overflow-y-auto rounded-t-[28px] bg-white p-5 sm:rounded-[28px] sm:p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Transaksi tertahan</h2>
                    <button class="text-slate-400" @click="showHold = false">Tutup</button>
                </div>
                <div class="mt-4 space-y-2">
                    <div
                        v-for="sale in heldSales"
                        :key="sale.id"
                        class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <button class="text-left" @click="resume(sale)">
                            <p class="text-sm font-semibold">{{ sale.customer?.is_walk_in ? 'Pelanggan umum' : sale.customer?.name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ formatRupiah(sale.grand_total) }}
                                <span v-if="sale.customer && !sale.customer.is_walk_in">
                                    · {{ sale.customer.phone || 'Tanpa telepon' }} · {{ formatRupiah(sale.customer.points ?? 0) }}
                                </span>
                            </p>
                        </button>
                        <div class="flex gap-2">
                            <button class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white" @click="resume(sale)">Lanjut</button>
                            <button class="rounded-xl px-3 py-1.5 text-xs text-rose-600" @click="discardHold(sale)">Hapus</button>
                        </div>
                    </div>
                    <p v-if="!heldSales.length" class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        Tidak ada transaksi yang ditahan.
                    </p>
                </div>
            </div>
        </div>

        <div v-if="showCustomerPicker" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4">
            <div class="flex max-h-[92dvh] w-full max-w-md flex-col rounded-t-[28px] bg-white p-5 sm:rounded-[28px] sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Pilih pelanggan</h2>
                        <p class="mt-1 text-xs text-slate-500">Cari member dari nama atau nomor telepon.</p>
                    </div>
                    <button type="button" class="rounded-full p-2 text-slate-400 hover:bg-slate-100" @click="showCustomerPicker = false">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>
                <input
                    ref="customerPickerInput"
                    v-model="customerQuery"
                    class="mt-4 h-12 w-full rounded-2xl border border-slate-200 px-4 outline-none focus:border-teal-600"
                    placeholder="Nama atau nomor telepon"
                >
                <div class="mt-3 min-h-0 flex-1 space-y-2 overflow-y-auto">
                    <button
                        v-for="customer in customerResults"
                        :key="customer.id"
                        type="button"
                        class="w-full rounded-2xl border px-4 py-3 text-left transition"
                        :class="customer.id === customerId
                            ? 'border-teal-600 bg-teal-50 ring-4 ring-teal-600/10'
                            : 'border-slate-100 bg-slate-50 hover:border-teal-500 hover:bg-white'"
                        @click="chooseCustomer(customer)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ customerDisplayName(customer) }}</p>
                                <p v-if="!customer.is_walk_in" class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ customer.phone || 'Tanpa telepon' }}
                                </p>
                                <p v-else class="mt-0.5 text-xs text-slate-400">Walk-in · tanpa poin</p>
                            </div>
                            <p v-if="!customer.is_walk_in" class="shrink-0 text-sm font-bold text-teal-700">
                                {{ formatRupiah(customer.points ?? 0) }}
                            </p>
                        </div>
                    </button>
                    <p v-if="!customerResults.length" class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        Member tidak ditemukan.
                    </p>
                </div>
                <button type="button" class="mt-4 text-left text-sm font-medium text-teal-700" @click="openNewCustomer">
                    + Tambah member baru
                </button>
            </div>
        </div>

        <div v-if="showKas" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4">
            <form class="w-full max-w-md rounded-t-[28px] bg-white p-5 sm:rounded-[28px] sm:p-6" @submit.prevent="submitKas">
                <h2 class="text-lg font-semibold">Kas masuk / keluar</h2>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="button" class="rounded-2xl border py-3 text-sm font-medium" :class="kasForm.type === 'in' ? 'border-teal-600 bg-teal-50' : 'border-slate-200'" @click="kasForm.type = 'in'">Kas masuk</button>
                    <button type="button" class="rounded-2xl border py-3 text-sm font-medium" :class="kasForm.type === 'out' ? 'border-teal-600 bg-teal-50' : 'border-slate-200'" @click="kasForm.type = 'out'">Kas keluar</button>
                </div>
                <input v-model="kasForm.amount" type="number" min="1" placeholder="Nominal" class="mt-4 h-12 w-full rounded-2xl border border-slate-200 px-4" required>
                <input v-model="kasForm.reason" placeholder="Alasan" class="mt-3 h-12 w-full rounded-2xl border border-slate-200 px-4" required>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm" @click="showKas = false">Batal</button>
                    <button class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                </div>
            </form>
        </div>

        <div v-if="showCustomer" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4">
            <form class="w-full max-w-md rounded-t-[28px] bg-white p-5 sm:rounded-[28px] sm:p-6" @submit.prevent="addCustomer">
                <h2 class="text-lg font-semibold">Pelanggan baru</h2>
                <input v-model="customerForm.name" placeholder="Nama" class="mt-4 h-12 w-full rounded-2xl border border-slate-200 px-4" required>
                <input v-model="customerForm.phone" placeholder="Telepon" class="mt-3 h-12 w-full rounded-2xl border border-slate-200 px-4">
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm" @click="showCustomer = false">Batal</button>
                    <button class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                </div>
            </form>
        </div>
    </PosLayout>
</template>
