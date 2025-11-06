<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import ImageUploader from '@/Components/Landing/ImageUploader.vue';
import StarRating from '@/Components/Landing/StarRating.vue';
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
        headline: content.value.headline || 'Was unsere Kunden sagen',
        items: content.value.items || []
    };
}

const addTestimonial = () => {
    content.value.items.push({
        name: '',
        role: '',
        club: '',
        quote: '',
        rating: 5,
        image: null
    });
};

const removeTestimonial = (index) => {
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
            <InputLabel for="testimonials_headline" value="Überschrift *" />
            <TextInput
                id="testimonials_headline"
                v-model="content.headline"
                type="text"
                class="mt-1 block w-full"
                required
                maxlength="255"
                placeholder="z.B. Was unsere Kunden sagen"
            />
            <InputError class="mt-2" :message="errors['content.headline']" />
            <p class="mt-1 text-xs text-gray-500">{{ characterCount(content.headline, 255).count }} / 255 Zeichen</p>
        </div>

        <!-- Testimonials List -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <InputLabel value="Kundenstimmen" />
                <span class="text-sm text-gray-500">
                    {{ content.items.length }} {{ content.items.length === 1 ? 'Testimonial' : 'Testimonials' }}
                </span>
            </div>

            <DraggableList
                v-model:items="content.items"
                add-label="+ Testimonial hinzufügen"
                :min-items="1"
                :max-items="10"
                @add="addTestimonial"
                @remove="removeTestimonial"
            >
                <template #default="{ item, index }">
                    <div class="space-y-4">
                        <!-- Basic Info Grid -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel :for="`testimonial_name_${index}`" value="Name *" />
                                <TextInput
                                    :id="`testimonial_name_${index}`"
                                    v-model="item.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    maxlength="100"
                                    placeholder="z.B. Michael Schmidt"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.name`]" />
                            </div>
                            <div>
                                <InputLabel :for="`testimonial_role_${index}`" value="Rolle *" />
                                <TextInput
                                    :id="`testimonial_role_${index}`"
                                    v-model="item.role"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    maxlength="100"
                                    placeholder="z.B. Vorstand"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.role`]" />
                            </div>
                            <div>
                                <InputLabel :for="`testimonial_club_${index}`" value="Verein *" />
                                <TextInput
                                    :id="`testimonial_club_${index}`"
                                    v-model="item.club"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    maxlength="100"
                                    placeholder="z.B. BBC München"
                                />
                                <InputError class="mt-2" :message="errors[`content.items.${index}.club`]" />
                            </div>
                        </div>

                        <!-- Quote -->
                        <div>
                            <InputLabel :for="`testimonial_quote_${index}`" value="Zitat *" />
                            <textarea
                                :id="`testimonial_quote_${index}`"
                                v-model="item.quote"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                                maxlength="300"
                                placeholder="z.B. BasketManager Pro hat unsere Vereinsverwaltung revolutioniert..."
                            ></textarea>
                            <InputError class="mt-2" :message="errors[`content.items.${index}.quote`]" />

                            <!-- Character Counter -->
                            <div class="mt-2">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs" :class="characterCount(item.quote, 300).isOver ? 'text-red-600' : 'text-gray-500'">
                                        {{ characterCount(item.quote, 300).count }} / 300 Zeichen
                                    </p>
                                    <p class="text-xs" :class="characterCount(item.quote, 300).remaining < 0 ? 'text-red-600' : 'text-gray-500'">
                                        {{ characterCount(item.quote, 300).remaining >= 0 ? `${characterCount(item.quote, 300).remaining} verbleibend` : `${Math.abs(characterCount(item.quote, 300).remaining)} zu viel` }}
                                    </p>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div
                                        class="h-1.5 rounded-full transition-all"
                                        :class="characterCount(item.quote, 300).isOver ? 'bg-red-600' : 'bg-indigo-600'"
                                        :style="{ width: `${Math.min(100, characterCount(item.quote, 300).percentage)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating -->
                        <StarRating
                            v-model="item.rating"
                            label="Bewertung"
                        />
                        <InputError class="mt-2" :message="errors[`content.items.${index}.rating`]" />

                        <!-- Image Upload -->
                        <ImageUploader
                            v-model="item.image"
                            label="Bild (optional)"
                            :max-size="2048"
                        />
                        <InputError class="mt-2" :message="errors[`content.items.${index}.image`]" />

                        <!-- Preview Card -->
                        <div class="p-6 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="text-xs text-gray-500 mb-3 font-medium">Vorschau:</div>
                            <div class="flex items-start space-x-4">
                                <!-- Avatar -->
                                <div v-if="item.image" class="flex-shrink-0 w-16 h-16 rounded-full bg-gray-200 overflow-hidden">
                                    <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="flex-shrink-0 w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-2xl">
                                    {{ (item.name || 'X').charAt(0).toUpperCase() }}
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <p class="text-sm italic text-gray-700 mb-3">"{{ item.quote || 'Zitat...' }}"</p>

                                    <!-- Stars -->
                                    <div class="flex items-center space-x-1 mb-2">
                                        <svg
                                            v-for="i in 5"
                                            :key="i"
                                            class="w-4 h-4"
                                            :class="i <= (item.rating || 0) ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>

                                    <!-- Info -->
                                    <p class="text-sm font-semibold text-gray-900">{{ item.name || 'Name' }}</p>
                                    <p class="text-xs text-gray-500">{{ item.role || 'Rolle' }} • {{ item.club || 'Verein' }}</p>
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
                    <p class="font-medium mb-1">Tipps für überzeugende Testimonials:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                        <li>Verwenden Sie echte Kundenzitate mit konkreten Vorteilen</li>
                        <li>Namen und Vereinszugehörigkeit schaffen Glaubwürdigkeit</li>
                        <li>Bilder machen Testimonials persönlicher (optional aber empfohlen)</li>
                        <li>Fokus auf spezifische Features oder Ergebnisse</li>
                        <li>Empfohlen: 3-5 Testimonials für optimale Wirkung</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
