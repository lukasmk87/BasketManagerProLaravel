<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClubAdminLayout from '@/Layouts/ClubAdminLayout.vue';

const props = defineProps({
    club: Object,
    categories: Object,
    types: Array,
});

const form = useForm({
    type: 'expense',
    category: '',
    amount: '',
    currency: 'EUR',
    description: '',
    transaction_date: new Date().toISOString().split('T')[0],
    reference_number: '',
});

const submit = () => {
    form.post(route('club-admin.financial.store'));
};
</script>

<template>
    <ClubAdminLayout title="Neue Transaktion">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Neue Transaktion erstellen
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Typ *</label>
                            <div class="mt-2 flex gap-4">
                                <label
                                    v-for="type in types"
                                    :key="type.value"
                                    class="flex items-center"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.type"
                                        :value="type.value"
                                        class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">{{ type.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategorie *</label>
                            <select
                                v-model="form.category"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Bitte wählen...</option>
                                <option v-for="(label, key) in categories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>

                        <!-- Amount -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Betrag *</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        v-model="form.amount"
                                        step="0.01"
                                        min="0.01"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="0.00"
                                    />
                                </div>
                                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Währung</label>
                                <select
                                    v-model="form.currency"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="EUR">EUR</option>
                                    <option value="CHF">CHF</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>

                        <!-- Transaction Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Datum *</label>
                            <input
                                type="date"
                                v-model="form.transaction_date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="form.errors.transaction_date" class="mt-1 text-sm text-red-600">{{ form.errors.transaction_date }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optionale Beschreibung der Transaktion..."
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- Reference Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Referenznummer</label>
                            <input
                                type="text"
                                v-model="form.reference_number"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="z.B. Rechnungsnummer"
                            />
                            <p v-if="form.errors.reference_number" class="mt-1 text-sm text-red-600">{{ form.errors.reference_number }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('club-admin.financial.index')"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Abbrechen
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                Transaktion erstellen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ClubAdminLayout>
</template>
