<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BasketballLogo from '@/Components/BasketballLogo.vue'

const page = usePage()

// Get club logo from current club context
const clubLogo = computed(() => page.props.currentClub?.logo_url)
const clubName = computed(() => page.props.currentClub?.name || page.props.appName || 'BasketManager Pro')
</script>

<template>
    <!-- Club Logo if available -->
    <img
        v-if="clubLogo"
        :src="clubLogo"
        :alt="clubName + ' Logo'"
        class="block h-9 w-auto"
    />
    <!-- Default Basketball Logo -->
    <BasketballLogo v-else :size="36" />
</template>
