<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    absence: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    type: 'vacation',
    start_date: '',
    end_date: '',
    reason: '',
    notes: '',
});

const isEditMode = computed(() => props.absence !== null);

const modalTitle = computed(() => {
    return isEditMode.value ? 'Abwesenheit bearbeiten' : 'Abwesenheit eintragen';
});

const absenceTypes = [
    { value: 'vacation', label: 'Urlaub', icon: 'sun' },
    { value: 'illness', label: 'Krankheit', icon: 'thermometer' },
    { value: 'injury', label: 'Verletzung', icon: 'bandage' },
    { value: 'personal', label: 'Persönlich', icon: 'user' },
    { value: 'other', label: 'Sonstiges', icon: 'info' },
];

const minDate = computed(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
});

const minEndDate = computed(() => {
    return form.value.start_date || minDate.value;
});

// Watch for changes in absence prop (edit mode)
watch(() => props.absence, (newVal) => {
    if (newVal) {
        form.value = {
            type: newVal.type || 'vacation',
            start_date: newVal.start_date || '',
            end_date: newVal.end_date || '',
            reason: newVal.reason || '',
            notes: newVal.notes || '',
        };
    } else {
        resetForm();
    }
}, { immediate: true });

// Watch for modal open
watch(() => props.show, (newVal) => {
    if (newVal && !props.absence) {
        resetForm();
    }
});

const resetForm = () => {
    form.value = {
        type: 'vacation',
        start_date: '',
        end_date: '',
        reason: '',
        notes: '',
    };
};

const handleSubmit = () => {
    emit('submit', {
        ...form.value,
        id: props.absence?.id,
    });
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="handleClose"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md transform transition-all">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-900">{{ modalTitle }}</h3>
                            <button
                                @click="handleClose"
                                class="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="handleSubmit" class="p-4 space-y-4">
                            <!-- Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Art der Abwesenheit</label>
                                <div class="grid grid-cols-5 gap-2">
                                    <button
                                        v-for="type in absenceTypes"
                                        :key="type.value"
                                        type="button"
                                        @click="form.type = type.value"
                                        class="flex flex-col items-center p-2 rounded-lg border-2 transition-all"
                                        :class="form.type === type.value
                                            ? 'border-orange-500 bg-orange-50 text-orange-700'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                    >
                                        <svg v-if="type.icon === 'sun'" class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <svg v-else-if="type.icon === 'thermometer'" class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        <svg v-else-if="type.icon === 'bandage'" class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                        <svg v-else-if="type.icon === 'user'" class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                        <svg v-else class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-xs">{{ type.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Date Range -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Von</label>
                                    <input
                                        id="start_date"
                                        type="date"
                                        v-model="form.start_date"
                                        :min="minDate"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                    />
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Bis</label>
                                    <input
                                        id="end_date"
                                        type="date"
                                        v-model="form.end_date"
                                        :min="minEndDate"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                    />
                                </div>
                            </div>

                            <!-- Reason -->
                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                                    Grund <span class="text-gray-400">(sichtbar für Trainer)</span>
                                </label>
                                <input
                                    id="reason"
                                    type="text"
                                    v-model="form.reason"
                                    maxlength="255"
                                    placeholder="z.B. Familienurlaub, Arzttermin..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                />
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                    Notizen <span class="text-gray-400">(optional)</span>
                                </label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="2"
                                    maxlength="1000"
                                    placeholder="Weitere Details..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                ></textarea>
                            </div>
                        </form>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 p-4 border-t bg-gray-50 rounded-b-lg">
                            <button
                                type="button"
                                @click="handleClose"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                Abbrechen
                            </button>
                            <button
                                type="button"
                                @click="handleSubmit"
                                :disabled="loading || !form.start_date || !form.end_date"
                                class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span v-if="loading" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Speichern...
                                </span>
                                <span v-else>{{ isEditMode ? 'Aktualisieren' : 'Speichern' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
