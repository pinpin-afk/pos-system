<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BrandMark from '@/Components/BrandMark.vue';
</script>

<template>
    <div class="auth-shell flex min-h-screen items-center justify-center px-4">
        <Head title="Masuk" />
        <div class="w-full max-w-2xl">
            <div class="text-center">
                <BrandMark size="xl" class="mx-auto" />
                <h1 class="mt-4 text-3xl font-semibold tracking-tight">Pilih halaman masuk</h1>
                <p class="auth-muted mt-2 text-sm">Admin dan kasir punya login sendiri.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <Link href="/login" class="rounded-[28px] bg-white p-6 shadow-[0_16px_40px_rgb(15,23,42,0.04)] transition hover:-translate-y-0.5">
                    <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Admin</p>
                    <h2 class="mt-2 text-xl font-semibold">Masuk Admin</h2>
                    <p class="auth-muted mt-2 text-sm">Dashboard, produk, stok, dan laporan.</p>
                </Link>
                <Link href="/kasir/login" class="rounded-[28px] bg-white p-6 shadow-[0_16px_40px_rgb(15,23,42,0.04)] transition hover:-translate-y-0.5">
                    <p class="auth-eyebrow text-xs font-semibold uppercase tracking-[0.14em]">Kasir</p>
                    <h2 class="mt-2 text-xl font-semibold">Masuk Kasir</h2>
                    <p class="auth-muted mt-2 text-sm">Buka shift dan mulai transaksi.</p>
                </Link>
            </div>
        </div>
    </div>
</template>
