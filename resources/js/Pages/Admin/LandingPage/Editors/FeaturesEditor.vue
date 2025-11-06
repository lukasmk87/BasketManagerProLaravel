<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import IconPicker from '@/Components/Landing/IconPicker.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

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
        headline: content.value.headline || 'Alles, was dein Verein braucht',
        subheadline: content.value.subheadline || 'Eine Plattform für alle Anforderungen moderner Basketballvereine',
        items: content.value.items || []
    };
}

const addFeature = () => {
    content.value.items.push({
        icon: '',
        title: '',
        description: ''
    });
};

const removeFeature = (index) => {
    content.value.items.splice(index, 1);
};

const characterCount = (text, max) => {
    const count = (text || '').length;
    const remaining = max - count;
    return {
        count,
        remaining,
        isOver: count > max,
        percentage: Math.min(100, (count / max) * 100)
    };
};
</script>

<template>
    <div class="space-y-6">
        <!-- Headline -->
        <div>
            <InputLabel for="features_headline" value="Überschrift *" />
            <TextInput
                id="features_headline"
                v-model="content.headline"
                type="text"
                class="mt-1 block w-full"
                required
                maxlength="255"
                placeholder="z.B. Alles, was dein Verein braucht"
            />
            <InputError class="mt-2" :message="errors['content.headline']" />
            <p class="mt-1 text-xs text-gray-500">{{ characterCount(content.headline, 255).count }} / 255 Zeichen</p>
        </div>

        <!-- Subheadline -->
        <div>
            <InputLabel for="features_subheadline" value="Unterüberschrift *" />
            <textarea
                id="features_subheadline"
                v-model="content.subheadline"
                rows="2"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                required
                maxlength="500"
                placeholder="z.B. Eine Plattform für alle Anforderungen moderner Basketballvereine"
            ></textarea>
            <InputError class="mt-2" :message="errors['content.subheadline']" />
            <p class="mt-1 text-xs text-gray-500">{{ characterCount(content.subheadline, 500).count }} / 500 Zeichen</p>
        </div>

        <!-- Features List -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <InputLabel value="Feature-Cards" />
                <span class="text-sm text-gray-500">
                    {{ content.items.length }} {{ content.items.length === 1 ? 'Feature' : 'Features' }}
                </span>
            </div>

            <DraggableList
                v-model:items="content.items"
                add-label="+ Feature hinzufügen"
                :min-items="1"
                :max-items="10"
                @add="addFeature"
                @remove="removeFeature"
            >
                <template #default="{ item, index }">
                    <div class="space-y-4">
                        <!-- Icon Picker -->
                        <IconPicker
                            v-model="item.icon"
                            label="Icon auswählen"
                        />
                        <InputError class="mt-2" :message="errors[`content.items.${index}.icon`]" />

                        <!-- Title -->
                        <div>
                            <InputLabel :for="`feature_title_${index}`" value="Titel *" />
                            <TextInput
                                :id="`feature_title_${index}`"
                                v-model="item.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                maxlength="100"
                                placeholder="z.B. Live-Scoring & Statistiken"
                            />
                            <InputError class="mt-2" :message="errors[`content.items.${index}.title`]" />
                            <p class="mt-1 text-xs" :class="characterCount(item.title, 100).isOver ? 'text-red-600' : 'text-gray-500'">
                                {{ characterCount(item.title, 100).count }} / 100 Zeichen
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel :for="`feature_description_${index}`" value="Beschreibung *" />
                            <textarea
                                :id="`feature_description_${index}`"
                                v-model="item.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                                maxlength="200"
                                placeholder="z.B. Erfasse Spielzüge in Echtzeit und generiere automatisch detaillierte Statistiken."
                            ></textarea>
                            <InputError class="mt-2" :message="errors[`content.items.${index}.description`]" />

                            <!-- Character Counter with Progress Bar -->
                            <div class="mt-2">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs" :class="characterCount(item.description, 200).isOver ? 'text-red-600' : 'text-gray-500'">
                                        {{ characterCount(item.description, 200).count }} / 200 Zeichen
                                    </p>
                                    <p class="text-xs" :class="characterCount(item.description, 200).remaining < 0 ? 'text-red-600' : 'text-gray-500'">
                                        {{ characterCount(item.description, 200).remaining >= 0 ? `${characterCount(item.description, 200).remaining} verbleibend` : `${Math.abs(characterCount(item.description, 200).remaining)} zu viel` }}
                                    </p>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div
                                        class="h-1.5 rounded-full transition-all"
                                        :class="characterCount(item.description, 200).isOver ? 'bg-red-600' : 'bg-indigo-600'"
                                        :style="{ width: `${Math.min(100, characterCount(item.description, 200).percentage)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Card -->
                        <div class="p-6 bg-white border-2 border-gray-200 rounded-xl hover:border-indigo-300 transition-all">
                            <div class="text-xs text-gray-500 mb-3 font-medium">Vorschau:</div>
                            <div class="flex items-start space-x-4">
                                <div v-if="item.icon" class="flex-shrink-0 w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div v-else class="flex-shrink-0 w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ item.title || 'Titel...' }}</h4>
                                    <p class="text-sm text-gray-600">{{ item.description || 'Beschreibung...' }}</p>
                                </div>
                            </div>
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
                    <p class="font-medium mb-1">Tipps für überzeugende Features:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                        <li>Wählen Sie aussagekräftige Icons, die das Feature visuell repräsentieren</li>
                        <li>Titel sollten kurz und prägnant sein (max 100 Zeichen)</li>
                        <li>Beschreibungen sollten den konkreten Nutzen hervorheben</li>
                        <li>Wichtigste Features zuerst platzieren (Drag & Drop zum Sortieren)</li>
                        <li>Empfohlen: 6-8 Features für optimale Übersichtlichkeit</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
