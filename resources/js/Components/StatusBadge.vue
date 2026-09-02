<script setup>
defineProps({
    tone: {
        type: String,
        default: 'slate',
    },
});

const tones = {
    slate: 'bg-slate-100 text-slate-600',
    teal: 'bg-teal-50 text-teal-700',
    amber: 'bg-amber-50 text-amber-800',
    rose: 'bg-rose-50 text-rose-700',
    sky: 'bg-sky-50 text-sky-700',
    violet: 'bg-violet-50 text-violet-700',
    emerald: 'bg-emerald-50 text-emerald-800',
};
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="tones[tone] ?? tones.slate">
        <slot />
    </span>
</template>
