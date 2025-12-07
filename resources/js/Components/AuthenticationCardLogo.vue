<script setup>
import { Link } from '@inertiajs/vue3';
import BasketballLogo from '@/Components/BasketballLogo.vue';
</script>

<template>
    <Link :href="'/'">
        <BasketballLogo :size="64" />
    </Link>
</template>
