<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    wide: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

function onKeydown(event) {
    if (event.key === 'Escape' && props.show) {
        emit('close');
    }
}

watch(() => props.show, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
});

onMounted(() => {
    window.addEventListener('keydown', onKeydown);

    if (props.show) {
        document.body.classList.add('overflow-hidden');
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center"
        >
            <button type="button" class="absolute inset-0 cursor-default" aria-label="Tutup" @click="emit('close')" />
            <div
                class="relative z-10 my-6 w-full rounded-[28px] bg-white shadow-2xl dark:bg-[#121a2b] dark:text-slate-100"
                :class="wide ? 'max-w-3xl' : 'max-w-lg'"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight">{{ title }}</h2>
                        <p v-if="subtitle" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 dark:hover:bg-white/5 dark:hover:text-white"
                        @click="emit('close')"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <slot />
                </div>
            </div>
        </div>
    </Teleport>
</template>
