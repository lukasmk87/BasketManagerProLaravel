<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    clubs: {
        type: Array,
        required: true,
    },
    available_roles: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    club_id: props.clubs.length === 1 ? props.clubs[0].id : null,
    default_role: 'member',
    expires_at: getDefaultExpiryDate(),
    max_uses: 100,
    qr_size: 300,
    qr_format: 'svg',
    settings: {},
});

function getDefaultExpiryDate() {
    const date = new Date();
    date.setDate(date.getDate() + 30); // 30 days from now
    return date.toISOString().split('T')[0];
}

const submit = () => {
    form.post(route('club-admin.invitations.store'), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>

<template>
    <ClubAdminLayout title="Neue Einladung erstellen">
        <template #header>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Neue Club-Einladung erstellen
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Erstellen Sie einen Einladungslink oder QR-Code für neue Mitglieder
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Club Selection -->
                        <div v-if="clubs.length > 1">
                            <label for="club_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Club <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.club_id"
                                id="club_id"
                                required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.club_id }"
                            >
                                <option :value="null">Bitte wählen...</option>
                                <option v-for="club in clubs" :key="club.id" :value="club.id">
                                    {{ club.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.club_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.club_id }}
                            </p>
                        </div>

                        <!-- Default Role -->
                        <div>
                            <label for="default_role" class="block text-sm font-medium text-gray-700 mb-2">
                                Standardrolle <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.default_role"
                                id="default_role"
                                required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.default_role }"
                            >
                                <option v-for="role in available_roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                Neue Mitglieder erhalten automatisch diese Rolle im Club
                            </p>
                            <p v-if="form.errors.default_role" class="mt-1 text-sm text-red-600">
                                {{ form.errors.default_role }}
                            </p>
                        </div>

                        <!-- Expiry Date -->
                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                                Gültig bis <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.expires_at"
                                type="date"
                                id="expires_at"
                                required
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.expires_at }"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Die Einladung läuft nach diesem Datum automatisch ab
                            </p>
                            <p v-if="form.errors.expires_at" class="mt-1 text-sm text-red-600">
                                {{ form.errors.expires_at }}
                            </p>
                        </div>

                        <!-- Max Uses -->
                        <div>
                            <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-2">
                                Maximale Nutzungen
                            </label>
                            <input
                                v-model.number="form.max_uses"
                                type="number"
                                id="max_uses"
                                min="1"
                                max="1000"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.max_uses }"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Wie viele Personen können sich mit dieser Einladung registrieren?
                            </p>
                            <p v-if="form.errors.max_uses" class="mt-1 text-sm text-red-600">
                                {{ form.errors.max_uses }}
                            </p>
                        </div>

                        <!-- QR Code Settings -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">QR-Code Einstellungen</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- QR Code Size -->
                                <div>
                                    <label for="qr_size" class="block text-sm font-medium text-gray-700 mb-2">
                                        QR-Code Größe
                                    </label>
                                    <select
                                        v-model.number="form.qr_size"
                                        id="qr_size"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option :value="200">Klein (200x200)</option>
                                        <option :value="300">Mittel (300x300)</option>
                                        <option :value="400">Groß (400x400)</option>
                                        <option :value="600">Extra Groß (600x600)</option>
                                        <option :value="800">Druck (800x800)</option>
                                    </select>
                                </div>

                                <!-- QR Code Format -->
                                <div>
                                    <label for="qr_format" class="block text-sm font-medium text-gray-700 mb-2">
                                        QR-Code Format
                                    </label>
                                    <select
                                        v-model="form.qr_format"
                                        id="qr_format"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="svg">SVG (empfohlen)</option>
                                        <option value="png">PNG</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">
                                        SVG ist vektorbasiert und skaliert besser
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">
                                        Hinweis zur Verwendung
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Der QR-Code und Einladungslink werden automatisch generiert</li>
                                            <li>Sie können den Link per E-Mail teilen oder den QR-Code ausdrucken</li>
                                            <li>Neue Mitglieder registrieren sich selbst über den Link</li>
                                            <li>Sie können die Einladung jederzeit deaktivieren</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                            <a
                                :href="route('club-admin.invitations.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Abbrechen
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Erstelle...' : 'Einladung erstellen' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
