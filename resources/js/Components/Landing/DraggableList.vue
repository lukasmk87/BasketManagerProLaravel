<script setup>
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    items: {
        type: Array,
        required: true
    },
    itemKey: {
        type: String,
        default: 'id'
    },
    addLabel: {
        type: String,
        default: 'Hinzufügen'
    },
    minItems: {
        type: Number,
        default: 0
    },
    maxItems: {
        type: Number,
        default: 20
    }
});

const emit = defineEmits(['update:items', 'add', 'remove']);

const localItems = computed({
    get: () => props.items,
    set: (value) => emit('update:items', value)
});

const canAdd = computed(() => props.items.length < props.maxItems);
const canRemove = (index) => props.items.length > props.minItems;

const addItem = () => {
    if (!canAdd.value) return;
    emit('add');
};

const removeItem = (index) => {
    if (!canRemove(index)) return;
    emit('remove', index);
};

const moveUp = (index) => {
    if (index === 0) return;
    const newItems = [...props.items];
    [newItems[index - 1], newItems[index]] = [newItems[index], newItems[index - 1]];
    emit('update:items', newItems);
};

const moveDown = (index) => {
    if (index === props.items.length - 1) return;
    const newItems = [...props.items];
    [newItems[index], newItems[index + 1]] = [newItems[index + 1], newItems[index]];
    emit('update:items', newItems);
};

const getItemKey = (item) => {
    // Versuche zuerst die konfigurierte Key-Property
    if (item && item[props.itemKey] !== undefined) {
        return item[props.itemKey];
    }
    // Fallback: Verwende Index aus dem Array
    const index = props.items.indexOf(item);
    return index >= 0 ? `item-${index}` : `item-${Date.now()}`;
};
</script>

<template>
    <div class="space-y-4">
        <draggable
            v-model="localItems"
            :animation="200"
            handle=".drag-handle"
            ghost-class="opacity-50"
            :item-key="getItemKey"
            class="space-y-3"
        >
            <template #item="{ element, index }">
                <div
                    class="group relative bg-white border-2 border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-all"
                >
                    <!-- Header with Drag Handle & Actions -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <!-- Drag Handle -->
                            <button
                                type="button"
                                class="drag-handle cursor-move p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition"
                                title="Ziehen zum Sortieren"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                </svg>
                            </button>

                            <!-- Item Number -->
                            <span class="text-sm font-medium text-gray-500">
                                #{{ index + 1 }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2">
                            <!-- Move Up -->
                            <button
                                v-if="index > 0"
                                type="button"
                                @click="moveUp(index)"
                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition"
                                title="Nach oben"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>

                            <!-- Move Down -->
                            <button
                                v-if="index < localItems.length - 1"
                                type="button"
                                @click="moveDown(index)"
                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition"
                                title="Nach unten"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Remove -->
                            <button
                                v-if="canRemove(index)"
                                type="button"
                                @click="removeItem(index)"
                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition"
                                title="Entfernen"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Slot for Item Content -->
                    <div class="pl-2">
                        <slot :item="element" :index="index"></slot>
                    </div>
                </div>
            </template>
        </draggable>

        <!-- Add Button -->
        <button
            v-if="canAdd"
            type="button"
            @click="addItem"
            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex items-center justify-center space-x-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>{{ addLabel }}</span>
        </button>

        <!-- Max Items Reached -->
        <div v-if="!canAdd" class="text-center py-2 text-sm text-gray-500">
            Maximum erreicht ({{ maxItems }} Items)
        </div>

        <!-- Info Text -->
        <p class="text-xs text-gray-500 text-center">
            {{ items.length }} {{ items.length === 1 ? 'Eintrag' : 'Einträge' }}
            {{ minItems > 0 ? `(Minimum: ${minItems})` : '' }}
        </p>
    </div>
</template>
