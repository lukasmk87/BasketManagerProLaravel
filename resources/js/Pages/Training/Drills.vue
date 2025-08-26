<template>
    <AppLayout title="Trainingsübungen">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Trainingsübungen
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton 
                        :href="route('training.sessions')"
                        as="Link"
                    >
                        Zu den Trainingseinheiten
                    </SecondaryButton>
                    <PrimaryButton 
                        v-if="can?.create"
                        @click="createDrill"
                    >
                        Neue Übung
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filter Section -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Übung suchen..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="filters.category"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Kategorien</option>
                                    <option value="warmup">Aufwärmen</option>
                                    <option value="shooting">Wurf</option>
                                    <option value="dribbling">Dribbling</option>
                                    <option value="passing">Passen</option>
                                    <option value="defense">Verteidigung</option>
                                    <option value="offense">Angriff</option>
                                    <option value="conditioning">Kondition</option>
                                    <option value="teamplay">Teamspiel</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    v-model="filters.difficulty"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Schwierigkeiten</option>
                                    <option value="beginner">Anfänger</option>
                                    <option value="intermediate">Fortgeschritten</option>
                                    <option value="advanced">Experte</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    v-model="filters.age_group"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Alle Altersgruppen</option>
                                    <option value="u10">U10</option>
                                    <option value="u12">U12</option>
                                    <option value="u14">U14</option>
                                    <option value="u16">U16</option>
                                    <option value="u18">U18</option>
                                    <option value="senior">Senioren</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drills Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="drill in drills.data" 
                        :key="drill.id"
                        class="bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow"
                    >
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ drill.name }}
                                </h3>
                                <div class="flex gap-2">
                                    <span 
                                        v-if="drill.status"
                                        :class="getStatusClasses(drill.status)"
                                        class="px-2 py-1 text-xs rounded-full font-medium"
                                    >
                                        {{ getStatusLabel(drill.status) }}
                                    </span>
                                    <span 
                                        :class="getDifficultyClasses(drill.difficulty)"
                                        class="px-2 py-1 text-xs rounded-full font-medium"
                                    >
                                        {{ getDifficultyLabel(drill.difficulty) }}
                                    </span>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 mb-3 line-clamp-3">
                                {{ drill.description }}
                            </p>

                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span class="text-gray-600">{{ getCategoryLabel(drill.category) }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-gray-600">{{ drill.duration }} Min.</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="text-gray-600">{{ drill.min_players }}-{{ drill.max_players }} Spieler</span>
                                </div>

                                <div v-if="drill.equipment && drill.equipment.length" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span class="text-gray-600">{{ drill.equipment.join(', ') }}</span>
                                </div>
                            </div>

                            <div v-if="drill.tags && drill.tags.length" class="mt-3 flex flex-wrap gap-1">
                                <span 
                                    v-for="tag in drill.tags" 
                                    :key="tag"
                                    class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <div class="mt-4 flex justify-between items-center">
                                <div class="flex items-center gap-1">
                                    <svg 
                                        v-for="i in 5" 
                                        :key="i"
                                        class="w-4 h-4"
                                        :class="i <= drill.rating ? 'text-yellow-400' : 'text-gray-300'"
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="text-xs text-gray-500 ml-1">({{ drill.usage_count }})</span>
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        @click="viewDrill(drill)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        Details
                                    </button>
                                    <button
                                        v-if="can?.update"
                                        @click="editDrill(drill)"
                                        class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                                    >
                                        Bearbeiten
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="drills.data.length === 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Übungen gefunden</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Erstellen Sie Ihre erste Trainingsübung.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="drills.last_page > 1" class="mt-6 flex justify-center">
                    <nav class="flex gap-1">
                        <button
                            v-for="page in drills.last_page"
                            :key="page"
                            @click="changePage(page)"
                            :class="[
                                'px-3 py-1 rounded',
                                page === drills.current_page 
                                    ? 'bg-indigo-600 text-white' 
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    drills: Object,
    can: Object,
})

const search = ref('')
const filters = ref({
    category: '',
    difficulty: '',
    age_group: ''
})
const showCreateModal = ref(false)

function getCategoryLabel(category) {
    const labels = {
        'warmup': 'Aufwärmen',
        'shooting': 'Wurf',
        'dribbling': 'Dribbling',
        'passing': 'Passen',
        'defense': 'Verteidigung',
        'offense': 'Angriff',
        'conditioning': 'Kondition',
        'teamplay': 'Teamspiel'
    }
    return labels[category] || category
}

function getDifficultyLabel(difficulty) {
    const labels = {
        'beginner': 'Anfänger',
        'intermediate': 'Fortgeschritten',
        'advanced': 'Experte'
    }
    return labels[difficulty] || difficulty
}

function getDifficultyClasses(difficulty) {
    const classes = {
        'beginner': 'bg-green-100 text-green-800',
        'intermediate': 'bg-yellow-100 text-yellow-800',
        'advanced': 'bg-red-100 text-red-800'
    }
    return classes[difficulty] || 'bg-gray-100 text-gray-800'
}

function getStatusLabel(status) {
    const labels = {
        'draft': 'Entwurf',
        'pending_review': 'Zur Prüfung',
        'approved': 'Genehmigt',
        'rejected': 'Abgelehnt',
        'archived': 'Archiviert'
    }
    return labels[status] || status
}

function getStatusClasses(status) {
    const classes = {
        'draft': 'bg-orange-100 text-orange-800',
        'pending_review': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-blue-100 text-blue-800',
        'rejected': 'bg-red-100 text-red-800',
        'archived': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

function viewDrill(drill) {
    // Implementation for viewing drill details
    router.get(`/training/drills/${drill.id}`)
}

function editDrill(drill) {
    // Implementation for editing a drill
    router.get(`/training/drills/${drill.id}/edit`)
}

function createDrill() {
    router.get('/training/drills/create')
}

function changePage(page) {
    router.get('/training/drills', {
        search: search.value,
        ...filters.value,
        page: page
    }, {
        preserveState: true,
        preserveScroll: true
    })
}
</script>