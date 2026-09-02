<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    supplier: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: props.supplier?.name ?? '',
    phone: props.supplier?.phone ?? '',
    email: props.supplier?.email ?? '',
    address: props.supplier?.address ?? '',
    is_active: props.supplier?.is_active ?? true,
});

function submit() {
    const options = { onSuccess: () => emit('close') };

    if (props.supplier) {
        form.put(`/suppliers/${props.supplier.id}`, options);
        return;
    }

    form.post('/suppliers', options);
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <input v-model="form.name" placeholder="Nama supplier" class="input-soft w-full" required>
        <input v-model="form.phone" placeholder="Telepon" class="input-soft w-full">
        <input v-model="form.email" type="email" placeholder="Email" class="input-soft w-full">
        <textarea v-model="form.address" placeholder="Alamat" class="input-soft w-full" rows="3" />
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
