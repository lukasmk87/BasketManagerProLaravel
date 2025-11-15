<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TransferStatusBadge from './Partials/TransferStatusBadge.vue';
import TransferTimeline from './Partials/TransferTimeline.vue';

const props = defineProps({
    transfer: {
        type: Object,
        required: true,
    },
});

const showRollbackModal = ref(false);
const processing = ref(false);

const performRollback = () => {
    processing.value = true;

    router.post(route('admin.club-transfers.rollback', props.transfer.id), {}, {
        onSuccess: () => {
            showRollbackModal.value = false;
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(dateString));
};

const getRollbackTimeRemaining = () => {
    if (!props.transfer.rollback_expires_at) return '';

    const now = new Date();
    const expiresAt = new Date(props.transfer.rollback_expires_at);
    const diff = expiresAt - now;

    if (diff <= 0) return 'Abgelaufen';

    const hours = Math.floor(diff / 1000 / 60 / 60);
    const minutes = Math.floor((diff / 1000 / 60) % 60);

    return `${hours}h ${minutes}m verbleibend`;
};
</script>

<template>
    <AdminLayout :title="`Transfer: ${transfer.club.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Transfer: {{ transfer.club.name }}
                        </h2>
                        <TransferStatusBadge :status="transfer.status" />
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        Transfer-ID: {{ transfer.id }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.club-transfers.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Zurück zur Übersicht
                    </Link>

                    <DangerButton
                        v-if="transfer.can_rollback"
                        @click="showRollbackModal = true"
                    >
                        Rollback durchführen
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Transfer Overview -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Transfer-Übersicht
                        </h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <!-- Club -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Club</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transfer.club.name }}</dd>
                            </div>

                            <!-- Source Tenant -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Von Tenant</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transfer.source_tenant.name }}</dd>
                            </div>

                            <!-- Target Tenant -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nach Tenant</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transfer.target_tenant.name }}</dd>
                            </div>

                            <!-- Initiated By -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Initiiert von</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transfer.initiated_by.name }}</dd>
                            </div>

                            <!-- Started At -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Gestartet am</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(transfer.started_at) }}</dd>
                            </div>

                            <!-- Completed At -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Abgeschlossen am</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ formatDate(transfer.completed_at || transfer.failed_at || transfer.rolled_back_at) }}
                                </dd>
                            </div>

                            <!-- Duration -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dauer</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transfer.duration || 'Läuft noch...' }}</dd>
                            </div>

                            <!-- Created At -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(transfer.created_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Rollback Information -->
                <div v-if="transfer.can_rollback || transfer.rollback_expires_at" class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-yellow-50 to-yellow-100">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Rollback-Information
                        </h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Rollback möglich</dt>
                                <dd class="mt-1 text-sm">
                                    <span v-if="transfer.can_rollback" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Ja
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Nein
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Rollback läuft ab</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ formatDate(transfer.rollback_expires_at) }}
                                    <span v-if="transfer.can_rollback" class="ml-2 text-xs text-yellow-600">
                                        ({{ getRollbackTimeRemaining() }})
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Metadata -->
                <div v-if="transfer.metadata" class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Transfer-Details
                        </h3>
                    </div>
                    <div class="px-6 py-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Transferred Data -->
                            <div class="bg-green-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-green-900 mb-3">Übertragene Daten</h4>
                                <dl class="space-y-2 text-sm">
                                    <div v-if="transfer.metadata.data_to_transfer">
                                        <div v-for="(value, key) in transfer.metadata.data_to_transfer" :key="key" class="flex justify-between">
                                            <dt class="text-green-700">{{ key }}:</dt>
                                            <dd class="font-medium text-green-900">{{ value }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>

                            <!-- Removed Data -->
                            <div class="bg-red-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-red-900 mb-3">Entfernte Daten</h4>
                                <dl class="space-y-2 text-sm">
                                    <div v-if="transfer.metadata.data_to_remove">
                                        <div v-for="(value, key) in transfer.metadata.data_to_remove" :key="key" class="flex justify-between">
                                            <dt class="text-red-700">{{ key }}:</dt>
                                            <dd class="font-medium text-red-900">{{ value }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>

                            <!-- Warnings -->
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-yellow-900 mb-3">Warnungen</h4>
                                <ul v-if="transfer.metadata.warnings" class="space-y-2">
                                    <li
                                        v-for="(warning, index) in transfer.metadata.warnings"
                                        :key="index"
                                        class="text-sm text-yellow-800"
                                    >
                                        <span class="font-medium">{{ warning.type }}:</span>
                                        {{ warning.message }}
                                    </li>
                                </ul>
                                <p v-else class="text-sm text-yellow-700">Keine Warnungen</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transfer Timeline -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-indigo-100">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Transfer-Verlauf
                        </h3>
                    </div>
                    <div class="px-6 py-5">
                        <TransferTimeline v-if="transfer.logs.length > 0" :logs="transfer.logs" />
                        <p v-else class="text-sm text-gray-500">Keine Log-Einträge vorhanden.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Rollback Confirmation Modal -->
        <ConfirmationModal :show="showRollbackModal" @close="showRollbackModal = false">
            <template #title>
                Transfer rückgängig machen
            </template>

            <template #content>
                <p class="text-sm text-gray-600">
                    Sind Sie sicher, dass Sie diesen Transfer rückgängig machen möchten?
                </p>
                <p class="mt-2 text-sm text-gray-600">
                    Der Club <strong>{{ transfer.club.name }}</strong> wird zurück zum Tenant <strong>{{ transfer.source_tenant.name }}</strong> transferiert.
                </p>
                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-3">
                    <p class="text-sm text-yellow-800">
                        <strong>Hinweis:</strong> Diese Aktion stellt den ursprünglichen Zustand wieder her, einschließlich User-Memberships und Stripe-Subscription.
                    </p>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showRollbackModal = false" :disabled="processing">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    @click="performRollback"
                    :disabled="processing"
                >
                    <span v-if="processing">Rollback läuft...</span>
                    <span v-else>Rollback durchführen</span>
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AdminLayout>
</template>
