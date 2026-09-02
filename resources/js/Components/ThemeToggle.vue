<script setup>
import { useTheme } from '@/composables/useTheme';

const { isDark, toggle } = useTheme();
</script>

<template>
    <button
        type="button"
        class="flex w-full items-center justify-between rounded-2xl px-3 py-2 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
        :aria-pressed="isDark"
        @click="toggle"
    >
        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
            {{ isDark ? 'Tema gelap' : 'Tema terang' }}
        </span>
        <span
            class="relative h-6 w-11 rounded-full transition"
            :class="isDark ? 'bg-teal-500' : 'bg-slate-200'"
        >
            <span
                class="absolute top-0.5 left-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm transition"
                :class="isDark ? 'translate-x-5 text-teal-700' : ''"
            >
                <svg v-if="isDark" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z" />
                </svg>
                <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36 6.36-1.42-1.42M7.05 7.05 5.64 5.64m12.72 0-1.41 1.41M7.05 16.95l-1.41 1.41M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" />
                </svg>
            </span>
        </span>
    </button>
</template>
