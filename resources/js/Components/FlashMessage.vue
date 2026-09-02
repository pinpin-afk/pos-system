<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
</script>

<template>
    <div v-if="success || error" class="pointer-events-none fixed inset-x-0 top-5 z-50 flex justify-center px-4">
        <div
            class="pointer-events-auto rounded-2xl px-4 py-3 text-sm font-medium shadow-lg"
            :class="success ? 'bg-teal-700 text-white' : 'bg-rose-600 text-white'"
        >
            {{ success || error }}
        </div>
    </div>
</template>
