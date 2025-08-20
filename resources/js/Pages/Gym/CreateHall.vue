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
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
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
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.description }}
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
                                <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.address }}
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
                                    <div v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.capacity }}
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
                                    <div v-if="form.errors.hourly_rate" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.hourly_rate }}
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
                                    <div v-if="form.errors.court_length" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.court_length }}
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
                                    <div v-if="form.errors.court_width" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.court_width }}
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

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                            <Link
                                :href="route('gym.halls')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Abbrechen
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Wird erstellt...' : 'Sporthalle erstellen' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
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

const submit = () => {
    form.post(route('api.gym-halls.store'), {
        onSuccess: () => {
            // Redirect to halls list or show success message
        },
    })
}
</script>