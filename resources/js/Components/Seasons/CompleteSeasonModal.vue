<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    season: {
        type: Object,
        default: null
    },
    club: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close']);

const processing = ref(false);
const createSnapshots = ref(true);

const completeSeason = () => {
    if (!props.season) return;

    processing.value = true;

    router.post(route('club.seasons.complete', {
        club: props.club.id,
        season: props.season.id
    }), {
        create_snapshots: createSnapshots.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};
</script>

<template>
    <DialogModal :show="show" @close="$emit('close')">
        <template #title>
            Saison abschließen
        </template>

        <template #content>
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Sie sind dabei, die Saison <strong class="font-semibold text-gray-900">{{ season?.name }}</strong> abzuschließen.
                </p>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-blue-900 mb-2">
                                Was passiert beim Abschluss?
                            </h4>
                            <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                <li>Die Saison wird als "abgeschlossen" markiert</li>
                                <li>Statistik-Snapshots werden erstellt (wenn aktiviert)</li>
                                <li>Keine weiteren Änderungen an Spielen möglich</li>
                                <li>Endgültige Rankings werden fixiert</li>
                                <li>Sie können jederzeit eine neue Saison starten</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-if="season" class="bg-gray-50 rounded-md p-3">
                    <h5 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">
                        Saison-Übersicht
                    </h5>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div>
                            <dt class="text-gray-500">Zeitraum:</dt>
                            <dd class="text-gray-900 font-medium">
                                {{ new Date(season.start_date).toLocaleDateString('de-DE') }} -
                                {{ new Date(season.end_date).toLocaleDateString('de-DE') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Teams:</dt>
                            <dd class="text-gray-900 font-medium">{{ season.teams_count || 0 }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Gespielte Spiele:</dt>
                            <dd class="text-gray-900 font-medium">{{ season.games_count || 0 }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Spieler:</dt>
                            <dd class="text-gray-900 font-medium">{{ season.players_count || 0 }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <Checkbox
                                id="create_snapshots"
                                v-model:checked="createSnapshots"
                            />
                        </div>
                        <div class="ml-3 text-sm">
                            <InputLabel for="create_snapshots" class="text-gray-700 font-medium">
                                Statistik-Snapshots erstellen
                            </InputLabel>
                            <p class="text-gray-500 text-xs mt-1">
                                Empfohlen: Erstellt unveränderliche Snapshots aller Spieler-Statistiken, Team-Rankings und Spieldaten zum Zeitpunkt des Abschlusses.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-md p-3">
                    <p class="text-xs text-amber-800">
                        <strong class="font-semibold">Hinweis:</strong> Nach dem Abschluss können keine neuen Spiele mehr hinzugefügt oder bestehende Ergebnisse geändert werden. Sie können die Saison jedoch weiterhin einsehen und Berichte generieren.
                    </p>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="$emit('close')" :disabled="processing">
                Abbrechen
            </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                @click="completeSeason"
                :disabled="processing"
                :class="{ 'opacity-25': processing }"
            >
                <span v-if="processing">Wird abgeschlossen...</span>
                <span v-else>Saison abschließen</span>
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
