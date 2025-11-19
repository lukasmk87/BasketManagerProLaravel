<script setup>
import { computed } from 'vue';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    canActivate: {
        type: Boolean,
        default: true
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const settings = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const updateSetting = (key, value) => {
    if (props.readonly) return;

    emit('update:modelValue', {
        ...settings.value,
        [key]: value
    });
};
</script>

<template>
    <div class="space-y-8">
        <!-- Saison-Status Section -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Saison-Status</h3>
            <div class="space-y-4">
                <!-- Auto Activate -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            :id="`auto-activate-${$.uid}`"
                            type="checkbox"
                            :checked="settings.auto_activate"
                            @change="updateSetting('auto_activate', $event.target.checked)"
                            :disabled="!canActivate || readonly"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                        />
                    </div>
                    <div class="ml-3 text-sm">
                        <label :for="`auto-activate-${$.uid}`" class="font-medium text-gray-700">
                            Saison sofort aktivieren
                        </label>
                        <p class="text-gray-500">
                            Die Saison wird direkt nach der Erstellung aktiviert.
                            Alle anderen aktiven Saisons werden deaktiviert.
                        </p>
                        <div v-if="!canActivate" class="mt-1 flex items-center text-yellow-600">
                            <InformationCircleIcon class="h-4 w-4 mr-1" />
                            <span class="text-xs">Keine Berechtigung zum Aktivieren</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spielplan Section -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Spielplan</h3>
            <div class="space-y-4">
                <!-- Create Schedule -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            :id="`create-schedule-${$.uid}`"
                            type="checkbox"
                            :checked="settings.create_schedule"
                            @change="updateSetting('create_schedule', $event.target.checked)"
                            :disabled="readonly"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                    </div>
                    <div class="ml-3 text-sm">
                        <label :for="`create-schedule-${$.uid}`" class="font-medium text-gray-700">
                            Automatischen Spielplan generieren
                        </label>
                        <p class="text-gray-500">
                            Ein Spielplan wird automatisch für alle Teams erstellt.
                        </p>
                    </div>
                </div>

                <!-- Schedule Type (only if create_schedule is true) -->
                <div v-show="settings.create_schedule" class="ml-7 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Rundensystem</label>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center">
                                <input
                                    :id="`schedule-single-${$.uid}`"
                                    type="radio"
                                    :checked="settings.schedule_type === 'single'"
                                    @change="updateSetting('schedule_type', 'single')"
                                    :disabled="readonly"
                                    value="single"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                />
                                <label :for="`schedule-single-${$.uid}`" class="ml-3 text-sm text-gray-700">
                                    Einfache Runde (jedes Team spielt einmal gegeneinander)
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input
                                    :id="`schedule-double-${$.uid}`"
                                    type="radio"
                                    :checked="settings.schedule_type === 'double'"
                                    @change="updateSetting('schedule_type', 'double')"
                                    :disabled="readonly"
                                    value="double"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                />
                                <label :for="`schedule-double-${$.uid}`" class="ml-3 text-sm text-gray-700">
                                    Doppelrunde (Hin- und Rückspiel)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Home Advantage -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                :id="`home-advantage-${$.uid}`"
                                type="checkbox"
                                :checked="settings.consider_home_advantage"
                                @change="updateSetting('consider_home_advantage', $event.target.checked)"
                                :disabled="readonly"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                        </div>
                        <div class="ml-3 text-sm">
                            <label :for="`home-advantage-${$.uid}`" class="font-medium text-gray-700">
                                Heimvorteil berücksichtigen
                            </label>
                            <p class="text-gray-500">
                                Teams spielen abwechselnd Heim- und Auswärtsspiele
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Benachrichtigungen Section -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Benachrichtigungen</h3>
            <div class="space-y-4">
                <!-- Notify Teams -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            :id="`notify-teams-${$.uid}`"
                            type="checkbox"
                            :checked="settings.notify_teams"
                            @change="updateSetting('notify_teams', $event.target.checked)"
                            :disabled="readonly"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                    </div>
                    <div class="ml-3 text-sm">
                        <label :for="`notify-teams-${$.uid}`" class="font-medium text-gray-700">
                            Teams über neue Saison informieren
                        </label>
                        <p class="text-gray-500">
                            Trainer erhalten eine Benachrichtigung über die neue Saison
                        </p>
                    </div>
                </div>

                <!-- Notify Players -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            :id="`notify-players-${$.uid}`"
                            type="checkbox"
                            :checked="settings.notify_players"
                            @change="updateSetting('notify_players', $event.target.checked)"
                            :disabled="readonly"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                    </div>
                    <div class="ml-3 text-sm">
                        <label :for="`notify-players-${$.uid}`" class="font-medium text-gray-700">
                            Spieler über Kader-Zuordnung informieren
                        </label>
                        <p class="text-gray-500">
                            Spieler erhalten eine E-Mail über ihre Zuordnung zum Team
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Erweiterte Einstellungen Section -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Erweiterte Einstellungen</h3>
            <div class="space-y-4">
                <!-- Snapshot Interval -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Statistik-Snapshot Intervall
                    </label>
                    <select
                        :value="settings.snapshot_interval"
                        @change="updateSetting('snapshot_interval', $event.target.value)"
                        :disabled="readonly"
                        class="mt-1 block w-full sm:w-auto border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="daily">Täglich</option>
                        <option value="weekly">Wöchentlich</option>
                        <option value="monthly">Monatlich</option>
                        <option value="never">Nie</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Wie oft sollen Statistik-Snapshots erstellt werden?
                    </p>
                </div>

                <!-- Roster Lock Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Kader-Sperre Datum (Optional)
                    </label>
                    <input
                        type="date"
                        :value="settings.roster_lock_date"
                        @input="updateSetting('roster_lock_date', $event.target.value)"
                        :disabled="readonly"
                        class="mt-1 block w-full sm:w-auto border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                    <p class="mt-1 text-sm text-gray-500">
                        Ab diesem Datum können keine Kader-Änderungen mehr vorgenommen werden
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <InformationCircleIcon class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Tipp:</strong> Diese Einstellungen können später in den Saison-Einstellungen
                        jederzeit geändert werden.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
