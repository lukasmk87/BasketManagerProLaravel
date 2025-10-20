<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    clubs: {
        type: Array,
        required: true,
    },
    teams: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    club_id: props.clubs.length === 1 ? props.clubs[0].id : null,
    target_team_id: null,
    expires_at: getDefaultExpiryDate(),
    max_registrations: 50,
    qr_size: 300,
    settings: {},
});

// Get default expiry date (30 days from now)
function getDefaultExpiryDate() {
    const date = new Date();
    date.setDate(date.getDate() + 30);
    return date.toISOString().slice(0, 16); // Format for datetime-local input
}

// Computed: filtered teams based on selected club
const availableTeams = computed(() => {
    if (!form.club_id) return [];
    return props.teams.filter(team => team.club_id === form.club_id);
});

// Handle club change - reset team selection
const handleClubChange = () => {
    form.target_team_id = null;
};

// Submit form
const submit = () => {
    form.post(route('trainer.invitations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Will redirect to show page on success
        },
    });
};
</script>

<template>
    <AppLayout title="Neue Spieler-Einladung erstellen">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Neue Spieler-Einladung erstellen
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Information Banner -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Erstellen Sie eine QR-Code-basierte Einladung für neue Spieler. Spieler können sich über den QR-Code oder Link registrieren. Club-Administratoren weisen sie dann einem Team zu.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Club Selection -->
                        <div>
                            <label for="club_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Club *
                            </label>
                            <select
                                id="club_id"
                                v-model="form.club_id"
                                class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.club_id }"
                                @change="handleClubChange"
                                required
                            >
                                <option :value="null">-- Club auswählen --</option>
                                <option v-for="club in clubs" :key="club.id" :value="club.id">
                                    {{ club.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.club_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.club_id }}
                            </p>
                        </div>

                        <!-- Target Team Selection (Optional) -->
                        <div>
                            <label for="target_team_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Ziel-Team (Optional)
                            </label>
                            <select
                                id="target_team_id"
                                v-model="form.target_team_id"
                                class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.target_team_id }"
                                :disabled="!form.club_id || availableTeams.length === 0"
                            >
                                <option :value="null">-- Kein spezifisches Team (Club-allgemein) --</option>
                                <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                                    {{ team.name }}
                                    <template v-if="team.age_group || team.gender">
                                        ({{ team.age_group }} {{ team.gender }})
                                    </template>
                                </option>
                            </select>
                            <p v-if="form.errors.target_team_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.target_team_id }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Leer lassen für allgemeine Club-Einladungen. Der Club-Admin kann später das Team zuweisen.
                            </p>
                        </div>

                        <!-- Expiry Date -->
                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Ablaufdatum *
                            </label>
                            <input
                                id="expires_at"
                                v-model="form.expires_at"
                                type="datetime-local"
                                class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.expires_at }"
                                required
                            />
                            <p v-if="form.errors.expires_at" class="mt-1 text-sm text-red-600">
                                {{ form.errors.expires_at }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Wann soll die Einladung ablaufen? Standard: 30 Tage
                            </p>
                        </div>

                        <!-- Max Registrations -->
                        <div>
                            <label for="max_registrations" class="block text-sm font-medium text-gray-700 mb-1">
                                Maximale Registrierungen
                            </label>
                            <input
                                id="max_registrations"
                                v-model.number="form.max_registrations"
                                type="number"
                                min="1"
                                max="500"
                                class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.max_registrations }"
                            />
                            <p v-if="form.errors.max_registrations" class="mt-1 text-sm text-red-600">
                                {{ form.errors.max_registrations }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Wie viele Spieler dürfen sich maximal über diese Einladung registrieren? (1-500)
                            </p>
                        </div>

                        <!-- QR Code Size -->
                        <div>
                            <label for="qr_size" class="block text-sm font-medium text-gray-700 mb-1">
                                QR-Code Größe (Pixel)
                            </label>
                            <div class="flex items-center gap-4">
                                <input
                                    id="qr_size"
                                    v-model.number="form.qr_size"
                                    type="range"
                                    min="100"
                                    max="1000"
                                    step="50"
                                    class="flex-1"
                                />
                                <span class="text-sm font-medium text-gray-700 w-20 text-right">
                                    {{ form.qr_size }}px
                                </span>
                            </div>
                            <p v-if="form.errors.qr_size" class="mt-1 text-sm text-red-600">
                                {{ form.errors.qr_size }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Größe des generierten QR-Codes (100-1000 Pixel)
                            </p>
                        </div>

                        <!-- Visual QR Size Preview -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">QR-Code Vorschau-Größe:</p>
                            <div class="flex justify-center">
                                <div
                                    class="bg-gray-300 rounded-lg flex items-center justify-center text-gray-500 text-xs"
                                    :style="{ width: Math.min(form.qr_size, 400) + 'px', height: Math.min(form.qr_size, 400) + 'px' }"
                                >
                                    {{ form.qr_size }}x{{ form.qr_size }}
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <a
                                :href="route('trainer.invitations.index')"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Abbrechen
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <svg
                                    v-else
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ form.processing ? 'Erstelle...' : 'Einladung erstellen' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Help Text -->
                <div class="mt-6 bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">
                        <svg class="inline w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        So funktioniert's
                    </h3>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                        <li>Erstellen Sie eine Einladung mit QR-Code für Ihren Club oder ein spezifisches Team</li>
                        <li>Teilen Sie den QR-Code oder Link mit potenziellen Spielern</li>
                        <li>Spieler registrieren sich selbst über ein einfaches Formular</li>
                        <li>Club-Administratoren prüfen die Registrierungen und weisen Spieler Teams zu</li>
                        <li>Nach Zuweisung kann sich der Spieler einloggen und die App nutzen</li>
                    </ol>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
