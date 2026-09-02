<script setup>
defineProps({
    empty: {
        type: String,
        default: 'Belum ada data.',
    },
    isEmpty: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="admin-card overflow-hidden">
        <div v-if="$slots.toolbar" class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-white/10">
            <slot name="toolbar" />
        </div>

        <div v-if="!isEmpty" class="overflow-x-auto">
            <table class="admin-table" :class="{ 'admin-table-compact': compact }">
                <thead>
                    <slot name="head" />
                </thead>
                <tbody>
                    <slot />
                </tbody>
            </table>
        </div>

        <div v-else class="px-6 py-16 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 dark:bg-white/5">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h7" />
                </svg>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ empty }}</p>
        </div>

        <div v-if="$slots.footer && !isEmpty" class="border-t border-slate-100 px-5 py-3 dark:border-white/10">
            <slot name="footer" />
        </div>
    </div>
</template>
