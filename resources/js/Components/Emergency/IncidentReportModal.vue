<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Notfall melden
            </h3>
            
            <form @submit.prevent="submitReport">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Notfalltyp
                        </label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                        >
                            <option value="">Typ auswählen</option>
                            <option value="medical">Medizinischer Notfall</option>
                            <option value="injury">Verletzung</option>
                            <option value="security">Sicherheit</option>
                            <option value="other">Sonstiges</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Beschreibung
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                        />
                    </div>
                </div>
                
                <div class="flex justify-end mt-6 space-x-3">
                    <SecondaryButton @click="closeModal">
                        Abbrechen
                    </SecondaryButton>
                    <DangerButton type="submit">
                        Notfall melden
                    </DangerButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref } from 'vue'
import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    show: Boolean,
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
    type: '',
    description: '',
})

const closeModal = () => {
    emit('close')
    form.value = { type: '', description: '' }
}

const submitReport = () => {
    emit('submit', form.value)
    closeModal()
}
</script>