<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { ArrowDownTrayIcon, DocumentTextIcon, TableCellsIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    season: {
        type: Object,
        required: true
    },
    club: {
        type: Object,
        required: true
    },
    canExport: {
        type: Boolean,
        default: false
    }
});

const exporting = ref(false);
const exportFormat = ref(null);

const handleExportPDF = () => {
    if (!props.canExport || exporting.value) return;

    exporting.value = true;
    exportFormat.value = 'pdf';

    router.post(route('club.seasons.export', {
        club: props.club.id,
        season: props.season.id
    }), {
        format: 'pdf',
        include_games: true,
        include_players: true
    }, {
        onFinish: () => {
            exporting.value = false;
            exportFormat.value = null;
        }
    });
};

const handleExportCSV = () => {
    if (!props.canExport || exporting.value) return;

    exporting.value = true;
    exportFormat.value = 'csv';

    // Client-side CSV generation
    const metrics = [
        { key: 'name', label: 'Saison' },
        { key: 'start_date', label: 'Start' },
        { key: 'end_date', label: 'Ende' },
        { key: 'teams_count', label: 'Teams' },
        { key: 'games_count', label: 'Spiele' },
        { key: 'players_count', label: 'Spieler' },
        { key: 'avg_score', label: 'Durchschnittliche Punkte' },
        { key: 'avg_assists', label: 'Durchschnittliche Assists' },
        { key: 'avg_rebounds', label: 'Durchschnittliche Rebounds' },
    ];

    // Headers
    let csvContent = metrics.map(m => m.label).join(',') + '\n';

    // Data row
    const row = metrics.map(metric => {
        let value = props.season[metric.key] || '';

        // Format dates
        if (metric.key === 'start_date' || metric.key === 'end_date') {
            value = new Date(value).toLocaleDateString('de-DE');
        }

        // Format decimals
        if (['avg_score', 'avg_assists', 'avg_rebounds'].includes(metric.key) && value) {
            value = parseFloat(value).toFixed(1);
        }

        // Escape commas
        if (typeof value === 'string' && value.includes(',')) {
            value = `"${value}"`;
        }

        return value;
    });

    csvContent += row.join(',') + '\n';

    // Download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `saison-${props.season.name}-statistiken.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    exporting.value = false;
    exportFormat.value = null;
};
</script>

<template>
    <Menu as="div" class="relative inline-block text-left">
        <MenuButton
            :disabled="!canExport || exporting"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
            <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
            <span v-if="exporting">Exportiere...</span>
            <span v-else>Exportieren</span>
        </MenuButton>

        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="py-1">
                    <MenuItem v-slot="{ active }">
                        <button
                            type="button"
                            @click="handleExportPDF"
                            :disabled="exporting"
                            :class="[
                                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                                exporting && exportFormat === 'pdf' ? 'opacity-50' : '',
                                'group flex w-full items-center px-4 py-2 text-sm disabled:cursor-not-allowed'
                            ]"
                        >
                            <DocumentTextIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" />
                            <div class="flex-1 text-left">
                                <div class="font-medium">PDF Export</div>
                                <div class="text-xs text-gray-500">
                                    Vollständiger Bericht mit Statistiken
                                </div>
                            </div>
                        </button>
                    </MenuItem>

                    <MenuItem v-slot="{ active }">
                        <button
                            type="button"
                            @click="handleExportCSV"
                            :disabled="exporting"
                            :class="[
                                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                                exporting && exportFormat === 'csv' ? 'opacity-50' : '',
                                'group flex w-full items-center px-4 py-2 text-sm disabled:cursor-not-allowed'
                            ]"
                        >
                            <TableCellsIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" />
                            <div class="flex-1 text-left">
                                <div class="font-medium">CSV Export</div>
                                <div class="text-xs text-gray-500">
                                    Daten für Excel/Numbers
                                </div>
                            </div>
                        </button>
                    </MenuItem>
                </div>

                <div class="border-t border-gray-100 py-1">
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-500">
                            Der Export enthält alle Saison-Statistiken und kann zur weiteren Analyse verwendet werden.
                        </p>
                    </div>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
