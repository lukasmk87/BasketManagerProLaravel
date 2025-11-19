<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import {
    EyeIcon,
    PencilIcon,
    PlayIcon,
    CheckCircleIcon,
    ArrowDownTrayIcon,
    ArrowsRightLeftIcon,
    TrashIcon,
    EllipsisVerticalIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    club: {
        type: Object,
        required: true
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits([
    'view',
    'edit',
    'activate',
    'complete',
    'export',
    'compare',
    'delete'
]);

const canView = computed(() => true); // Alle können ansehen

const canEdit = computed(() => {
    return props.permissions?.canEdit ||
           props.permissions?.includes?.('manage seasons') ||
           false;
});

const canActivate = computed(() => {
    return props.season.status === 'draft' && canEdit.value;
});

const canComplete = computed(() => {
    return props.season.status === 'active' &&
           (props.permissions?.canComplete ||
            props.permissions?.includes?.('complete seasons') ||
            false);
});

const canExport = computed(() => {
    return props.permissions?.canExport ||
           props.permissions?.includes?.('export season statistics') ||
           false;
});

const canCompare = computed(() => {
    return props.permissions?.canCompare ||
           props.permissions?.includes?.('compare seasons') ||
           false;
});

const canDelete = computed(() => {
    return props.season.status !== 'active' && canEdit.value;
});

const handleView = () => {
    emit('view', props.season);
    router.visit(route('club.seasons.show', {
        club: props.club.id,
        season: props.season.id
    }));
};

const handleEdit = () => {
    emit('edit', props.season);
    router.visit(route('club.seasons.edit', {
        club: props.club.id,
        season: props.season.id
    }));
};

const handleActivate = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich aktivieren? Dies deaktiviert alle anderen Saisons.`)) {
        emit('activate', props.season);
        router.post(route('club.seasons.activate', {
            club: props.club.id,
            season: props.season.id
        }), {}, {
            preserveScroll: true
        });
    }
};

const handleComplete = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich abschließen? Dies erstellt einen Statistik-Snapshot.`)) {
        emit('complete', props.season);
        router.post(route('club.seasons.complete', {
            club: props.club.id,
            season: props.season.id
        }), {
            create_snapshots: true
        }, {
            preserveScroll: true
        });
    }
};

const handleExport = () => {
    emit('export', props.season);
    // Export-Funktionalität wird in Phase 2.5 implementiert
    alert('Export-Funktion wird in Kürze verfügbar sein');
};

const handleCompare = () => {
    emit('compare', props.season);
    router.visit(route('club.seasons.compare', {
        club: props.club.id,
        ids: [props.season.id]
    }));
};

const handleDelete = () => {
    if (confirm(`Möchten Sie die Saison "${props.season.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!`)) {
        emit('delete', props.season);
        router.delete(route('club.seasons.destroy', {
            club: props.club.id,
            season: props.season.id
        }), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Dropdown align="right" width="48">
        <template #trigger>
            <button
                type="button"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150"
            >
                <EllipsisVerticalIcon class="h-5 w-5" />
            </button>
        </template>

        <template #content>
            <!-- View -->
            <button
                v-if="canView"
                type="button"
                @click="handleView"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out flex items-center"
            >
                <EyeIcon class="h-4 w-4 mr-2 text-gray-500" />
                Anzeigen
            </button>

            <!-- Edit -->
            <button
                v-if="canEdit"
                type="button"
                @click="handleEdit"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out flex items-center"
            >
                <PencilIcon class="h-4 w-4 mr-2 text-gray-500" />
                Bearbeiten
            </button>

            <!-- Divider -->
            <div v-if="canEdit" class="border-t border-gray-100"></div>

            <!-- Activate -->
            <button
                v-if="canActivate"
                type="button"
                @click="handleActivate"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-green-700 hover:bg-green-50 focus:outline-none focus:bg-green-50 transition duration-150 ease-in-out flex items-center"
            >
                <PlayIcon class="h-4 w-4 mr-2 text-green-500" />
                Aktivieren
            </button>

            <!-- Complete -->
            <button
                v-if="canComplete"
                type="button"
                @click="handleComplete"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-blue-700 hover:bg-blue-50 focus:outline-none focus:bg-blue-50 transition duration-150 ease-in-out flex items-center"
            >
                <CheckCircleIcon class="h-4 w-4 mr-2 text-blue-500" />
                Abschließen
            </button>

            <!-- Divider -->
            <div v-if="canExport || canCompare" class="border-t border-gray-100"></div>

            <!-- Export -->
            <button
                v-if="canExport"
                type="button"
                @click="handleExport"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out flex items-center"
            >
                <ArrowDownTrayIcon class="h-4 w-4 mr-2 text-gray-500" />
                Exportieren
            </button>

            <!-- Compare -->
            <button
                v-if="canCompare"
                type="button"
                @click="handleCompare"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out flex items-center"
            >
                <ArrowsRightLeftIcon class="h-4 w-4 mr-2 text-gray-500" />
                Vergleichen
            </button>

            <!-- Divider -->
            <div v-if="canDelete" class="border-t border-gray-100"></div>

            <!-- Delete -->
            <button
                v-if="canDelete"
                type="button"
                @click="handleDelete"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-700 hover:bg-red-50 focus:outline-none focus:bg-red-50 transition duration-150 ease-in-out flex items-center"
            >
                <TrashIcon class="h-4 w-4 mr-2 text-red-500" />
                Löschen
            </button>
        </template>
    </Dropdown>
</template>
