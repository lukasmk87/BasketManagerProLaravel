<script setup>
import { Link, router } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    club: Object,
    transaction: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: props.transaction?.currency || 'EUR',
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
};

const deleteTransaction = () => {
    if (confirm('Möchten Sie diese Transaktion wirklich löschen?')) {
        router.delete(route('club-admin.financial.destroy', props.transaction.id));
    }
};
</script>

<template>
    <ClubAdminLayout title="Transaktionsdetails">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Transaktionsdetails
                </h2>
                <Link
                    :href="route('club-admin.financial.index')"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    ← Zurück zur Übersicht
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <span
                                :class="[
                                    'px-3 py-1 text-sm font-semibold rounded-full',
                                    transaction.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ transaction.type === 'income' ? 'Einnahme' : 'Ausgabe' }}
                            </span>
                            <span
                                class="text-2xl font-bold"
                                :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ transaction.type === 'income' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Kategorie</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transaction.category_label }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Datum</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(transaction.transaction_date) }}</dd>
                            </div>
                            <div v-if="transaction.reference_number">
                                <dt class="text-sm font-medium text-gray-500">Referenznummer</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transaction.reference_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt von</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ transaction.created_by || '-' }}</dd>
                            </div>
                        </div>

                        <div v-if="transaction.description">
                            <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ transaction.description }}</dd>
                        </div>

                        <div class="pt-4 border-t flex justify-end gap-3">
                            <button
                                @click="deleteTransaction"
                                class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50"
                            >
                                Löschen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
