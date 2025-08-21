<template>
    <AppLayout title="Neue Sporthalle">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Neue Sporthalle erstellen
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white overflow-hidden shadow-xl rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Grunddaten der Sporthalle
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Geben Sie die grundlegenden Informationen für die neue Sporthalle ein.
                            </p>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Name der Sporthalle *
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="z.B. Turnhalle Hauptschule"
                                />
                                <div v-if="errors.name" class="mt-1 text-sm text-red-600">
                                    {{ Array.isArray(errors.name) ? errors.name.join(', ') : errors.name }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                    Beschreibung
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Optionale Beschreibung der Sporthalle..."
                                ></textarea>
                                <div v-if="errors.description" class="mt-1 text-sm text-red-600">
                                    {{ Array.isArray(errors.description) ? errors.description.join(', ') : errors.description }}
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Adresse
                                </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Vollständige Adresse der Sporthalle..."
                                ></textarea>
                                <div v-if="errors.address" class="mt-1 text-sm text-red-600">
                                    {{ Array.isArray(errors.address) ? errors.address.join(', ') : errors.address }}
                                </div>
                            </div>

                            <!-- Capacity -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">
                                        Kapazität (Personen)
                                    </label>
                                    <input
                                        id="capacity"
                                        v-model="form.capacity"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="z.B. 200"
                                    />
                                    <div v-if="errors.capacity" class="mt-1 text-sm text-red-600">
                                        {{ Array.isArray(errors.capacity) ? errors.capacity.join(', ') : errors.capacity }}
                                    </div>
                                </div>

                                <div>
                                    <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-1">
                                        Stundensatz (€)
                                    </label>
                                    <input
                                        id="hourly_rate"
                                        v-model="form.hourly_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="z.B. 25.00"
                                    />
                                    <div v-if="errors.hourly_rate" class="mt-1 text-sm text-red-600">
                                        {{ Array.isArray(errors.hourly_rate) ? errors.hourly_rate.join(', ') : errors.hourly_rate }}
                                    </div>
                                </div>
                            </div>

                            <!-- Court Dimensions -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="court_length" class="block text-sm font-medium text-gray-700 mb-1">
                                        Spielfeldlänge (m)
                                    </label>
                                    <input
                                        id="court_length"
                                        v-model="form.court_length"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="z.B. 28.0"
                                    />
                                    <div v-if="errors.court_length" class="mt-1 text-sm text-red-600">
                                        {{ Array.isArray(errors.court_length) ? errors.court_length.join(', ') : errors.court_length }}
                                    </div>
                                </div>

                                <div>
                                    <label for="court_width" class="block text-sm font-medium text-gray-700 mb-1">
                                        Spielfeldbreite (m)
                                    </label>
                                    <input
                                        id="court_width"
                                        v-model="form.court_width"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="z.B. 15.0"
                                    />
                                    <div v-if="errors.court_width" class="mt-1 text-sm text-red-600">
                                        {{ Array.isArray(errors.court_width) ? errors.court_width.join(', ') : errors.court_width }}
                                    </div>
                                </div>
                            </div>

                            <!-- Features -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ausstattung
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.has_scoreboard"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Anzeigetafel</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.has_sound_system"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Soundanlage</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.has_livestream"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Livestream</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Sporthalle ist aktiv</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500">
                                    Nur aktive Sporthallen können gebucht werden.
                                </p>
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <div v-if="Object.keys(errors).length > 0" class="px-6 py-4 bg-red-50 border-t border-red-200">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-red-800">Fehler beim Erstellen:</h3>
                                    <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                        <li v-for="(fieldErrors, field) in errors" :key="field">
                                            <span v-if="field === 'general'">{{ fieldErrors.join(', ') }}</span>
                                            <span v-else>{{ field }}: {{ fieldErrors.join(', ') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                            <Link
                                :href="route('gym.halls')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Abbrechen
                            </Link>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Wird erstellt...' : 'Sporthalle erstellen' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    currentClub: Object
})

const form = ref({
    club_id: props.currentClub?.id || null,
    name: '',
    description: '',
    address: '',
    capacity: null,
    hourly_rate: null,
    court_length: 28.0,
    court_width: 15.0,
    has_scoreboard: false,
    has_sound_system: false,
    has_livestream: false,
    is_active: true,
})

const processing = ref(false)
const errors = ref({})

const submit = async () => {
    if (processing.value) return
    
    processing.value = true
    errors.value = {}
    
    try {
        const response = await window.axios.post('/api/v2/gym-halls', form.value, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            withCredentials: true
        })
        
        if (response.status >= 200 && response.status < 300) {
            // Redirect to halls list on success
            router.visit(route('gym.halls'), {
                onSuccess: () => {
                    // Show success message via flash or notification
                    console.log('Sporthalle erfolgreich erstellt')
                }
            })
        }
    } catch (error) {
        console.error('Error creating gym hall:', error)
        
        if (error.response?.data?.errors) {
            // Laravel validation errors - ensure they're properly formatted
            const formattedErrors = {}
            for (const [field, fieldErrors] of Object.entries(error.response.data.errors)) {
                formattedErrors[field] = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors]
            }
            errors.value = formattedErrors
        } else if (error.response?.data?.message) {
            errors.value = { general: [error.response.data.message] }
        } else {
            errors.value = { general: ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'] }
        }
    } finally {
        processing.value = false
    }
}
</script>