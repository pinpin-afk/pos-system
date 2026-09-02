<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
</script>

<template>
    <div class="flex h-dvh max-h-dvh flex-col overflow-hidden bg-[#f3f5f8] text-slate-900">
        <div v-if="success || error" class="pointer-events-none absolute inset-x-0 top-5 z-40 flex justify-center px-4">
            <div
                class="pointer-events-auto rounded-2xl px-4 py-3 text-sm font-medium shadow-lg"
                :class="success ? 'bg-teal-700 text-white' : 'bg-rose-600 text-white'"
            >
                {{ success || error }}
            </div>
        </div>
        <div class="flex min-h-0 flex-1 flex-col">
            <slot />
        </div>
    </div>
</template>
