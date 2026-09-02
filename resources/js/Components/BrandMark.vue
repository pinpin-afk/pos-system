<script setup>
defineProps({
    src: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const sizes = {
    sm: 'h-8 w-14',
    md: 'h-10 w-16',
    lg: 'h-14 w-28',
    xl: 'h-16 w-40',
};
</script>

<template>
    <img
        :src="src ? `/storage/${src}` : '/images/pospin-logo.jpg'"
        alt="POSPIN"
        class="shrink-0 object-contain"
        :class="sizes[size] ?? sizes.md"
    >
</template>
