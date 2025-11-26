<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    plan: Object,
    clubs: Object,
});

const showDeleteModal = ref(false);

const form = useForm({});

const clonePlan = () => {
    form.post(route('admin.club-plans.clone', props.plan.id));
};

const deletePlan = () => {
    form.delete(route('admin.club-plans.destroy', props.plan.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};

const formatPrice = (price) => {
    const priceValue = typeof price === 'number' ? price : parseFloat(price) || 0;
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(priceValue);
};

const formatLimit = (value) => {
    if (value === -1 || value === null || value === undefined) {
        return 'Unbegrenzt';
    }
    return new Intl.NumberFormat('de-DE').format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};
</script>

<template>
    <AdminLayout :title="plan.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <div
                            v-if="plan.color"
                            class="w-4 h-4 rounded-full"
                            :style="{ backgroundColor: plan.color }"
                        ></div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ plan.name }}
                        </h2>
                        <span
                            v-if="plan.is_active"
                            class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800"
                        >
                            Aktiv
                        </span>
                        <span
                            v-else
                            class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800"
                        >
                            Inaktiv
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ plan.slug }} | {{ plan.tenant?.name || 'Unbekannter Tenant' }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.club-plans.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Zurück
                    </Link>
                    <SecondaryButton @click="clonePlan" :disabled="form.processing">
                        Klonen
                    </SecondaryButton>
                    <Link
                        :href="route('admin.club-plans.edit', plan.id)"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Preis</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ formatPrice(plan.price) }}
                            </dd>
                            <dd class="text-xs text-gray-500">{{ plan.billing_interval_label }}</dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Clubs</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ plan.clubs_count || 0 }}
                            </dd>
                            <dd class="text-xs text-gray-500">mit diesem Plan</dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Trial</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ plan.trial_period_days || 0 }}
                            </dd>
                            <dd class="text-xs text-gray-500">Tage kostenlos</dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <dt class="text-sm font-medium text-gray-500 truncate">Stripe</dt>
                            <dd class="mt-1 text-2xl font-semibold" :class="plan.is_stripe_synced ? 'text-green-600' : 'text-gray-400'">
                                {{ plan.is_stripe_synced ? 'Synced' : 'Nicht synced' }}
                            </dd>
                            <dd v-if="plan.last_stripe_sync_at" class="text-xs text-gray-500">
                                {{ formatDate(plan.last_stripe_sync_at) }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Plan Details -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Plan Details</h3>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <div v-if="plan.description">
                            <p class="text-sm font-medium text-gray-500">Beschreibung</p>
                            <p class="mt-1 text-base text-gray-900">{{ plan.description }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Tenant</p>
                                <p class="mt-1 text-base text-gray-900">{{ plan.tenant?.name || 'Unbekannt' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Erstellt am</p>
                                <p class="mt-1 text-base text-gray-900">{{ formatDate(plan.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limits -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Limits</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div v-if="plan.limits?.max_teams !== undefined">
                                <dt class="text-sm font-medium text-gray-500">Max Teams</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ formatLimit(plan.limits.max_teams) }}
                                </dd>
                            </div>
                            <div v-if="plan.limits?.max_players !== undefined">
                                <dt class="text-sm font-medium text-gray-500">Max Spieler</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ formatLimit(plan.limits.max_players) }}
                                </dd>
                            </div>
                            <div v-if="plan.limits?.max_storage_gb !== undefined">
                                <dt class="text-sm font-medium text-gray-500">Storage</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ formatLimit(plan.limits.max_storage_gb) }} GB
                                </dd>
                            </div>
                            <div v-if="plan.limits?.max_games_per_month !== undefined">
                                <dt class="text-sm font-medium text-gray-500">Spiele / Monat</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ formatLimit(plan.limits.max_games_per_month) }}
                                </dd>
                            </div>
                            <div v-if="plan.limits?.max_training_sessions_per_month !== undefined">
                                <dt class="text-sm font-medium text-gray-500">Trainings / Monat</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ formatLimit(plan.limits.max_training_sessions_per_month) }}
                                </dd>
                            </div>
                            <div v-if="plan.limits?.max_api_calls_per_hour !== undefined">
                                <dt class="text-sm font-medium text-gray-500">API Calls / Stunde</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ formatLimit(plan.limits.max_api_calls_per_hour) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Features -->
                <div v-if="plan.features && plan.features.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Features</h3>
                    </div>
                    <div class="px-6 py-5">
                        <ul class="space-y-3">
                            <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start">
                                <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-base text-gray-900">{{ feature }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Clubs using this plan -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Clubs ({{ plan.clubs_count || 0 }})</h3>
                        <p class="mt-1 text-sm text-gray-500">Clubs die diesen Plan nutzen</p>
                    </div>
                    <div class="px-6 py-5">
                        <div v-if="clubs?.data && clubs.data.length > 0" class="space-y-4">
                            <ul class="divide-y divide-gray-200">
                                <li v-for="club in clubs.data" :key="club.id" class="py-4 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ club.name }}</p>
                                        <p class="text-sm text-gray-500">{{ club.slug }}</p>
                                    </div>
                                </li>
                            </ul>
                            <Pagination v-if="clubs.links" :links="clubs.links" />
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-500">Keine Clubs nutzen diesen Plan.</p>
                        </div>
                    </div>
                </div>

                <!-- Delete Section -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-red-50">
                        <h3 class="text-lg font-semibold text-red-900">Gefahrenzone</h3>
                    </div>
                    <div class="px-6 py-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Plan löschen</p>
                            <p class="text-sm text-gray-500">
                                Diese Aktion kann nicht rückgängig gemacht werden.
                            </p>
                        </div>
                        <DangerButton
                            @click="showDeleteModal = true"
                            :disabled="(plan.clubs_count || 0) > 0"
                        >
                            Plan löschen
                        </DangerButton>
                    </div>
                    <p v-if="(plan.clubs_count || 0) > 0" class="px-6 pb-5 text-sm text-red-600">
                        Plan kann nicht gelöscht werden - es gibt noch {{ plan.clubs_count }} Club(s) mit diesem Plan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
            <template #title>
                Plan löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie den Plan "{{ plan.name }}" löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>

            <template #footer>
                <SecondaryButton @click="showDeleteModal = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deletePlan"
                >
                    Plan löschen
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AdminLayout>
</template>
