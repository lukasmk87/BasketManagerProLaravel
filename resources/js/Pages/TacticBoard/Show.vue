<template>
    <AppLayout :title="play.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tactic-board.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ play.name }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                :class="[
                                    'px-2 py-0.5 text-xs font-semibold rounded',
                                    getStatusClass(play.status)
                                ]"
                            >
                                {{ getStatusLabel(play.status) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ getCategoryLabel(play.category) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="exportPng"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    >
                        <PhotoIcon class="h-4 w-4 mr-2" />
                        PNG
                    </button>
                    <button
                        @click="exportPdf"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    >
                        <DocumentIcon class="h-4 w-4 mr-2" />
                        PDF
                    </button>
                    <Link
                        v-if="canEdit"
                        :href="route('tactic-board.plays.edit', play.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                    >
                        <PencilIcon class="h-4 w-4 mr-2" />
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content - Play Viewer -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <TacticBoardViewer
                                ref="viewerRef"
                                :playData="play.play_data"
                                :courtType="play.court_type"
                            />
                        </div>
                    </div>

                    <!-- Sidebar - Play Info -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Details -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Details
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Spielfeld
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ getCourtTypeLabel(play.court_type) }}
                                    </p>
                                </div>

                                <div v-if="play.description">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Beschreibung
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ play.description }}
                                    </p>
                                </div>

                                <div v-if="play.tags && play.tags.length > 0">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                        Tags
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="tag in play.tags"
                                            :key="tag"
                                            class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Erstellt von
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ play.created_by?.name || 'Unbekannt' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Erstellt am
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ formatDate(play.created_at) }}
                                    </p>
                                </div>

                                <div v-if="play.usage_count > 0">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Verwendet
                                    </label>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ play.usage_count }}x
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Connected Playbooks -->
                        <div v-if="play.playbooks && play.playbooks.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                In Playbooks
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="playbook in play.playbooks" :key="playbook.id">
                                    <Link
                                        :href="route('tactic-board.playbooks.show', playbook.id)"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        {{ playbook.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Connected Drills -->
                        <div v-if="play.drills && play.drills.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Verknüpfte Übungen
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="drill in play.drills" :key="drill.id">
                                    <Link
                                        :href="route('training.drills.show', drill.id)"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        {{ drill.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div v-if="canEdit" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Aktionen
                            </h3>
                            <div class="space-y-2">
                                <button
                                    @click="duplicatePlay"
                                    class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left"
                                >
                                    Duplizieren
                                </button>
                                <button
                                    v-if="play.status === 'published'"
                                    @click="archivePlay"
                                    class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left"
                                >
                                    Archivieren
                                </button>
                                <button
                                    v-if="play.status === 'draft'"
                                    @click="publishPlay"
                                    class="w-full px-4 py-2 bg-green-100 dark:bg-green-800 rounded-md text-sm text-green-700 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-700 transition text-left"
                                >
                                    Veröffentlichen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TacticBoardViewer from '@/Components/TacticBoard/TacticBoardViewer.vue';
import {
    ArrowLeftIcon,
    PencilIcon,
    PhotoIcon,
    DocumentIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    play: Object,
    canEdit: Boolean,
});

const viewerRef = ref(null);

// Categories and court types labels
const categoryLabels = {
    offense: 'Offense',
    defense: 'Defense',
    inbound: 'Einwurf',
    fast_break: 'Fast Break',
    press_break: 'Press Break',
    zone_offense: 'Zonenoffense',
    zone_defense: 'Zonendefense',
    out_of_bounds: 'Out of Bounds',
    special: 'Speziell',
};

const courtTypeLabels = {
    half_horizontal: 'Halbes Feld (horizontal)',
    full: 'Ganzes Feld',
    half_vertical: 'Halbes Feld (vertikal)',
};

// Export functions
const exportPng = async () => {
    if (viewerRef.value && viewerRef.value.exportPng) {
        await viewerRef.value.exportPng();
    } else {
        window.open(`/api/plays/${props.play.id}/export/png`, '_blank');
    }
};

const exportPdf = () => {
    window.open(`/api/plays/${props.play.id}/export/pdf`, '_blank');
};

// Actions
const duplicatePlay = () => {
    if (confirm('Möchten Sie diesen Spielzug duplizieren?')) {
        router.post(`/api/plays/${props.play.id}/duplicate`, {}, {
            onSuccess: (page) => {
                // Redirect to the new play if available
                if (page.props.play?.id) {
                    router.visit(route('tactic-board.plays.edit', page.props.play.id));
                }
            },
        });
    }
};

const publishPlay = () => {
    if (confirm('Möchten Sie diesen Spielzug veröffentlichen?')) {
        router.post(`/api/plays/${props.play.id}/publish`, {}, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

const archivePlay = () => {
    if (confirm('Möchten Sie diesen Spielzug archivieren?')) {
        router.post(`/api/plays/${props.play.id}/archive`, {}, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

// Helper functions
const getStatusClass = (status) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'archived':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'published': return 'Veröffentlicht';
        case 'draft': return 'Entwurf';
        case 'archived': return 'Archiviert';
        default: return status;
    }
};

const getCategoryLabel = (category) => {
    return categoryLabels[category] || category;
};

const getCourtTypeLabel = (courtType) => {
    return courtTypeLabels[courtType] || courtType;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
