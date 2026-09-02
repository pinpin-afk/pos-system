<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    brand: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: props.brand?.name ?? '',
    is_active: props.brand?.is_active ?? true,
});

function submit() {
    const options = { onSuccess: () => emit('close') };

    if (props.brand) {
        form.put(`/brands/${props.brand.id}`, options);
        return;
    }

    form.post('/brands', options);
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <input v-model="form.name" placeholder="Nama merek" class="input-soft w-full" required>
        <label class="flex items-center gap-2 text-sm">
            <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600">
            Aktif
        </label>
        <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="btn-secondary" @click="emit('close')">Batal</button>
            <button class="btn-primary" :disabled="form.processing">Simpan</button>
        </div>
    </form>
</template>
