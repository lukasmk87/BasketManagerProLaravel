<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

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

// Ensure content structure exists
if (!content.value.headline) {
    content.value = {
        headline: content.value.headline || 'Häufig gestellte Fragen',
        items: content.value.items || []
    };
}

const addFaq = () => {
    content.value.items.push({
        question: '',
        answer: ''
    });
};

const removeFaq = (index) => {
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
            <InputLabel for="faq_headline" value="Überschrift *" />
            <TextInput
                id="faq_headline"
                v-model="content.headline"
                type="text"
                class="mt-1 block w-full"
                required
                maxlength="255"
                placeholder="z.B. Häufig gestellte Fragen"
            />
            <InputError class="mt-2" :message="errors['content.headline']" />
            <p class="mt-1 text-xs text-gray-500">{{ characterCount(content.headline, 255).count }} / 255 Zeichen</p>
        </div>

        <!-- FAQ Items -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <InputLabel value="FAQ-Einträge" />
                <span class="text-sm text-gray-500">
                    {{ content.items.length }} {{ content.items.length === 1 ? 'Eintrag' : 'Einträge' }}
                </span>
            </div>

            <DraggableList
                v-model:items="content.items"
                add-label="+ FAQ hinzufügen"
                :min-items="1"
                :max-items="20"
                @add="addFaq"
                @remove="removeFaq"
            >
                <template #default="{ item, index }">
                    <div class="space-y-4">
                        <!-- Question -->
                        <div>
                            <InputLabel :for="`faq_question_${index}`" value="Frage *" />
                            <TextInput
                                :id="`faq_question_${index}`"
                                v-model="item.question"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                maxlength="255"
                                placeholder="z.B. Ist BasketManager Pro DSGVO-konform?"
                            />
                            <InputError class="mt-2" :message="errors[`content.items.${index}.question`]" />
                            <p class="mt-1 text-xs" :class="characterCount(item.question, 255).isOver ? 'text-red-600' : 'text-gray-500'">
                                {{ characterCount(item.question, 255).count }} / 255 Zeichen
                            </p>
                        </div>

                        <!-- Answer -->
                        <div>
                            <InputLabel :for="`faq_answer_${index}`" value="Antwort * (mit Formatierung)" />
                            <RichTextEditor
                                :id="`faq_answer_${index}`"
                                v-model="item.answer"
                                :max-length="2000"
                                placeholder="z.B. Ja, absolut. Wir sind zu 100% DSGVO-konform, hosten ausschließlich in Deutschland und nehmen Datenschutz sehr ernst."
                                :error="errors[`content.items.${index}.answer`]"
                            />
                            <InputError class="mt-2" :message="errors[`content.items.${index}.answer`]" />
                        </div>

                        <!-- Live Preview -->
                        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="text-xs text-gray-500 mb-2 font-medium">Vorschau:</div>
                            <details class="group">
                                <summary class="flex items-center justify-between cursor-pointer list-none font-medium text-gray-900 hover:text-indigo-600">
                                    <span>{{ item.question || 'Frage...' }}</span>
                                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <div class="mt-3 text-gray-600 text-sm prose prose-sm max-w-none" v-html="item.answer || '<p class=\'text-gray-400\'>Antwort...</p>'">
                                </div>
                            </details>
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
                    <p class="font-medium mb-1">Tipps für gute FAQs:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                        <li>Fragen sollten klar und präzise sein</li>
                        <li>Antworten sollten vollständig und hilfreich sein</li>
                        <li>Verwenden Sie einfache Sprache</li>
                        <li>Häufigste Fragen zuerst platzieren (Drag & Drop)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="flex items-center space-x-3">
            <button
                v-if="content.items.length < 20"
                type="button"
                @click="() => { for (let i = 0; i < 3; i++) addFaq(); }"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition text-sm font-medium"
            >
                + 3 FAQs auf einmal hinzufügen
            </button>

            <button
                v-if="content.items.some(item => !item.question && !item.answer)"
                type="button"
                @click="content.items = content.items.filter(item => item.question || item.answer)"
                class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition text-sm font-medium"
            >
                Leere FAQs entfernen
            </button>
        </div>
    </div>
</template>
