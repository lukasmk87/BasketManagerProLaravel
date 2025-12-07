<script setup>
defineProps({
    size: {
        type: [Number, String],
        default: 32
    }
})
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 48 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <!-- Orange Ball -->
        <circle cx="24" cy="24" r="22" fill="#f97316"/>

        <!-- Horizontale Linie -->
        <line x1="4" y1="24" x2="44" y2="24" stroke="#333" stroke-width="1.5"/>

        <!-- Vertikale Linie -->
        <line x1="24" y1="4" x2="24" y2="44" stroke="#333" stroke-width="1.5"/>

        <!-- Linke gebogene Linie -->
        <path d="M 12 6 Q 6 24 12 42" stroke="#333" stroke-width="1.5" fill="none"/>

        <!-- Rechte gebogene Linie -->
        <path d="M 36 6 Q 42 24 36 42" stroke="#333" stroke-width="1.5" fill="none"/>
    </svg>
</template>
