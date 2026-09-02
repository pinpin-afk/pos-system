<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path
            v-if="name === 'dashboard'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"
        />
        <path
            v-else-if="name === 'pos'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 7h16M7 7V5h10v2M6 7l1.2 12h9.6L18 7"
        />
        <path
            v-else-if="name === 'products'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M21 8v8l-9 5-9-5V8l9-5 9 5Zm-9 5 9-5M12 13 3 8m9 5v8"
        />
        <path
            v-else-if="name === 'categories'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 6h7v7H4zM13 6h7v4h-7zM13 12h7v6h-7zM4 15h7v3H4z"
        />
        <path
            v-else-if="name === 'stock'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 19V8l8-4 8 4v11M4 8l8 4 8-4M12 12v11"
        />
        <path
            v-else-if="name === 'customers'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M11.5 8.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0ZM20 19v-1a3.5 3.5 0 0 0-2.5-3.35M16.5 5.2a3 3 0 0 1 0 5.6"
        />
        <path
            v-else-if="name === 'sales'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M7 7h13l-1.5 9H8L6 4H3M9 20a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm8 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"
        />
        <path
            v-else-if="name === 'reports'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 19V5h12v14zM8 9h4M8 13h6M16 8h4v11h-4"
        />
        <path
            v-else-if="name === 'shifts'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
        <path
            v-else-if="name === 'users'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M16 19v-1a4 4 0 0 0-8 0v1M12 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"
        />
        <path
            v-else-if="name === 'brands'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M7 4h10l1 6H6zM6 10h12v10H6zM9 14h6"
        />
        <path
            v-else-if="name === 'opname'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 5H5v14h14v-4M14 5h5v5M9 15l3-3 2 2 5-5"
        />
        <path
            v-else-if="name === 'suppliers'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 21V8l9-5 9 5v13M8 21v-7h8v7"
        />
        <path
            v-else-if="name === 'purchases'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 7h16v12H4zM8 7V5h8v2M8 12h8M8 16h5"
        />
        <path
            v-else-if="name === 'returns'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 12a8 8 0 1 0 3-6.3M4 4v6h6"
        />
        <path
            v-else-if="name === 'imports'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 4v10m0 0 4-4m-4 4-4-4M5 18h14"
        />
        <path
            v-else-if="name === 'labels'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 8h3v8H4zM9 8h2v8H9zM13 8h1v8h-1zM16 8h4v8h-4z"
        />
        <path
            v-else-if="name === 'settings'"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7ZM19.4 13a7.8 7.8 0 0 0 .1-2l2-1.5-2-3.5-2.4 1a7.6 7.6 0 0 0-1.7-1L15 4h-4l-.4 2.5a7.6 7.6 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a7.8 7.8 0 0 0 .1 2l-2 1.5 2 3.5 2.4-1a7.6 7.6 0 0 0 1.7 1L11 22h4l.4-2.5a7.6 7.6 0 0 0 1.7-1l2.4 1 2-3.5z"
        />
    </svg>
</template>
