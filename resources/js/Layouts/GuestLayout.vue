<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
</script>

<template>
    <div class="auth-shell flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <BrandMark size="xl" />
            </div>
            <FlashMessage />
            <slot />
        </div>
    </div>
</template>
