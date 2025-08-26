<template>
    <AppLayout :title="`Drill: ${drill.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ drill.name }}
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton @click="goBack">
                        Zurück zu den Übungen
                    </SecondaryButton>
                    <PrimaryButton 
                        v-if="can?.edit"
                        @click="editDrill"
                    >
                        Bearbeiten
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header Info -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <div class="flex items-center gap-4 mb-2">
                                    <span 
                                        :class="getDifficultyClasses(drill.difficulty_level)"
                                        class="px-3 py-1 text-sm rounded-full font-medium"
                                    >
                                        {{ getDifficultyLabel(drill.difficulty_level) }}
                                    </span>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full font-medium">
                                        {{ getCategoryLabel(drill.category) }}
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm">
                                    Erstellt von: {{ drill.created_by?.name || 'Unbekannt' }} 
                                    am {{ formatDate(drill.created_at) }}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Beschreibung</h3>
                            <p class="text-gray-700">{{ drill.description }}</p>
                        </div>

                        <!-- Objectives -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Ziele</h3>
                            <p class="text-gray-700">{{ drill.objectives }}</p>
                        </div>

                        <!-- Instructions -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Anweisungen</h3>
                            <div class="text-gray-700 whitespace-pre-wrap">{{ drill.instructions }}</div>
                        </div>

                        <!-- Training Parameters -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-1">Spieleranzahl</h4>
                                <p class="text-gray-600">
                                    {{ drill.min_players }}<span v-if="drill.max_players">-{{ drill.max_players }}</span><span v-else>+</span> Spieler
                                </p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-1">Dauer</h4>
                                <p class="text-gray-600">{{ drill.estimated_duration }} Minuten</p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-1">Altersgruppe</h4>
                                <p class="text-gray-600">{{ drill.age_group }}</p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-1">Status</h4>
                                <p class="text-gray-600">{{ getStatusLabel(drill.status) }}</p>
                            </div>
                        </div>

                        <!-- Equipment -->
                        <div v-if="drill.required_equipment?.length || drill.optional_equipment?.length" class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ausrüstung</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="drill.required_equipment?.length">
                                    <h4 class="font-medium text-gray-900 mb-2">Erforderlich</h4>
                                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                                        <li v-for="equipment in drill.required_equipment" :key="equipment">
                                            {{ equipment }}
                                        </li>
                                    </ul>
                                </div>
                                <div v-if="drill.optional_equipment?.length">
                                    <h4 class="font-medium text-gray-900 mb-2">Optional</h4>
                                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                                        <li v-for="equipment in drill.optional_equipment" :key="equipment">
                                            {{ equipment }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Court Requirements -->
                        <div v-if="drill.requires_full_court || drill.requires_half_court" class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Platzanforderungen</h3>
                            <div class="flex gap-4">
                                <span v-if="drill.requires_full_court" class="px-3 py-1 bg-orange-100 text-orange-800 text-sm rounded-full">
                                    Vollfeld erforderlich
                                </span>
                                <span v-if="drill.requires_half_court" class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                    Halbfeld erforderlich
                                </span>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div v-if="drill.coaching_points?.length">
                                <h4 class="font-medium text-gray-900 mb-2">Trainer-Tipps</h4>
                                <ul class="list-disc list-inside text-gray-700 space-y-1 text-sm">
                                    <li v-for="point in drill.coaching_points" :key="point">
                                        {{ point }}
                                    </li>
                                </ul>
                            </div>

                            <div v-if="drill.success_criteria?.length">
                                <h4 class="font-medium text-gray-900 mb-2">Erfolgskriterien</h4>
                                <ul class="list-disc list-inside text-gray-700 space-y-1 text-sm">
                                    <li v-for="criteria in drill.success_criteria" :key="criteria">
                                        {{ criteria }}
                                    </li>
                                </ul>
                            </div>

                            <div v-if="drill.tags?.length">
                                <h4 class="font-medium text-gray-900 mb-2">Tags</h4>
                                <div class="flex flex-wrap gap-1">
                                    <span 
                                        v-for="tag in drill.tags" 
                                        :key="tag"
                                        class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Variations and Progressions -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-if="drill.variations">
                                <h4 class="font-medium text-gray-900 mb-2">Variationen</h4>
                                <div class="text-gray-700 text-sm whitespace-pre-wrap">{{ drill.variations }}</div>
                            </div>

                            <div v-if="drill.progressions">
                                <h4 class="font-medium text-gray-900 mb-2">Steigerungen</h4>
                                <div class="text-gray-700 text-sm whitespace-pre-wrap">{{ drill.progressions }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    drill: Object,
    can: Object,
})

function getCategoryLabel(category) {
    const labels = {
        'ball_handling': 'Ballhandling',
        'shooting': 'Wurf',
        'passing': 'Passen',
        'defense': 'Verteidigung',
        'rebounding': 'Rebound',
        'conditioning': 'Kondition',
        'agility': 'Beweglichkeit',
        'footwork': 'Beinarbeit',
        'team_offense': 'Team-Offense',
        'team_defense': 'Team-Defense',
        'transition': 'Transition',
        'set_plays': 'Spielzüge',
        'scrimmage': 'Scrimmage',
        'warm_up': 'Aufwärmen',
        'cool_down': 'Abwärmen',
    }
    return labels[category] || category
}

function getDifficultyLabel(difficulty) {
    const labels = {
        'beginner': 'Anfänger',
        'intermediate': 'Fortgeschritten',
        'advanced': 'Fortgeschritten',
        'expert': 'Experte'
    }
    return labels[difficulty] || difficulty
}

function getDifficultyClasses(difficulty) {
    const classes = {
        'beginner': 'bg-green-100 text-green-800',
        'intermediate': 'bg-yellow-100 text-yellow-800',
        'advanced': 'bg-orange-100 text-orange-800',
        'expert': 'bg-red-100 text-red-800'
    }
    return classes[difficulty] || 'bg-gray-100 text-gray-800'
}

function getStatusLabel(status) {
    const labels = {
        'draft': 'Entwurf',
        'pending_review': 'Zur Überprüfung',
        'approved': 'Genehmigt',
        'rejected': 'Abgelehnt',
        'archived': 'Archiviert'
    }
    return labels[status] || status
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

function editDrill() {
    router.get(`/training/drills/${props.drill.id}/edit`)
}

function goBack() {
    router.get('/training/drills')
}
</script>