<script setup>
import { useForm } from '@inertiajs/vue3';
import { roleLabel } from '@/utils/labels';

const props = defineProps({
    userModel: { type: Object, default: null },
    roles: Array,
    branches: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

function roleValue(role) {
    return typeof role === 'object' ? role.value : role;
}

const form = useForm({
    name: props.userModel?.name ?? '',
    email: props.userModel?.email ?? '',
    password: '',
    role: props.userModel?.role ?? 'cashier',
    is_active: props.userModel?.is_active ?? true,
    pin: '',
    card_number: props.userModel?.card_number ?? '',
    branch_id: props.userModel?.branch_id ?? '',
});

function submit() {
    const options = { onSuccess: () => emit('close') };

    if (props.userModel) {
        form.put(`/users/${props.userModel.id}`, options);

        return;
    }

    form.post('/users', options);
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <input v-model="form.name" placeholder="Nama" class="input-soft w-full" required>
        <input v-model="form.email" type="email" placeholder="Email" class="input-soft w-full" required>
        <input
            v-model="form.password"
            type="password"
            :placeholder="userModel ? 'Password baru (opsional)' : 'Password'"
            class="input-soft w-full"
            :required="!userModel"
        >
        <select v-model="form.role" class="input-soft w-full">
            <option v-for="role in roles" :key="roleValue(role)" :value="roleValue(role)">
                {{ roleLabel(roleValue(role)) }}
            </option>
        </select>
        <select v-model="form.branch_id" class="input-soft w-full">
            <option value="">Cabang</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
        </select>
        <input v-model="form.pin" placeholder="PIN kasir (4-6 digit, opsional)" class="input-soft w-full" inputmode="numeric">
        <input v-model="form.card_number" placeholder="Nomor kartu karyawan" class="input-soft w-full">
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
