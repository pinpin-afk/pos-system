<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="min-w-9 rounded-xl px-3 py-1.5 text-center text-xs font-semibold"
                :class="link.active ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5'"
                v-html="link.label"
            />
            <span
                v-else
                class="min-w-9 rounded-xl px-3 py-1.5 text-center text-xs font-semibold text-slate-300"
                v-html="link.label"
            />
        </template>
    </div>
</template>
