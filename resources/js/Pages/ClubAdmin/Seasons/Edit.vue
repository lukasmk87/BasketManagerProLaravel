<script setup>
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ArrowLeftIcon, CalendarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    club: {
        type: Object,
        required: true
    },
    season: {
        type: Object,
        required: true
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    name: props.season.name || '',
    description: props.season.description || '',
    start_date: props.season.start_date || '',
    end_date: props.season.end_date || ''
});

const canDelete = computed(() => {
    return props.season.status !== 'active' &&
           (props.permissions?.canDelete || props.permissions?.canManage || false);
});

const isCompleted = computed(() => {
    return props.season.status === 'completed';
});

const submit = () => {
    form.put(route('club-admin.seasons.update', props.season.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Success message will be shown via Laravel flash
        }
    });
};

const handleBack = () => {
    router.visit(route('club-admin.seasons.show', props.season.id));
};

const handleDelete = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!`)) {
        router.delete(route('club-admin.seasons.destroy', props.season.id));
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout :title="`${season.name} bearbeiten`">
        <Head :title="`${season.name} bearbeiten - ${club.name}`" />

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-4">
                    <button
                        @click="handleBack"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition"
                    >
                        <ArrowLeftIcon class="h-4 w-4 mr-1" />
                        Zurück zur Saison
                    </button>
                </div>

                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Saison bearbeiten
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Ändern Sie die Details für "{{ season.name }}"
                    </p>
                </div>

                <!-- Warning for Completed Seasons -->
                <div v-if="isCompleted" class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Achtung:</strong> Diese Saison wurde bereits abgeschlossen.
                                Änderungen sollten nur in Ausnahmefällen vorgenommen werden.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="bg-white shadow rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <!-- Season Name -->
                            <div>
                                <InputLabel for="name" value="Saison-Name *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    placeholder="z.B. 2023/2024"
                                    :disabled="form.processing"
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Ein eindeutiger Name für diese Saison
                                </p>
                            </div>

                            <!-- Date Range -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="start_date" value="Startdatum *" />
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <CalendarIcon class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="start_date"
                                            v-model="form.start_date"
                                            type="date"
                                            class="mt-1 block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                            :disabled="form.processing"
                                        />
                                    </div>
                                    <InputError :message="form.errors.start_date" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="end_date" value="Enddatum *" />
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <CalendarIcon class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="end_date"
                                            v-model="form.end_date"
                                            type="date"
                                            class="mt-1 block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                            :disabled="form.processing"
                                            :min="form.start_date"
                                        />
                                    </div>
                                    <InputError :message="form.errors.end_date" class="mt-2" />
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Beschreibung" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    maxlength="500"
                                    placeholder="Optionale Beschreibung für diese Saison..."
                                    :disabled="form.processing"
                                ></textarea>
                                <InputError :message="form.errors.description" class="mt-2" />
                                <div class="mt-1 flex justify-between text-xs text-gray-500">
                                    <span>Optional: Zusätzliche Informationen zur Saison</span>
                                    <span>{{ form.description?.length || 0 }} / 500</span>
                                </div>
                            </div>

                            <!-- Season Info -->
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Saison-Informationen</h4>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                    <div>
                                        <dt class="text-blue-700">Status:</dt>
                                        <dd class="text-blue-900 font-medium">
                                            {{ season.status === 'draft' ? 'Entwurf' : season.status === 'active' ? 'Aktiv' : 'Abgeschlossen' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-700">Teams:</dt>
                                        <dd class="text-blue-900 font-medium">{{ season.teams_count || 0 }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-700">Spiele:</dt>
                                        <dd class="text-blue-900 font-medium">{{ season.games_count || 0 }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-700">Spieler:</dt>
                                        <dd class="text-blue-900 font-medium">{{ season.players_count || 0 }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex items-center justify-between pt-6 border-t border-gray-200">
                            <div>
                                <DangerButton
                                    v-if="canDelete"
                                    type="button"
                                    @click="handleDelete"
                                    :disabled="form.processing"
                                >
                                    Saison löschen
                                </DangerButton>
                            </div>

                            <div class="flex items-center space-x-3">
                                <SecondaryButton
                                    type="button"
                                    @click="handleBack"
                                    :disabled="form.processing"
                                >
                                    Abbrechen
                                </SecondaryButton>

                                <PrimaryButton
                                    type="submit"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing">Speichern...</span>
                                    <span v-else>Änderungen speichern</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Additional Info -->
                <div class="mt-6 bg-gray-50 border border-gray-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Hinweise</h4>
                    <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                        <li>Änderungen am Saison-Namen werden sofort übernommen</li>
                        <li>Das Startdatum sollte vor dem Enddatum liegen</li>
                        <li v-if="season.status === 'active'">
                            Eine aktive Saison kann nicht gelöscht werden
                        </li>
                        <li v-if="season.status === 'completed'">
                            Vorsicht bei Änderungen an abgeschlossenen Saisons - dies kann Statistiken beeinflussen
                        </li>
                        <li>Felder mit * sind Pflichtfelder</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
