<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    preview: Object,
});

const fileInput = ref(null);
const uploadForm = useForm({
    file: null,
});

const handleFileChange = (event) => {
    uploadForm.file = event.target.files[0];
};

const uploadFile = () => {
    if (!uploadForm.file) {
        alert('Bitte wählen Sie eine Datei aus.');
        return;
    }

    uploadForm.post(route('admin.users.import.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset file input
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            uploadForm.reset();
        },
    });
};

const executeImport = () => {
    if (!confirm('Möchten Sie die ' + props.preview.valid + ' validen Benutzer wirklich importieren?')) {
        return;
    }

    router.post(route('admin.users.import.execute'), {}, {
        preserveScroll: true,
    });
};

const cancelImport = () => {
    router.post(route('admin.users.import.cancel'));
};

const downloadTemplate = () => {
    window.location.href = route('admin.users.import.template');
};
</script>

<template>
    <AdminLayout title="Benutzer importieren">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Benutzer importieren
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Mehrere Benutzer gleichzeitig via CSV oder Excel importieren
                    </p>
                </div>

                <SecondaryButton :href="route('admin.users')" as="Link">
                    Zurück zur Benutzer-Liste
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Anleitung zum Import</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ol class="list-decimal list-inside space-y-1">
                                    <li>Laden Sie die Vorlage herunter und füllen Sie sie mit Ihren Daten</li>
                                    <li>Laden Sie die ausgefüllte CSV- oder Excel-Datei hoch</li>
                                    <li>Prüfen Sie die Vorschau und beheben Sie eventuelle Fehler</li>
                                    <li>Führen Sie den Import aus</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Upload -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-6 py-5">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            1. Datei hochladen
                        </h3>

                        <div class="flex items-center space-x-4 mb-4">
                            <SecondaryButton @click="downloadTemplate">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Vorlage herunterladen
                            </SecondaryButton>

                            <span class="text-sm text-gray-500">
                                (CSV-Format mit Beispieldaten)
                            </span>
                        </div>

                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                            <div class="space-y-4">
                                <div>
                                    <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
                                        Wählen Sie eine Datei aus
                                    </label>
                                    <input
                                        id="file-upload"
                                        ref="fileInput"
                                        type="file"
                                        accept=".csv,.xlsx,.xls"
                                        @change="handleFileChange"
                                        class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-indigo-50 file:text-indigo-700
                                            hover:file:bg-indigo-100"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Unterstützte Formate: CSV, XLSX, XLS (Max. 10MB)
                                    </p>
                                </div>

                                <PrimaryButton
                                    @click="uploadFile"
                                    :disabled="!uploadForm.file || uploadForm.processing"
                                    :class="{ 'opacity-25': !uploadForm.file || uploadForm.processing }"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Datei hochladen & validieren
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview (shown after upload) -->
                <div v-if="preview" class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-6 py-5">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            2. Vorschau & Validierung
                        </h3>

                        <!-- Summary -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-500">Gesamt</div>
                                <div class="text-2xl font-bold text-gray-900">{{ preview.total }}</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="text-sm text-green-600">Valide</div>
                                <div class="text-2xl font-bold text-green-700">{{ preview.valid }}</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="text-sm text-red-600">Fehler</div>
                                <div class="text-2xl font-bold text-red-700">{{ preview.invalid }}</div>
                            </div>
                        </div>

                        <!-- Invalid Rows -->
                        <div v-if="preview.invalid > 0" class="mb-6">
                            <h4 class="text-md font-medium text-red-700 mb-3">
                                Fehlerhafte Zeilen ({{ preview.invalid }})
                            </h4>
                            <div class="border border-red-200 rounded-lg overflow-hidden">
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-red-200">
                                        <thead class="bg-red-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700">Zeile</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700">Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700">E-Mail</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700">Fehler</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-red-100">
                                            <tr v-for="row in preview.invalid_rows" :key="row.row_number">
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.row_number }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.data.name || '-' }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.data.email || '-' }}</td>
                                                <td class="px-4 py-2 text-sm">
                                                    <ul class="list-disc list-inside text-red-600">
                                                        <li v-for="(error, index) in row.errors" :key="index">{{ error }}</li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Valid Rows Preview -->
                        <div v-if="preview.valid > 0" class="mb-6">
                            <h4 class="text-md font-medium text-green-700 mb-3">
                                Valide Zeilen ({{ preview.valid }}) - Erste 10 Einträge
                            </h4>
                            <div class="border border-green-200 rounded-lg overflow-hidden">
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-green-200">
                                        <thead class="bg-green-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-green-700">Zeile</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-green-700">Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-green-700">E-Mail</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-green-700">Rollen</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-green-100">
                                            <tr v-for="row in preview.valid_rows.slice(0, 10)" :key="row.row_number">
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.row_number }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.data.name }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.data.email }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ row.data.roles }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <p v-if="preview.valid > 10" class="mt-2 text-sm text-gray-500">
                                Und {{ preview.valid - 10 }} weitere...
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <DangerButton @click="cancelImport">
                                Abbrechen
                            </DangerButton>

                            <PrimaryButton
                                v-if="preview.valid > 0"
                                @click="executeImport"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ preview.valid }} Benutzer importieren
                            </PrimaryButton>
                            <span v-else class="text-sm text-gray-500">
                                Keine validen Daten zum Importieren
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
