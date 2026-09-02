<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        default: '',
    },
    src: {
        type: String,
        default: null,
    },
});

const palettes = [
    'bg-teal-100 text-teal-800',
    'bg-sky-100 text-sky-800',
    'bg-violet-100 text-violet-800',
    'bg-amber-100 text-amber-800',
    'bg-rose-100 text-rose-800',
    'bg-orange-100 text-orange-800',
];

const initial = computed(() => String(props.name || '?').trim().charAt(0).toUpperCase());

const tone = computed(() => {
    const total = [...String(props.name || '')].reduce((sum, char) => sum + char.charCodeAt(0), 0);

    return palettes[total % palettes.length];
});
</script>

<template>
    <img
        v-if="src"
        :src="src"
        :alt="name"
        class="h-10 w-10 shrink-0 rounded-2xl object-cover"
    >
    <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-xs font-bold" :class="tone">
        {{ initial }}
    </div>
</template>
