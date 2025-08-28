<template>
    <AppLayout title="Spiele Import">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Spiele Import
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton 
                        :href="route('games.import.history')"
                        as="Link"
                    >
                        📊 Import-Verlauf
                    </SecondaryButton>
                    <SecondaryButton 
                        :href="route('web.games.index')"
                        as="Link"
                    >
                        🏀 Alle Spiele
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Info Card -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Spiele aus iCAL-Dateien importieren</strong><br>
                                Laden Sie iCAL-Dateien von basketball-bund.net oder anderen Quellen hoch. 
                                Nach dem Upload können Sie die Teams aus der Datei den entsprechenden Teams in Ihrem System zuordnen.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Import Form -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            iCAL-Datei Import
                        </h3>

                        <form @submit.prevent="uploadFile" class="space-y-6">
                            <!-- Team Selection -->
                            <div>
                                <InputLabel for="team_id" value="Team auswählen*" />
                                <select
                                    id="team_id"
                                    v-model="form.team_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="">Team auswählen</option>
                                    <option 
                                        v-for="team in teams" 
                                        :key="team.id" 
                                        :value="team.id"
                                    >
                                        {{ team.name }} ({{ team.club.name }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.team_id" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Wählen Sie das Team aus, für das die Spiele importiert werden sollen.
                                </p>
                            </div>

                            <!-- File Upload -->
                            <div>
                                <InputLabel for="ical_file" value="iCAL-Datei (.ics)*" />
                                <input
                                    id="ical_file"
                                    ref="fileInput"
                                    type="file"
                                    accept=".ics"
                                    @change="handleFileChange"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    required
                                />
                                <InputError :message="form.errors.ical_file" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Unterstützte Formate: .ics (iCalendar) • Max. 2MB
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end space-x-4">
                                <SecondaryButton
                                    type="button"
                                    @click="resetForm"
                                    :disabled="form.processing"
                                >
                                    Zurücksetzen
                                </SecondaryButton>
                                
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing || !selectedFile || !form.team_id"
                                >
                                    <span v-if="form.processing" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Verarbeite...
                                    </span>
                                    <span v-else>
                                        🔍 Datei analysieren
                                    </span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions for Club Admins -->
                <div v-if="canImportForAllTeams" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Erweiterte Import-Optionen
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button
                                @click="$inertia.visit(route('games.import.select-team'))"
                                class="flex items-center justify-center px-4 py-3 bg-indigo-100 border border-transparent rounded-lg font-medium text-indigo-700 hover:bg-indigo-200 focus:outline-none focus:border-indigo-700 focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Multi-Team Import
                            </button>
                            
                            <button
                                @click="$inertia.visit(route('games.import.history'))"
                                class="flex items-center justify-center px-4 py-3 bg-green-100 border border-transparent rounded-lg font-medium text-green-700 hover:bg-green-200 focus:outline-none focus:border-green-700 focus:ring-2 focus:ring-green-500 transition duration-150 ease-in-out"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Import-Statistiken
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Imports -->
                <div v-if="$page.props.importResult" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Letzter Import
                        </h3>
                        <div class="bg-green-50 border-l-4 border-green-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        <strong>{{ $page.props.importResult.imported }} Spiele erfolgreich importiert</strong>
                                        <span v-if="$page.props.importResult.skipped > 0">
                                            <br>{{ $page.props.importResult.skipped }} Spiele übersprungen (bereits vorhanden)
                                        </span>
                                        <span v-if="$page.props.importResult.errors && $page.props.importResult.errors.length > 0">
                                            <br>{{ $page.props.importResult.errors.length }} Fehler aufgetreten
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    teams: Array,
    canImportForAllTeams: Boolean,
})

// Form and file handling
const form = useForm({
    team_id: '',
    ical_file: null,
})

const fileInput = ref(null)
const selectedFile = ref(null)

const handleFileChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        selectedFile.value = file
        form.ical_file = file
    } else {
        selectedFile.value = null
        form.ical_file = null
    }
}

const resetForm = () => {
    form.reset()
    selectedFile.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const uploadFile = () => {
    if (!selectedFile.value || !form.team_id) {
        return
    }

    form.post(route('games.import.analyze'), {
        forceFormData: true,
        onSuccess: () => {
            // Will redirect to team mapping page
        },
        onError: (errors) => {
            console.log('Upload errors:', errors)
        }
    })
}
</script>