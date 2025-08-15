<template>
    <div class="space-y-2">
        <!-- Tags Display -->
        <div v-if="modelValue && modelValue.length > 0" class="flex flex-wrap gap-2">
            <span 
                v-for="(tag, index) in modelValue" 
                :key="index"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800"
            >
                {{ tag }}
                <button 
                    @click="removeTag(index)"
                    type="button"
                    class="ml-2 text-indigo-600 hover:text-indigo-800 focus:outline-none"
                >
                    ×
                </button>
            </span>
        </div>
        
        <!-- Input Field -->
        <div class="relative">
            <input
                ref="input"
                v-model="inputValue"
                @keydown.enter.prevent="addTag"
                @keydown.backspace="handleBackspace"
                :placeholder="placeholder"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                :class="className"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Tag hinzufügen und Enter drücken...'
    },
    className: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const inputValue = ref('')
const input = ref(null)

const addTag = () => {
    const value = inputValue.value.trim()
    if (value && !props.modelValue.includes(value)) {
        const newTags = [...props.modelValue, value]
        emit('update:modelValue', newTags)
        inputValue.value = ''
    }
}

const removeTag = (index) => {
    const newTags = props.modelValue.filter((_, i) => i !== index)
    emit('update:modelValue', newTags)
}

const handleBackspace = () => {
    if (inputValue.value === '' && props.modelValue.length > 0) {
        removeTag(props.modelValue.length - 1)
    }
}
</script>