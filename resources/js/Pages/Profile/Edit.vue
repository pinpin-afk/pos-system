<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    twoFactorEnabled: { type: Boolean, default: false },
    twoFactorPending: { type: Boolean, default: false },
    qrSvg: { type: String, default: null },
    recoveryCodes: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const twoFactorForm = useForm({
    code: '',
});

function submit() {
    form.put('/profile/password', {
        onSuccess: () => form.reset(),
    });
}

function enableTwoFactor() {
    router.post('/profile/two-factor');
}

function confirmTwoFactor() {
    twoFactorForm.post('/profile/two-factor/confirm');
}

function disableTwoFactor() {
    router.delete('/profile/two-factor');
}
</script>

<template>
    <AdminLayout>
        <Head title="Profil" />
        <PageHeader title="Profil" subtitle="Ganti password akun yang sedang masuk." />

        <section class="admin-card mt-6 max-w-xl space-y-4 p-6">
            <div>
                <p class="text-sm text-slate-500">Nama</p>
                <p class="mt-1 font-semibold">{{ user?.name }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Email</p>
                <p class="mt-1 font-semibold">{{ user?.email }}</p>
            </div>
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium">Password saat ini</label>
                    <input v-model="form.current_password" type="password" class="input-soft mt-1 w-full" required>
                    <p v-if="form.errors.current_password" class="mt-1 text-sm text-rose-600">{{ form.errors.current_password }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium">Password baru</label>
                    <input v-model="form.password" type="password" class="input-soft mt-1 w-full" required>
                    <p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium">Ulangi password baru</label>
                    <input v-model="form.password_confirmation" type="password" class="input-soft mt-1 w-full" required>
                </div>
                <button class="btn-primary" :disabled="form.processing">Ganti password</button>
            </form>
        </section>

        <section class="admin-card mt-6 max-w-xl space-y-4 p-6">
            <h2 class="font-semibold">Autentikasi dua faktor</h2>
            <p class="text-sm text-slate-500">Lindungi akun admin dengan OTP dari aplikasi authenticator.</p>
            <div v-if="qrSvg" class="overflow-hidden rounded-2xl bg-white p-4" v-html="qrSvg" />
            <div v-if="recoveryCodes.length" class="rounded-2xl bg-slate-50 p-4 text-sm">
                <p class="font-medium">Recovery codes</p>
                <p v-for="code in recoveryCodes" :key="code" class="font-mono">{{ code }}</p>
            </div>
            <form v-if="twoFactorPending" class="space-y-3" @submit.prevent="confirmTwoFactor">
                <input v-model="twoFactorForm.code" placeholder="Kode OTP" class="input-soft w-full">
                <p v-if="twoFactorForm.errors.code" class="text-sm text-rose-600">{{ twoFactorForm.errors.code }}</p>
                <button class="btn-primary" :disabled="twoFactorForm.processing">Konfirmasi 2FA</button>
            </form>
            <button v-else-if="!twoFactorEnabled" class="btn-primary" @click="enableTwoFactor">Aktifkan 2FA</button>
            <button v-else class="btn-danger" @click="disableTwoFactor">Matikan 2FA</button>
        </section>
    </AdminLayout>
</template>
