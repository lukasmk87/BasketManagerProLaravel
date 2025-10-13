<script setup>
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    modelValue: {
        type: Number,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    metric: {
        type: String,
        required: true,
    },
    unit: {
        type: String,
        default: '',
    },
    min: {
        type: Number,
        default: -1, // -1 = unlimited
    },
    max: {
        type: Number,
        default: 10000,
    },
    step: {
        type: Number,
        default: 1,
    },
    error: {
        type: String,
        default: null,
    },
    description: {
        type: String,
        default: null,
    },
    showSlider: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const isUnlimited = ref(props.modelValue === -1);
const internalValue = ref(props.modelValue === -1 ? 100 : props.modelValue);

// Sync with parent
watch(() => props.modelValue, (newValue) => {
    isUnlimited.value = newValue === -1;
    internalValue.value = newValue === -1 ? 100 : newValue;
});

// Emit changes
watch([isUnlimited, internalValue], ([unlimited, value]) => {
    if (unlimited) {
        emit('update:modelValue', -1);
    } else {
        emit('update:modelValue', parseInt(value) || 0);
    }
});

const sliderValue = computed({
    get() {
        return isUnlimited.value ? props.max : internalValue.value;
    },
    set(value) {
        internalValue.value = parseInt(value);
    }
});

const displayValue = computed(() => {
    if (isUnlimited.value) return 'Unbegrenzt';
    return `${internalValue.value.toLocaleString('de-DE')}${props.unit ? ' ' + props.unit : ''}`;
});

const sliderPercentage = computed(() => {
    if (isUnlimited.value) return 100;
    const range = props.max - Math.max(props.min, 0);
    const value = internalValue.value - Math.max(props.min, 0);
    return (value / range) * 100;
});

const sliderColor = computed(() => {
    const percentage = sliderPercentage.value;
    if (isUnlimited.value) return 'rgb(34, 197, 94)'; // green-500
    if (percentage >= 75) return 'rgb(239, 68, 68)'; // red-500
    if (percentage >= 50) return 'rgb(251, 146, 60)'; // orange-500
    return 'rgb(59, 130, 246)'; // blue-500
});
</script>

<template>
    <div class="space-y-3">
        <!-- Label and Checkbox -->
        <div class="flex items-center justify-between">
            <InputLabel :value="label" :for="metric" />
            <label class="flex items-center cursor-pointer">
                <input
                    v-model="isUnlimited"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-600">Unbegrenzt</span>
            </label>
        </div>

        <!-- Description -->
        <p v-if="description" class="text-xs text-gray-500">
            {{ description }}
        </p>

        <!-- Slider (wenn aktiviert und nicht unbegrenzt) -->
        <div v-if="showSlider && !isUnlimited" class="space-y-2">
            <div class="relative pt-1">
                <input
                    v-model="sliderValue"
                    type="range"
                    :min="Math.max(min, 0)"
                    :max="max"
                    :step="step"
                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                    :style="{
                        background: `linear-gradient(to right, ${sliderColor} 0%, ${sliderColor} ${sliderPercentage}%, #e5e7eb ${sliderPercentage}%, #e5e7eb 100%)`
                    }"
                />
            </div>
        </div>

        <!-- Input Field & Display -->
        <div class="flex items-center space-x-3">
            <div class="flex-1">
                <div class="relative rounded-md shadow-sm">
                    <TextInput
                        :id="metric"
                        v-model="internalValue"
                        type="number"
                        :min="Math.max(min, 0)"
                        :max="max"
                        :step="step"
                        :disabled="isUnlimited"
                        class="w-full"
                        :class="{ 'bg-gray-100 cursor-not-allowed': isUnlimited }"
                        :placeholder="isUnlimited ? 'Unbegrenzt' : 'Wert eingeben...'"
                    />
                    <div v-if="unit && !isUnlimited" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">{{ unit }}</span>
                    </div>
                </div>
            </div>

            <!-- Display Value -->
            <div class="flex-shrink-0 w-32">
                <div
                    class="px-3 py-2 text-center rounded-md font-medium text-sm"
                    :class="isUnlimited ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                >
                    {{ displayValue }}
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <InputError v-if="error" :message="error" />

        <!-- Quick Presets (optional) -->
        <div v-if="!isUnlimited && showSlider" class="flex items-center space-x-2">
            <span class="text-xs text-gray-500">Schnellauswahl:</span>
            <button
                v-for="preset in [10, 50, 100, 500, 1000]"
                :key="preset"
                type="button"
                class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                :class="{ 'bg-indigo-50 border-indigo-300': internalValue === preset }"
                @click="internalValue = preset"
            >
                {{ preset }}
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Custom slider styling for better visual feedback */
.slider::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    border: 2px solid v-bind(sliderColor);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.slider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    border: 2px solid v-bind(sliderColor);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.slider:focus::-webkit-slider-thumb {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.slider:focus::-moz-range-thumb {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}
</style>
