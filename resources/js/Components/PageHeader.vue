<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
