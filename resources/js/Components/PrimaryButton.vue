<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    as: {
        type: String,
        default: 'button',
    },
    href: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Link
        v-if="as === 'Link' && href"
        :href="href"
        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
        :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
        <slot />
    </Link>
    <a
        v-else-if="as === 'a' && href"
        :href="href"
        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
        :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
        <slot />
    </a>
    <button
        v-else
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
    >
        <slot />
    </button>
</template>
