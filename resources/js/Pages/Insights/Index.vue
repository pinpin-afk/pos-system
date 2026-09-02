<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    suggestions: Array,
});

function toneClass(tone) {
    return {
        amber: 'border-amber-200 bg-amber-50',
        sky: 'border-sky-200 bg-sky-50',
        rose: 'border-rose-200 bg-rose-50',
        teal: 'border-teal-200 bg-teal-50',
    }[tone] ?? 'border-slate-200 bg-white';
}
</script>

<template>
    <AdminLayout>
        <Head title="Insights" />
        <PageHeader title="Insights" subtitle="Saran restock, promo, dan biaya — berbasis aturan, bukan AI cloud." />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <article
                v-for="item in suggestions"
                :key="item.title"
                class="rounded-[28px] border p-5"
                :class="toneClass(item.tone)"
            >
                <h2 class="font-semibold">{{ item.title }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ item.detail }}</p>
            </article>
        </div>
    </AdminLayout>
</template>
