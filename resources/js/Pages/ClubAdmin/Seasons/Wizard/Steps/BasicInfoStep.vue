<script setup>
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { CalendarIcon, LightBulbIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);

const localForm = computed({
    get: () => props.form,
    set: (value) => emit('update:form', value)
});

const characterCount = computed(() => {
    return localForm.value.description?.length || 0;
});

const seasonDuration = computed(() => {
    if (!localForm.value.start_date || !localForm.value.end_date) {
        return null;
    }

    const start = new Date(localForm.value.start_date);
    const end = new Date(localForm.value.end_date);

    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const diffWeeks = Math.floor(diffDays / 7);

    return {
        days: diffDays,
        weeks: diffWeeks
    };
});

const suggestedName = computed(() => {
    if (!localForm.value.start_date || !localForm.value.end_date) {
        return null;
    }

    const start = new Date(localForm.value.start_date);
    const end = new Date(localForm.value.end_date);

    const startYear = start.getFullYear();
    const endYear = end.getFullYear();

    if (startYear === endYear) {
        return `Saison ${startYear}`;
    } else {
        return `${startYear}/${endYear}`;
    }
});

const canUseSuggestedName = computed(() => {
    return suggestedName.value && !localForm.value.name;
});

const useSuggestedName = () => {
    if (suggestedName.value) {
        localForm.value.name = suggestedName.value;
    }
};

// Auto-generate name when dates change
watch([() => localForm.value.start_date, () => localForm.value.end_date], () => {
    if (canUseSuggestedName.value) {
        useSuggestedName();
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Basis-Informationen</h2>
            <p class="mt-1 text-sm text-gray-500">
                Geben Sie die grundlegenden Details für die neue Saison ein.
            </p>
        </div>

        <!-- Season Name -->
        <div>
            <InputLabel for="season-name" value="Saison-Name *" />
            <div class="mt-1 flex rounded-md shadow-sm">
                <TextInput
                    id="season-name"
                    v-model="localForm.name"
                    type="text"
                    class="flex-1"
                    placeholder="z.B. 2024/2025"
                    required
                    maxlength="255"
                />
                <button
                    v-if="suggestedName && localForm.name !== suggestedName"
                    type="button"
                    @click="useSuggestedName"
                    class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <LightBulbIcon class="h-4 w-4 mr-1.5 text-yellow-500" />
                    Vorschlag verwenden
                </button>
            </div>
            <InputError :message="localForm.errors?.name" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">
                Ein eindeutiger Name für diese Saison (z.B. "2024/2025" oder "Herbst 2024")
            </p>
            <div v-if="suggestedName" class="mt-2 flex items-center text-sm text-blue-600">
                <LightBulbIcon class="h-4 w-4 mr-1.5" />
                <span>Vorschlag: {{ suggestedName }}</span>
            </div>
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Start Date -->
            <div>
                <InputLabel for="start-date" value="Startdatum *" />
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <CalendarIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                        id="start-date"
                        v-model="localForm.start_date"
                        type="date"
                        class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        required
                    />
                </div>
                <InputError :message="localForm.errors?.start_date" class="mt-2" />
            </div>

            <!-- End Date -->
            <div>
                <InputLabel for="end-date" value="Enddatum *" />
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <CalendarIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                        id="end-date"
                        v-model="localForm.end_date"
                        type="date"
                        class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        required
                        :min="localForm.start_date"
                    />
                </div>
                <InputError :message="localForm.errors?.end_date" class="mt-2" />
            </div>
        </div>

        <!-- Season Duration Info -->
        <div v-if="seasonDuration" class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <CalendarIcon class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800">Saison-Dauer</h4>
                    <div class="mt-1 text-sm text-blue-700">
                        <span class="font-semibold">{{ seasonDuration.weeks }} Wochen</span>
                        <span class="mx-1">•</span>
                        <span>{{ seasonDuration.days }} Tage</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div>
            <InputLabel for="description" value="Beschreibung (Optional)" />
            <textarea
                id="description"
                v-model="localForm.description"
                rows="4"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                maxlength="500"
                placeholder="Geben Sie eine kurze Beschreibung für diese Saison ein..."
            ></textarea>
            <InputError :message="localForm.errors?.description" class="mt-2" />
            <div class="mt-1 flex justify-between text-xs text-gray-500">
                <span>Optional: Zusätzliche Informationen zur Saison</span>
                <span :class="characterCount >= 500 ? 'text-red-600 font-medium' : ''">
                    {{ characterCount }} / 500
                </span>
            </div>
        </div>

        <!-- Help Box -->
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Tipps</h4>
            <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                <li>Wählen Sie eindeutige Namen, um Verwechslungen zu vermeiden</li>
                <li>Das Startdatum sollte vor dem ersten Spiel der Saison liegen</li>
                <li>Das Enddatum sollte nach dem letzten Spiel/Event der Saison liegen</li>
                <li>Die Beschreibung kann später noch angepasst werden</li>
            </ul>
        </div>
    </div>
</template>
