<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    clubs: {
        type: Array,
        default: () => [],
    },
    tenants: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close', 'transferred']);

const activeTab = ref('select-clubs');
const clubSearch = ref('');
const selectedClubIds = ref([]);
const selectedTenantId = ref('');

const form = useForm({
    club_ids: [],
    target_tenant_id: '',
    confirmed: false,
});

const filteredClubs = computed(() => {
    if (!clubSearch.value) return props.clubs;
    const search = clubSearch.value.toLowerCase();
    return props.clubs.filter(club =>
        club.name.toLowerCase().includes(search)
    );
});

const selectedClubs = computed(() => {
    return props.clubs.filter(club => selectedClubIds.value.includes(club.id));
});

const selectedTenant = computed(() => {
    return props.tenants.find(t => t.id === selectedTenantId.value);
});

const toggleClub = (clubId) => {
    const index = selectedClubIds.value.indexOf(clubId);
    if (index > -1) {
        selectedClubIds.value.splice(index, 1);
    } else {
        selectedClubIds.value.push(clubId);
    }
};

const nextTab = () => {
    if (activeTab.value === 'select-clubs' && selectedClubIds.value.length > 0) {
        activeTab.value = 'select-tenant';
    } else if (activeTab.value === 'select-tenant' && selectedTenantId.value) {
        activeTab.value = 'confirm';
    }
};

const previousTab = () => {
    if (activeTab.value === 'confirm') {
        activeTab.value = 'select-tenant';
    } else if (activeTab.value === 'select-tenant') {
        activeTab.value = 'select-clubs';
    }
};

const submitBatchTransfer = () => {
    form.club_ids = selectedClubIds.value;
    form.target_tenant_id = selectedTenantId.value;

    form.post(route('admin.clubs.batch-transfer'), {
        onSuccess: () => {
            emit('transferred');
            closeModal();
        },
    });
};

const closeModal = () => {
    activeTab.value = 'select-clubs';
    clubSearch.value = '';
    selectedClubIds.value = [];
    selectedTenantId.value = '';
    form.reset();
    emit('close');
};

const canProceed = computed(() => {
    if (activeTab.value === 'select-clubs') {
        return selectedClubIds.value.length > 0;
    } else if (activeTab.value === 'select-tenant') {
        return !!selectedTenantId.value;
    } else if (activeTab.value === 'confirm') {
        return form.confirmed;
    }
    return false;
});
</script>

<template>
    <DialogModal :show="show" @close="closeModal" max-width="5xl">
        <template #title>
            Batch-Transfer
        </template>

        <template #content>
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'select-clubs'"
                        :class="[
                            activeTab === 'select-clubs'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        1. Clubs auswählen
                        <span v-if="selectedClubIds.length > 0" class="ml-2 bg-indigo-100 text-indigo-600 py-0.5 px-2 rounded-full text-xs">
                            {{ selectedClubIds.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'select-tenant'"
                        :disabled="selectedClubIds.length === 0"
                        :class="[
                            activeTab === 'select-tenant'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            selectedClubIds.length === 0 ? 'opacity-50 cursor-not-allowed' : '',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        2. Ziel-Tenant
                    </button>

                    <button
                        @click="activeTab = 'confirm'"
                        :disabled="selectedClubIds.length === 0 || !selectedTenantId"
                        :class="[
                            activeTab === 'confirm'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            (selectedClubIds.length === 0 || !selectedTenantId) ? 'opacity-50 cursor-not-allowed' : '',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        3. Bestätigung
                    </button>
                </nav>
            </div>

            <!-- Tab 1: Club Selection -->
            <div v-if="activeTab === 'select-clubs'" class="space-y-4">
                <div>
                    <InputLabel for="club-search" value="Clubs durchsuchen" />
                    <TextInput
                        id="club-search"
                        v-model="clubSearch"
                        type="text"
                        class="mt-1 w-full"
                        placeholder="Club-Name eingeben..."
                    />
                </div>

                <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th scope="col" class="w-12 px-6 py-3">
                                    <span class="sr-only">Select</span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Club-Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktueller Tenant
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="club in filteredClubs"
                                :key="club.id"
                                @click="toggleClub(club.id)"
                                :class="[
                                    'cursor-pointer hover:bg-gray-50',
                                    selectedClubIds.includes(club.id) ? 'bg-indigo-50' : ''
                                ]"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        :checked="selectedClubIds.includes(club.id)"
                                        @click.stop
                                        @change="toggleClub(club.id)"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ club.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ club.tenant?.name || 'N/A' }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="filteredClubs.length === 0" class="text-center py-8 text-gray-500">
                        Keine Clubs gefunden
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                    <p class="text-sm text-blue-800">
                        <strong>{{ selectedClubIds.length }}</strong> {{ selectedClubIds.length === 1 ? 'Club' : 'Clubs' }} ausgewählt
                    </p>
                </div>
            </div>

            <!-- Tab 2: Tenant Selection -->
            <div v-if="activeTab === 'select-tenant'" class="space-y-4">
                <p class="text-sm text-gray-600">
                    Wählen Sie den Ziel-Tenant für <strong>{{ selectedClubIds.length }}</strong> {{ selectedClubIds.length === 1 ? 'Club' : 'Clubs' }}
                </p>

                <div>
                    <InputLabel for="target-tenant-batch" value="Ziel-Tenant" />
                    <select
                        id="target-tenant-batch"
                        v-model="selectedTenantId"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">-- Tenant auswählen --</option>
                        <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                            {{ tenant.name }}
                        </option>
                    </select>
                </div>

                <div v-if="selectedTenant" class="bg-green-50 border border-green-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-green-900 mb-2">Ausgewählter Ziel-Tenant</h4>
                    <p class="text-sm text-green-800">{{ selectedTenant.name }}</p>
                </div>
            </div>

            <!-- Tab 3: Confirmation -->
            <div v-if="activeTab === 'confirm'" class="space-y-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Transfer-Übersicht</h4>

                    <dl class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-600">Anzahl Clubs:</dt>
                            <dd class="font-medium text-gray-900">{{ selectedClubIds.length }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-600">Ziel-Tenant:</dt>
                            <dd class="font-medium text-gray-900">{{ selectedTenant?.name }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 max-h-64 overflow-y-auto">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Ausgewählte Clubs</h4>
                    <ul class="space-y-2">
                        <li v-for="club in selectedClubs" :key="club.id" class="flex items-center text-sm">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-gray-900">{{ club.name }}</span>
                            <span class="ml-2 text-gray-500 text-xs">({{ club.tenant?.name }})</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-yellow-900 mb-2">⚠️ Wichtiger Hinweis</h4>
                    <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                        <li>Alle Clubs werden gleichzeitig zum Ziel-Tenant transferiert</li>
                        <li>Stripe-Subscriptions werden gekündigt</li>
                        <li>User-Memberships werden entfernt</li>
                        <li>Jeder Transfer kann innerhalb von 24h zurückgesetzt werden</li>
                    </ul>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <Checkbox id="confirm-batch-transfer" v-model:checked="form.confirmed" />
                    </div>
                    <div class="ml-3">
                        <label for="confirm-batch-transfer" class="text-sm font-medium text-gray-700">
                            Ich bestätige, dass ich <strong>{{ selectedClubIds.length }}</strong> {{ selectedClubIds.length === 1 ? 'Club' : 'Clubs' }} zu <strong>{{ selectedTenant?.name }}</strong> transferieren möchte.
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="previousTab" v-if="activeTab !== 'select-clubs'">
                Zurück
            </SecondaryButton>

            <SecondaryButton @click="closeModal">
                Abbrechen
            </SecondaryButton>

            <PrimaryButton
                v-if="activeTab !== 'confirm'"
                @click="nextTab"
                :disabled="!canProceed"
                class="ml-3"
            >
                Weiter
            </PrimaryButton>

            <PrimaryButton
                v-if="activeTab === 'confirm'"
                @click="submitBatchTransfer"
                :disabled="!canProceed || form.processing"
                class="ml-3"
            >
                <span v-if="form.processing">Transfer läuft...</span>
                <span v-else>{{ selectedClubIds.length }} {{ selectedClubIds.length === 1 ? 'Club' : 'Clubs' }} transferieren</span>
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
