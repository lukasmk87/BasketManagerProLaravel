<script setup>
import { computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    modelValue: {
        type: Number,
        default: 0
    },
    label: {
        type: String,
        default: 'Bewertung'
    },
    max: {
        type: Number,
        default: 5
    }
});

const emit = defineEmits(['update:modelValue']);

const stars = computed(() => Array.from({ length: props.max }, (_, i) => i + 1));

const setRating = (rating) => {
    emit('update:modelValue', rating);
};
</script>

<template>
    <div class="space-y-2">
        <InputLabel v-if="label" :value="label" />

        <div class="flex items-center space-x-1">
            <button
                v-for="star in stars"
                :key="star"
                type="button"
                @click="setRating(star)"
                class="focus:outline-none focus:ring-2 focus:ring-yellow-400 rounded transition"
            >
                <svg
                    class="w-8 h-8 transition"
                    :class="star <= modelValue ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </button>

            <span class="ml-2 text-sm text-gray-600">{{ modelValue }} / {{ max }}</span>

            <button
                v-if="modelValue > 0"
                type="button"
                @click="setRating(0)"
                class="ml-3 text-xs text-red-600 hover:text-red-700 underline"
            >
                Zurücksetzen
            </button>
        </div>
    </div>
</template>
