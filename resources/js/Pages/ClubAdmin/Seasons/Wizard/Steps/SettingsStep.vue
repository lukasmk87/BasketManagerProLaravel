<script setup>
import { computed } from 'vue';
import SeasonSettingsForm from '@/Components/Wizard/SeasonSettingsForm.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:form']);

const localForm = computed({
    get: () => props.form,
    set: (value) => emit('update:form', value)
});

const settings = computed({
    get: () => ({
        auto_activate: localForm.value.auto_activate || false,
        create_schedule: localForm.value.create_schedule || false,
        schedule_type: localForm.value.schedule_type || 'double',
        consider_home_advantage: localForm.value.consider_home_advantage || true,
        notify_teams: localForm.value.notify_teams || true,
        notify_players: localForm.value.notify_players || false,
        snapshot_interval: localForm.value.snapshot_interval || 'weekly',
        roster_lock_date: localForm.value.roster_lock_date || null
    }),
    set: (value) => {
        localForm.value = {
            ...localForm.value,
            ...value
        };
    }
});

const canActivate = computed(() => {
    return props.permissions?.canActivate ||
           props.permissions?.canManage ||
           false;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Einstellungen</h2>
            <p class="mt-1 text-sm text-gray-500">
                Konfigurieren Sie zusätzliche Optionen für die neue Saison.
            </p>
        </div>

        <!-- Settings Form -->
        <SeasonSettingsForm
            v-model="settings"
            :can-activate="canActivate"
            :readonly="false"
        />

        <!-- Preview of Selected Settings -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-3">Ausgewählte Einstellungen</h4>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>
                    <dt class="text-blue-700">Status:</dt>
                    <dd class="text-blue-900 font-medium">
                        {{ settings.auto_activate ? 'Sofort aktivieren' : 'Als Entwurf speichern' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-blue-700">Spielplan:</dt>
                    <dd class="text-blue-900 font-medium">
                        {{ settings.create_schedule
                            ? `Ja (${settings.schedule_type === 'double' ? 'Doppelrunde' : 'Einfache Runde'})`
                            : 'Nein'
                        }}
                    </dd>
                </div>
                <div>
                    <dt class="text-blue-700">Team-Benachrichtigung:</dt>
                    <dd class="text-blue-900 font-medium">
                        {{ settings.notify_teams ? 'Ja' : 'Nein' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-blue-700">Spieler-Benachrichtigung:</dt>
                    <dd class="text-blue-900 font-medium">
                        {{ settings.notify_players ? 'Ja' : 'Nein' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-blue-700">Snapshot-Intervall:</dt>
                    <dd class="text-blue-900 font-medium">
                        {{
                            settings.snapshot_interval === 'daily' ? 'Täglich' :
                            settings.snapshot_interval === 'weekly' ? 'Wöchentlich' :
                            settings.snapshot_interval === 'monthly' ? 'Monatlich' :
                            'Nie'
                        }}
                    </dd>
                </div>
                <div v-if="settings.roster_lock_date">
                    <dt class="text-blue-700">Kader-Sperre:</dt>
                    <dd class="text-blue-900 font-medium">
                        {{ new Date(settings.roster_lock_date).toLocaleDateString('de-DE') }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Warnings -->
        <div v-if="settings.auto_activate && !canActivate" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Hinweis:</strong> Sie haben keine Berechtigung zum sofortigen Aktivieren.
                        Die Saison wird als Entwurf gespeichert.
                    </p>
                </div>
            </div>
        </div>

        <div v-if="settings.auto_activate" class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <strong>Achtung:</strong> Beim sofortigen Aktivieren werden alle anderen aktiven Saisons
                        automatisch deaktiviert. Diese Aktion kann später rückgängig gemacht werden.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
