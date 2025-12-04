<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ label }}
        </label>
        <select
            :id="id"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            :disabled="loading"
        >
            <option value="">{{ placeholder }}</option>
            <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
            >
                {{ category.name }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'both', // 'play', 'drill', or 'both'
        validator: (value) => ['play', 'drill', 'both'].includes(value),
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Kategorie wählen...',
    },
    id: {
        type: String,
        default: 'category-selector',
    },
    error: {
        type: String,
        default: '',
    },
    // Optional: Pass categories directly instead of fetching
    providedCategories: {
        type: Array,
        default: null,
    },
});

defineEmits(['update:modelValue']);

const categories = ref([]);
const loading = ref(false);

const fetchCategories = async () => {
    if (props.providedCategories) {
        categories.value = props.providedCategories;
        return;
    }

    loading.value = true;
    try {
        let endpoint = '/api/tactic-categories';
        if (props.type === 'play') {
            endpoint = '/api/tactic-categories/plays';
        } else if (props.type === 'drill') {
            endpoint = '/api/tactic-categories/drills';
        }

        const response = await axios.get(endpoint);
        categories.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch categories:', error);
        categories.value = [];
    } finally {
        loading.value = false;
    }
};

watch(() => props.type, () => {
    fetchCategories();
});

watch(() => props.providedCategories, (newCategories) => {
    if (newCategories) {
        categories.value = newCategories;
    }
}, { immediate: true });

onMounted(() => {
    if (!props.providedCategories) {
        fetchCategories();
    }
});
</script>
