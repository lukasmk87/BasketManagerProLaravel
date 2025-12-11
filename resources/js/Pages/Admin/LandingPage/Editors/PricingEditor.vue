<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const content = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

// Initialize structure
if (!content.value.headline) {
    content.value = {
        headline: content.value.headline || 'Transparent und fair',
        subheadline: content.value.subheadline || 'Wähle den Plan, der zu deinem Verein passt',
        items: content.value.items || []
    };
}

const addPlan = () => {
    content.value.items.push({
        name: '',
        price: '',
        period: 'Monat',
        description: '',
        features: [''],
        cta_text: 'Jetzt starten',
        cta_link: '/register',
        popular: false
    });
};

const removePlan = (index) => {
    content.value.items.splice(index, 1);
};

const addFeature = (plan) => {
    if (!plan.features) plan.features = [];
    plan.features.push('');
};

const removeFeature = (plan, featureIndex) => {
    if (plan.features.length > 1) {
        plan.features.splice(featureIndex, 1);
    }
};

const characterCount = (text, max) => {
    const count = (text || '').length;
    const remaining = max - count;
    return {
        count,
        remaining,
        isOver: count > max
    };
};
</script>

<template>
    <div class="space-y-6">
        <!-- Headlines -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="pricing_headline" value="Überschrift *" />
                <TextInput
                    id="pricing_headline"
                    v-model="content.headline"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    maxlength="255"
                    placeholder="z.B. Transparent und fair"
                />
                <InputError class="mt-2" :message="errors['content.headline']" />
            </div>
            <div>
                <InputLabel for="pricing_subheadline" value="Unterüberschrift *" />
                <TextInput
                    id="pricing_subheadline"
                    v-model="content.subheadline"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    maxlength="500"
                    placeholder="z.B. Wähle den Plan, der zu deinem Verein passt"
                />
                <InputError class="mt-2" :message="errors['content.subheadline']" />
            </div>
        </div>

        <!-- Pricing Plans -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <InputLabel value="Preispläne" />
                <span class="text-sm text-gray-500">
                    {{ content.items.length }} {{ content.items.length === 1 ? 'Plan' : 'Pläne' }}
                </span>
            </div>

            <DraggableList
                v-model:items="content.items"
                add-label="+ Preisplan hinzufügen"
                :min-items="1"
                :max-items="6"
                @add="addPlan"
                @remove="removePlan"
            >
                <template #default="{ item: plan, index }">
                    <div class="space-y-4">
                        <!-- Basic Info Grid -->
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <InputLabel :for="`plan_name_${index}`" value="Plan-Name *" />
                                <TextInput
                                    :id="`plan_name_${index}`"
                                    v-model="plan.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    maxlength="100"
                                    placeholder="z.B. Professional"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.name`]" />
                            </div>
                            <div>
                                <InputLabel :for="`plan_price_${index}`" value="Preis *" />
                                <TextInput
                                    :id="`plan_price_${index}`"
                                    v-model="plan.price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="z.B. 29,99 oder Custom"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.price`]" />
                            </div>
                            <div>
                                <InputLabel :for="`plan_period_${index}`" value="Zeitraum" />
                                <select
                                    :id="`plan_period_${index}`"
                                    v-model="plan.period"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="Monat">Monat</option>
                                    <option value="Jahr">Jahr</option>
                                    <option value="">Custom</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="plan.popular" />
                                    <span class="ml-2 text-sm text-gray-700">Als "Beliebt" markieren</span>
                                </label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel :for="`plan_description_${index}`" value="Beschreibung" />
                            <TextInput
                                :id="`plan_description_${index}`"
                                v-model="plan.description"
                                type="text"
                                class="mt-1 block w-full"
                                maxlength="200"
                                placeholder="z.B. Für professionelle Vereine"
                            />
                            <InputError class="mt-2" :message="errors[`content.items.${index}.description`]" />
                            <p class="mt-1 text-xs text-gray-500">{{ characterCount(plan.description, 200).count }} / 200 Zeichen</p>
                        </div>

                        <!-- Features (Nested Array) -->
                        <div>
                            <InputLabel value="Features *" />
                            <div class="space-y-2 mt-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div v-for="(feature, fIndex) in plan.features" :key="fIndex" class="flex space-x-2">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-10 text-xs text-gray-500">
                                        {{ fIndex + 1 }}.
                                    </div>
                                    <TextInput
                                        v-model="plan.features[fIndex]"
                                        type="text"
                                        class="flex-1"
                                        placeholder="z.B. 10 Teams"
                                        maxlength="200"
                                    />
                                    <button
                                        v-if="plan.features.length > 1"
                                        type="button"
                                        @click="removeFeature(plan, fIndex)"
                                        class="flex-shrink-0 px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition"
                                        title="Feature entfernen"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addFeature(plan)"
                                    class="w-full px-3 py-2 text-sm text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition flex items-center justify-center space-x-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Feature hinzufügen</span>
                                </button>
                                <InputError class="mt-2" :message="errors[`content.items.${index}.features`]" />
                            </div>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel :for="`plan_cta_text_${index}`" value="Button Text *" />
                                <TextInput
                                    :id="`plan_cta_text_${index}`"
                                    v-model="plan.cta_text"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    maxlength="50"
                                    placeholder="z.B. Jetzt starten"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.cta_text`]" />
                            </div>
                            <div>
                                <InputLabel :for="`plan_cta_link_${index}`" value="Button Link *" />
                                <TextInput
                                    :id="`plan_cta_link_${index}`"
                                    v-model="plan.cta_link"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="/register?plan=professional"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.cta_link`]" />
                            </div>
                        </div>

                        <!-- Preview Card -->
                        <div class="p-6 bg-white border-2 rounded-xl shadow-sm relative" :class="plan.popular ? 'border-indigo-600 ring-2 ring-indigo-200' : 'border-gray-200'">
                            <div class="text-xs text-gray-500 mb-3 font-medium">Vorschau:</div>

                            <!-- Popular Badge -->
                            <span v-if="plan.popular" class="absolute top-4 right-4 inline-block px-3 py-1 bg-indigo-600 text-white text-xs font-semibold rounded-full">
                                Beliebt
                            </span>

                            <!-- Plan Name -->
                            <h3 class="text-2xl font-bold text-gray-900">{{ plan.name || 'Plan-Name' }}</h3>
                            <p class="text-gray-600 mt-1 text-sm">{{ plan.description || 'Beschreibung' }}</p>

                            <!-- Price -->
                            <div class="mt-4 flex items-baseline">
                                <span class="text-4xl font-bold text-gray-900">
                                    {{ plan.price || '0' }}{{ plan.price && !isNaN(parseFloat(plan.price.replace(',', '.'))) ? '€' : '' }}
                                </span>
                                <span v-if="plan.period" class="text-gray-500 ml-2">/{{ plan.period }}</span>
                            </div>

                            <!-- Features List -->
                            <ul class="mt-6 space-y-3">
                                <li
                                    v-for="(feature, fIdx) in plan.features.filter(f => f)"
                                    :key="fIdx"
                                    class="flex items-start"
                                >
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-700 text-sm">{{ feature }}</span>
                                </li>
                            </ul>

                            <!-- CTA Button -->
                            <button class="mt-6 w-full py-3 rounded-lg font-semibold transition"
                                :class="plan.popular ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-800 text-white hover:bg-gray-900'"
                            >
                                {{ plan.cta_text || 'Jetzt starten' }}
                            </button>
                        </div>
                    </div>
                </template>
            </DraggableList>
        </div>

        <!-- Help Text -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-medium mb-1">Tipps für überzeugende Preispläne:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                        <li><strong>Geben Sie Preise OHNE MwSt. an</strong> - das System zeigt sie automatisch mit korrekter MwSt. an</li>
                        <li>Für Kleingewerbe (§19 UStG) wird automatisch der Hinweis ergänzt</li>
                        <li>Markieren Sie den empfohlenen Plan als "Beliebt" für höhere Conversions</li>
                        <li>Features sollten konkrete Vorteile kommunizieren (nicht nur Zahlen)</li>
                        <li>Sortieren Sie Pläne von günstig nach teuer (Drag & Drop)</li>
                        <li>Empfohlen: 3-4 Pläne für optimale Auswahlmöglichkeiten</li>
                        <li>Verwenden Sie klare Call-to-Actions ("Jetzt starten", "Demo buchen")</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
