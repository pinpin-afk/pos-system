<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    customer: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: props.customer?.name ?? '',
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
    address: props.customer?.address ?? '',
    birthday: props.customer?.birthday ?? '',
    member_number: props.customer?.member_number ?? '',
});

function submit() {
    const options = { onSuccess: () => emit('close') };

    if (props.customer) {
        form.put(`/customers/${props.customer.id}`, options);

        return;
    }

    form.post('/customers', options);
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <input v-model="form.name" placeholder="Nama" class="input-soft w-full" required>
        <input v-model="form.phone" placeholder="Telepon" class="input-soft w-full">
        <input v-model="form.email" type="email" placeholder="Email" class="input-soft w-full">
        <textarea v-model="form.address" placeholder="Alamat" class="input-soft w-full" rows="3" />
        <input v-model="form.birthday" type="date" class="input-soft w-full">
        <input v-model="form.member_number" placeholder="Nomor member (otomatis jika kosong)" class="input-soft w-full">
        <p v-for="error in Object.values(form.errors)" :key="error" class="text-sm text-rose-600">{{ error }}</p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="btn-secondary" @click="emit('close')">Batal</button>
            <button class="btn-primary" :disabled="form.processing">Simpan</button>
        </div>
    </form>
</template>
